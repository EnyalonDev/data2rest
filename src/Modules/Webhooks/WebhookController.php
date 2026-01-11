<?php

namespace App\Modules\Webhooks;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Database;
use App\Core\Config;
use PDO;

class WebhookController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function index()
    {
        $projectId = Auth::getActiveProject();
        if (!$projectId) {
            $this->redirect('admin/projects/select');
        }

        $db = Database::getInstance()->getConnection();

        // Fetch Webhooks
        $stmt = $db->prepare("SELECT * FROM webhooks WHERE project_id = ? ORDER BY created_at DESC");
        $stmt->execute([$projectId]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Recent Logs for chart/stats
        $stats = [
            'total' => count($webhooks),
            'active' => 0,
            'success_rate' => 0,
            'logs_count' => 0
        ];

        foreach ($webhooks as $w) {
            if ($w['status'])
                $stats['active']++;
        }

        // Calculate success rate from last 100 logs
        $stmtLogs = $db->prepare("
            SELECT response_code FROM webhook_logs l 
            JOIN webhooks w ON l.webhook_id = w.id 
            WHERE w.project_id = ? 
            ORDER BY l.triggered_at DESC LIMIT 100
        ");
        $stmtLogs->execute([$projectId]);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_COLUMN);

        if (count($logs) > 0) {
            $success = count(array_filter($logs, function ($code) {
                return $code >= 200 && $code < 300;
            }));
            $stats['success_rate'] = round(($success / count($logs)) * 100);
            $stats['logs_count'] = count($logs);
        }

        $this->view('admin/webhooks/index', [
            'webhooks' => $webhooks,
            'stats' => $stats,
            'title' => 'Webhooks',
            'events' => $this->getAvailableEvents()
        ]);
    }

    public function form()
    {
        $projectId = Auth::getActiveProject();
        if (!$projectId)
            $this->redirect('admin/projects/select');

        $id = $_GET['id'] ?? null;
        $webhook = null;

        if ($id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM webhooks WHERE id = ? AND project_id = ?");
            $stmt->execute([$id, $projectId]);
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$webhook) {
                // Flash error?
                $this->redirect('admin/webhooks');
            }
        }

        $this->view('admin/webhooks/form', [
            'webhook' => $webhook,
            'title' => $webhook ? 'Edit Webhook' : 'New Webhook',
            'availableEvents' => $this->getAvailableEvents()
        ]);
    }

    public function save()
    {
        $projectId = Auth::getActiveProject();
        if (!$projectId)
            return $this->json(['error' => 'No active project'], 400);

        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? 'Untitled Webhook';
        $url = $_POST['url'] ?? '';
        $events = $_POST['events'] ?? []; // Array
        $secret = $_POST['secret'] ?? '';
        $status = isset($_POST['status']) ? 1 : 0;

        if (empty($url)) {
            // Check basic validation
            // If AJAX call: return JSON. If Form submit: redirect back.
            // Assuming form submit for now with potential ajax wrapper
        }

        $eventsStr = is_array($events) ? implode(',', $events) : $events;

        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("UPDATE webhooks SET name = ?, url = ?, events = ?, secret = ?, status = ? WHERE id = ? AND project_id = ?");
            $stmt->execute([$name, $url, $eventsStr, $secret, $status, $id, $projectId]);
        } else {
            $stmt = $db->prepare("INSERT INTO webhooks (project_id, name, url, events, secret, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$projectId, $name, $url, $eventsStr, $secret, $status]);
        }

        $this->redirect('admin/webhooks');
    }

    public function delete()
    {
        $projectId = Auth::getActiveProject();
        $id = $_GET['id'] ?? $_POST['id'] ?? null;

        if ($id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM webhooks WHERE id = ? AND project_id = ?");
            $stmt->execute([$id, $projectId]);
        }

        $this->redirect('admin/webhooks');
    }

    public function logs()
    {
        $projectId = Auth::getActiveProject();
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('admin/webhooks');

        $db = Database::getInstance()->getConnection();

        // Verify ownership
        $check = $db->prepare("SELECT id, name FROM webhooks WHERE id = ? AND project_id = ?");
        $check->execute([$id, $projectId]);
        $webhook = $check->fetch();

        if (!$webhook)
            $this->redirect('admin/webhooks');

        $stmt = $db->prepare("SELECT * FROM webhook_logs WHERE webhook_id = ? ORDER BY triggered_at DESC LIMIT 50");
        $stmt->execute([$id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/webhooks/logs', [
            'webhook' => $webhook,
            'logs' => $logs,
            'title' => 'Webhook Logs: ' . $webhook['name']
        ]);
    }

    public function test()
    {
        $projectId = Auth::getActiveProject();
        $id = $_POST['id'] ?? null;

        if (!$id)
            return $this->json(['error' => 'ID Missing'], 400);

        WebhookDispatcher::dispatch($projectId, 'test.event', [
            'message' => 'This is a test event from Data2Rest.',
            'triggered_by' => $_SESSION['username'] ?? 'Unknown'
        ]);

        return $this->json(['success' => true, 'message' => 'Test event dispatched']);
    }

    private function getAvailableEvents()
    {
        return [
            'record.created' => 'Record Created',
            'record.updated' => 'Record Updated',
            'record.deleted' => 'Record Deleted',
            'media.uploaded' => 'File Uploaded',
            'test.event' => 'Test Event'
        ];
    }
}
