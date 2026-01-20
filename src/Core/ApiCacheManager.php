<?php

namespace App\Core;

use PDO;

/**
 * API Response Cache Manager
 * 
 * Implements HTTP caching with ETag support for API responses.
 * Reduces server load and improves response times for repeated requests.
 * 
 * Features:
 * - ETag generation based on content hash
 * - Cache-Control headers
 * - Last-Modified tracking
 * - Automatic invalidation on data changes
 * - Configurable TTL per endpoint
 * 
 * @package App\Core
 * @version 1.0.0
 */
class ApiCacheManager
{
    private $db;
    private $enabled;
    private $defaultTTL = 300; // 5 minutes

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->enabled = Config::get('api_cache_enabled') ?? true;
    }

    /**
     * Generate ETag for content
     * 
     * @param mixed $content Content to hash
     * @return string ETag value
     */
    public function generateETag($content)
    {
        $hash = md5(json_encode($content));
        return '"' . $hash . '"';
    }

    /**
     * Check if client has valid cached version
     * 
     * @param string $etag Current ETag
     * @return bool True if client cache is valid
     */
    public function isClientCacheValid($etag)
    {
        $clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        return $clientETag === $etag;
    }

    /**
     * Set cache headers for response
     * 
     * @param string $etag ETag value
     * @param int $ttl Time to live in seconds
     * @param string|null $lastModified Last modified timestamp
     * @return void
     */
    public function setCacheHeaders($etag, $ttl = null, $lastModified = null)
    {
        if (!$this->enabled) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            return;
        }

        $ttl = $ttl ?? $this->defaultTTL;

        header("ETag: $etag");
        header("Cache-Control: public, max-age=$ttl");

        if ($lastModified) {
            header("Last-Modified: " . gmdate('D, d M Y H:i:s', strtotime($lastModified)) . ' GMT');
        }

        // Allow conditional requests
        header('Vary: Accept-Encoding, X-API-KEY');
    }

    /**
     * Send 304 Not Modified response
     * 
     * @return void
     */
    public function send304NotModified()
    {
        http_response_code(304);
        exit;
    }

    /**
     * Get cache key for request
     * 
     * @param string $endpoint Endpoint identifier
     * @param array $params Query parameters
     * @return string Cache key
     */
    public function getCacheKey($endpoint, $params = [])
    {
        ksort($params); // Normalize parameter order
        return md5($endpoint . json_encode($params));
    }

    /**
     * Store response in cache
     * 
     * @param string $cacheKey Cache key
     * @param mixed $data Response data
     * @param int $ttl Time to live in seconds
     * @return bool Success
     */
    public function store($cacheKey, $data, $ttl = null)
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTTL;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        try {
            // Create cache table if not exists
            $this->ensureCacheTable();

            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO api_cache 
                (cache_key, data, expires_at, created_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");

            return $stmt->execute([
                $cacheKey,
                json_encode($data),
                $expiresAt
            ]);
        } catch (\Exception $e) {
            error_log("Cache store error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve response from cache
     * 
     * @param string $cacheKey Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public function get($cacheKey)
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT data, expires_at 
                FROM api_cache 
                WHERE cache_key = ? 
                AND datetime(expires_at) > datetime('now')
            ");
            $stmt->execute([$cacheKey]);
            $result = $stmt->fetch();

            if ($result) {
                return json_decode($result['data'], true);
            }
        } catch (\Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Invalidate cache for specific endpoint or pattern
     * 
     * @param string $pattern Pattern to match (e.g., 'db_1_table_users')
     * @return int Number of entries invalidated
     */
    public function invalidate($pattern)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM api_cache 
                WHERE cache_key LIKE ?
            ");
            $stmt->execute(["%$pattern%"]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Cache invalidate error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear all expired cache entries
     * 
     * @return int Number of entries cleared
     */
    public function clearExpired()
    {
        try {
            $stmt = $this->db->query("
                DELETE FROM api_cache 
                WHERE datetime(expires_at) <= datetime('now')
            ");
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cache statistics
     * 
     * @return array Statistics
     */
    public function getStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN datetime(expires_at) > datetime('now') THEN 1 END) as active_entries,
                    COUNT(CASE WHEN datetime(expires_at) <= datetime('now') THEN 1 END) as expired_entries
                FROM api_cache
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return ['total_entries' => 0, 'active_entries' => 0, 'expired_entries' => 0];
        }
    }

    /**
     * Ensure cache table exists
     * 
     * @return void
     */
    private function ensureCacheTable()
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS api_cache (
                    cache_key TEXT PRIMARY KEY,
                    data TEXT NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create index for expiration cleanup
            $this->db->exec("
                CREATE INDEX IF NOT EXISTS idx_api_cache_expires 
                ON api_cache(expires_at)
            ");
        } catch (\Exception $e) {
            error_log("Cache table creation error: " . $e->getMessage());
        }
    }
}
