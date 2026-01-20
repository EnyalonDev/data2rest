<?php

namespace App\Core;

use PDO;

/**
 * Rate Limiter Service
 * 
 * Implements token bucket algorithm for API rate limiting.
 * Tracks requests per API key with configurable limits and time windows.
 * 
 * Features:
 * - Configurable rate limits per API key
 * - Automatic window reset
 * - Response headers for client feedback
 * - Cleanup of old tracking records
 * 
 * @package App\Core
 * @version 1.0.0
 */
class RateLimiter
{
    private $db;

    /**
     * Default rate limit: 1000 requests per hour
     */
    const DEFAULT_LIMIT = 1000;
    const DEFAULT_WINDOW = 3600; // 1 hour in seconds

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check if request is allowed under rate limit
     * 
     * @param int $apiKeyId API key ID
     * @param string $endpoint Endpoint being accessed
     * @param int $limit Maximum requests allowed (default: 1000)
     * @param int $window Time window in seconds (default: 3600)
     * @return array ['allowed' => bool, 'limit' => int, 'remaining' => int, 'reset' => timestamp]
     */
    public function checkLimit($apiKeyId, $endpoint = 'global', $limit = null, $window = null)
    {
        $limit = $limit ?? self::DEFAULT_LIMIT;
        $window = $window ?? self::DEFAULT_WINDOW;

        $now = time();
        $windowStart = date('Y-m-d H:i:s', $now - $window);

        // Get or create rate limit record
        $stmt = $this->db->prepare("
            SELECT id, request_count, window_start 
            FROM api_rate_limits 
            WHERE api_key_id = ? AND endpoint = ? 
            AND datetime(window_start) > datetime(?)
            ORDER BY window_start DESC 
            LIMIT 1
        ");
        $stmt->execute([$apiKeyId, $endpoint, $windowStart]);
        $record = $stmt->fetch();

        if (!$record) {
            // Create new window
            $stmt = $this->db->prepare("
                INSERT INTO api_rate_limits (api_key_id, endpoint, request_count, window_start) 
                VALUES (?, ?, 1, ?)
            ");
            $stmt->execute([$apiKeyId, $endpoint, date('Y-m-d H:i:s', $now)]);

            return [
                'allowed' => true,
                'limit' => $limit,
                'remaining' => $limit - 1,
                'reset' => $now + $window
            ];
        }

        // Check if limit exceeded
        if ($record['request_count'] >= $limit) {
            $resetTime = strtotime($record['window_start']) + $window;
            return [
                'allowed' => false,
                'limit' => $limit,
                'remaining' => 0,
                'reset' => $resetTime
            ];
        }

        // Increment counter
        $stmt = $this->db->prepare("
            UPDATE api_rate_limits 
            SET request_count = request_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$record['id']]);

        $remaining = $limit - ($record['request_count'] + 1);
        $resetTime = strtotime($record['window_start']) + $window;

        return [
            'allowed' => true,
            'limit' => $limit,
            'remaining' => max(0, $remaining),
            'reset' => $resetTime
        ];
    }

    /**
     * Set rate limit headers in response
     * 
     * @param array $limitInfo Result from checkLimit()
     * @return void
     */
    public function setHeaders($limitInfo)
    {
        header("X-RateLimit-Limit: {$limitInfo['limit']}");
        header("X-RateLimit-Remaining: {$limitInfo['remaining']}");
        header("X-RateLimit-Reset: {$limitInfo['reset']}");
    }

    /**
     * Clean up old rate limit records (older than 24 hours)
     * Should be called periodically via cron or maintenance task
     * 
     * @return int Number of records deleted
     */
    public function cleanup()
    {
        $cutoff = date('Y-m-d H:i:s', time() - 86400); // 24 hours ago
        $stmt = $this->db->prepare("DELETE FROM api_rate_limits WHERE window_start < ?");
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }

    /**
     * Get rate limit stats for an API key
     * 
     * @param int $apiKeyId
     * @return array Statistics
     */
    public function getStats($apiKeyId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                endpoint,
                SUM(request_count) as total_requests,
                COUNT(*) as windows_used,
                MAX(window_start) as last_request
            FROM api_rate_limits 
            WHERE api_key_id = ? 
            AND datetime(window_start) > datetime('now', '-24 hours')
            GROUP BY endpoint
        ");
        $stmt->execute([$apiKeyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
