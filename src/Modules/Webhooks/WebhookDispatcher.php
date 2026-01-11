<?php

namespace App\Modules\Webhooks;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class WebhookDispatcher
{
    /**
     * Dispatches webhooks for a specific event.
     *
     * @param int|null $projectId Project ID context.
     * @param string $event Event name (e.g., 'record.created', 'record.updated').
     * @param array $payload Data payload to send.
     */
    public static function dispatch($projectId, $event, $payload)
    {
        if (!$projectId)
            return;

        $db = Database::getInstance()->getConnection();

        // Find active webhooks for this project that listen to this event (or 'all')
        // We store events as comma-separated values or 'all'
        $stmt = $db->prepare("SELECT * FROM webhooks WHERE project_id = ? AND status = 1");
        $stmt->execute([$projectId]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($webhooks))
            return;

        // Run in background if possible, or fast-timeout synchronous
        foreach ($webhooks as $webhook) {
            $events = explode(',', $webhook['events']);
            if (in_array('all', $events) || in_array($event, $events)) {
                self::send($webhook, $event, $payload);
            }
        }
    }

    private static function send($webhook, $event, $payload)
    {
        $payloadData = [
            'event' => $event,
            'timestamp' => date('c'),
            'payload' => $payload
        ];

        $jsonPayload = json_encode($payloadData);
        $signature = hash_hmac('sha256', $jsonPayload, $webhook['secret'] ?? '');

        $ch = curl_init($webhook['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Data2Rest-Signature: ' . $signature,
            'X-Data2Rest-Event: ' . $event,
            'User-Agent: Data2Rest-Webhook/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Short timeout to not block main thread too much

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log the execution
        self::logExecution($webhook['id'], $event, $jsonPayload, $httpCode, $response ?: $error);

        // Update last trigger time
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE webhooks SET last_triggered_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$webhook['id']]);
    }

    private static function logExecution($webhookId, $event, $payload, $code, $response)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO webhook_logs (webhook_id, event, payload, response_code, response_body) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$webhookId, $event, $payload, $code, substr($response, 0, 5000)]); // Limit response size
        } catch (\Exception $e) {
            // Silently fail logging if DB issue, don't crash the main flow
        }
    }
}
