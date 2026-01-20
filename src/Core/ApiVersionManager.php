<?php

namespace App\Core;

/**
 * API Version Manager
 * 
 * Handles API versioning to allow backward-compatible evolution.
 * Supports version detection from URL path and Accept header.
 * 
 * Supported formats:
 * - URL: /api/v1/db/1/users
 * - Header: Accept: application/vnd.data2rest.v2+json
 * 
 * @package App\Core
 * @version 1.0.0
 */
class ApiVersionManager
{
    const DEFAULT_VERSION = 'v1';
    const SUPPORTED_VERSIONS = ['v1', 'v2'];

    private $currentVersion;

    public function __construct()
    {
        $this->currentVersion = $this->detectVersion();
    }

    /**
     * Detect API version from request
     * 
     * Priority:
     * 1. URL path (/api/v2/...)
     * 2. Accept header (application/vnd.data2rest.v2+json)
     * 3. Default version
     * 
     * @return string Version identifier (e.g., 'v1')
     */
    public function detectVersion()
    {
        // Check URL path
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/api/(v\d+)/#', $path, $matches)) {
            $version = $matches[1];
            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        // Check Accept header
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (preg_match('/application\/vnd\.data2rest\.(v\d+)\+json/', $accept, $matches)) {
            $version = $matches[1];
            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        return self::DEFAULT_VERSION;
    }

    /**
     * Check if version is supported
     * 
     * @param string $version Version to check
     * @return bool
     */
    public function isVersionSupported($version)
    {
        return in_array($version, self::SUPPORTED_VERSIONS);
    }

    /**
     * Get current API version
     * 
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * Get version-specific configuration
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getVersionConfig($key, $default = null)
    {
        $config = $this->getVersionConfigurations();
        return $config[$this->currentVersion][$key] ?? $default;
    }

    /**
     * Get all version configurations
     * 
     * @return array
     */
    private function getVersionConfigurations()
    {
        return [
            'v1' => [
                'max_limit' => 100,
                'default_limit' => 50,
                'supports_bulk' => false,
                'supports_graphql' => false,
                'response_format' => 'standard',
                'deprecated' => false,
                'sunset_date' => null
            ],
            'v2' => [
                'max_limit' => 500,
                'default_limit' => 100,
                'supports_bulk' => true,
                'supports_graphql' => true,
                'response_format' => 'enhanced',
                'deprecated' => false,
                'sunset_date' => null,
                'new_features' => [
                    'bulk_operations',
                    'enhanced_filtering',
                    'field_transformations'
                ]
            ]
        ];
    }

    /**
     * Set version deprecation headers
     * 
     * @return void
     */
    public function setVersionHeaders()
    {
        header("X-API-Version: {$this->currentVersion}");

        $config = $this->getVersionConfigurations()[$this->currentVersion];

        if ($config['deprecated'] ?? false) {
            header('Deprecation: true');
            if ($config['sunset_date'] ?? null) {
                header("Sunset: {$config['sunset_date']}");
            }
        }

        // Link to newer version if available
        $currentIndex = array_search($this->currentVersion, self::SUPPORTED_VERSIONS);
        if ($currentIndex !== false && isset(self::SUPPORTED_VERSIONS[$currentIndex + 1])) {
            $newerVersion = self::SUPPORTED_VERSIONS[$currentIndex + 1];
            header("Link: </api/$newerVersion>; rel=\"successor-version\"");
        }
    }

    /**
     * Transform response based on version
     * 
     * @param array $data Response data
     * @return array Transformed data
     */
    public function transformResponse($data)
    {
        $format = $this->getVersionConfig('response_format', 'standard');

        switch ($format) {
            case 'enhanced':
                return $this->enhancedFormat($data);
            case 'standard':
            default:
                return $data;
        }
    }

    /**
     * Enhanced response format for v2
     * 
     * @param array $data Original data
     * @return array Enhanced data
     */
    private function enhancedFormat($data)
    {
        // Add version info and links
        if (isset($data['metadata'])) {
            $data['metadata']['api_version'] = $this->currentVersion;
            $data['metadata']['response_time'] = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        }

        return $data;
    }

    /**
     * Get deprecation notice for version
     * 
     * @param string $version Version to check
     * @return array|null Deprecation info
     */
    public function getDeprecationNotice($version = null)
    {
        $version = $version ?? $this->currentVersion;
        $config = $this->getVersionConfigurations()[$version] ?? null;

        if (!$config || !($config['deprecated'] ?? false)) {
            return null;
        }

        return [
            'deprecated' => true,
            'sunset_date' => $config['sunset_date'] ?? null,
            'message' => "API version $version is deprecated. Please migrate to a newer version.",
            'migration_guide' => "/docs/migration/$version"
        ];
    }
}
