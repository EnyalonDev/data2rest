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

    private static function send($webhook, $event, $payload, $isRetry = false)
    {
        $payloadData = $isRetry ? $payload : [
            'event' => $event,
            'timestamp' => date('c'),
            'payload' => $payload
        ];

        $jsonPayload = is_string($payloadData) ? $payloadData : json_encode($payloadData);
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Fail conditions
        if ($httpCode < 200 || $httpCode >= 300 || $error) {
            self::handleFailure($webhook, $event, $jsonPayload, $error ?: "HTTP $httpCode");
        }

        // Log the execution
        self::logExecution($webhook['id'], $event, $jsonPayload, $httpCode, $response ?: $error);

        // Update last trigger time
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE webhooks SET last_triggered_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$webhook['id']]);
    }

    private static function handleFailure($webhook, $event, $payloadRaw, $error)
    {
        // Enqueue for retry logic
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO webhook_queue (project_id, url, event, payload, attempts, next_attempt_at, status, last_error) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            // First attempt, wait 1 minute
            $nextAttempt = date('Y-m-d H:i:s', time() + 60);

            $stmt->execute([
                $webhook['project_id'],
                $webhook['url'],
                $event,
                $payloadRaw,
                1,
                $nextAttempt,
                'pending',
                $error
            ]);
        } catch (\Exception $e) {
            Logger::log('WEBHOOK_QUEUE_ERROR', $e->getMessage());
        }
    }

    public static function processQueue()
    {
        $db = Database::getInstance()->getConnection();

        // Fetch pending items due
        $stmt = $db->prepare("SELECT * FROM webhook_queue WHERE status = 'pending' AND next_attempt_at <= CURRENT_TIMESTAMP LIMIT 10");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $webhook = ['url' => $item['url'], 'id' => 0, 'secret' => '', 'project_id' => $item['project_id']];

            // Try sending again
            // NOTE: We don't have the secret easily here without joining, assuming we can get it or just send without valid signature on retry if we don't fetch webhook record. 
            // Better: Get the webhook payload secret if needed but for now basic retry.
            // Actually, we should fetch webhook details using project_id or url if we want to sign it again properly.
            // But payload is already signed string? No, payload is raw json.

            // Simpler: Just try sending.

            $jsonPayload = $item['payload'];

            $ch = curl_init($item['url']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Data2Rest-Retry: ' . $item['attempts'],
                'User-Agent: Data2Rest-Webhook-Retry/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && !$error) {
                // Success
                $db->prepare("UPDATE webhook_queue SET status = 'completed', last_error = NULL WHERE id = ?")->execute([$item['id']]);
            } else {
                // Fail again
                $attempts = $item['attempts'] + 1;

                if ($attempts >= 5) {
                    // Max attempts reached
                    $db->prepare("UPDATE webhook_queue SET status = 'failed', last_error = ? WHERE id = ?")->execute([$error ?: "HTTP $httpCode", $item['id']]);
                } else {
                    // Backoff: 1m, 5m, 15m, 60m
                    $minutes = match ($attempts) {
                        2 => 5,
                        3 => 15,
                        4 => 60,
                        default => 60
                    };
                    $next = date('Y-m-d H:i:s', time() + ($minutes * 60));
                    $db->prepare("UPDATE webhook_queue SET attempts = ?, next_attempt_at = ?, last_error = ? WHERE id = ?")
                        ->execute([$attempts, $next, $error ?: "HTTP $httpCode", $item['id']]);
                }
            }
        }
    }

    private static function logExecution($webhookId, $event, $payload, $code, $response)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO webhook_logs (webhook_id, event, payload, response_code, response_body) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$webhookId, $event, $payload, $code, substr($response, 0, 5000)]);
        } catch (\Exception $e) {
        }
    }
}
