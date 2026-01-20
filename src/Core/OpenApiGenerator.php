<?php

namespace App\Core;

use PDO;

/**
 * OpenAPI/Swagger Specification Generator
 * 
 * Auto-generates OpenAPI 3.0 specification from database schema.
 * Provides interactive API documentation via Swagger UI.
 * 
 * @package App\Core
 * @version 1.0.0
 */
class OpenApiGenerator
{
    private $db;
    private $baseUrl;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->baseUrl = Config::get('base_url') ?? 'http://localhost';
    }

    /**
     * Generate complete OpenAPI specification
     * 
     * @param int $databaseId Database ID to generate spec for
     * @return array OpenAPI specification
     */
    public function generateSpec($databaseId)
    {
        $database = $this->getDatabaseInfo($databaseId);
        $tables = $this->getTables($databaseId);

        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $database['name'] . ' API',
                'description' => 'Auto-generated API documentation for ' . $database['name'],
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'url' => $this->baseUrl . '/admin/api'
                ]
            ],
            'servers' => [
                [
                    'url' => $this->baseUrl . '/api/v1',
                    'description' => 'API v1 (Current)'
                ],
                [
                    'url' => $this->baseUrl . '/api/v2',
                    'description' => 'API v2 (Beta)'
                ]
            ],
            'security' => [
                ['ApiKeyAuth' => []]
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-KEY',
                        'description' => 'API key for authentication'
                    ]
                ],
                'schemas' => [],
                'parameters' => $this->getCommonParameters()
            ],
            'paths' => []
        ];

        // Generate paths and schemas for each table
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $schema = $this->generateSchemaForTable($databaseId, $tableName);

            $spec['components']['schemas'][$tableName] = $schema;
            $spec['paths'] = array_merge(
                $spec['paths'],
                $this->generatePathsForTable($databaseId, $tableName, $schema)
            );
        }

        return $spec;
    }

    /**
     * Get database information
     * 
     * @param int $databaseId
     * @return array
     */
    private function getDatabaseInfo($databaseId)
    {
        $stmt = $this->db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$databaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get tables for database
     * 
     * @param int $databaseId
     * @return array
     */
    private function getTables($databaseId)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT table_name as name 
            FROM fields_config 
            WHERE db_id = ? 
            ORDER BY table_name
        ");
        $stmt->execute([$databaseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate schema for a table
     * 
     * @param int $databaseId
     * @param string $tableName
     * @return array
     */
    private function generateSchemaForTable($databaseId, $tableName)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM fields_config 
            WHERE db_id = ? AND table_name = ?
            ORDER BY field_name
        ");
        $stmt->execute([$databaseId, $tableName]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $properties = [];
        $required = [];

        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $fieldType = $this->mapFieldType($field['field_type']);

            $properties[$fieldName] = [
                'type' => $fieldType['type']
            ];

            if (isset($fieldType['format'])) {
                $properties[$fieldName]['format'] = $fieldType['format'];
            }

            if ($field['is_foreign_key']) {
                $properties[$fieldName]['description'] = "Foreign key to {$field['related_table']}";
                $properties[$fieldName . '_label'] = [
                    'type' => 'string',
                    'description' => "Display value from {$field['related_table']}"
                ];
            }

            // Mark as required if not nullable (simplified logic)
            if (strpos(strtolower($field['field_type']), 'not null') !== false) {
                $required[] = $fieldName;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Map database field type to OpenAPI type
     * 
     * @param string $fieldType
     * @return array
     */
    private function mapFieldType($fieldType)
    {
        $type = strtolower($fieldType);

        if (strpos($type, 'int') !== false) {
            return ['type' => 'integer'];
        }
        if (strpos($type, 'float') !== false || strpos($type, 'decimal') !== false || strpos($type, 'double') !== false) {
            return ['type' => 'number'];
        }
        if (strpos($type, 'bool') !== false) {
            return ['type' => 'boolean'];
        }
        if (strpos($type, 'date') !== false && strpos($type, 'time') !== false) {
            return ['type' => 'string', 'format' => 'date-time'];
        }
        if (strpos($type, 'date') !== false) {
            return ['type' => 'string', 'format' => 'date'];
        }

        return ['type' => 'string'];
    }

    /**
     * Generate API paths for a table
     * 
     * @param int $databaseId
     * @param string $tableName
     * @param array $schema
     * @return array
     */
    private function generatePathsForTable($databaseId, $tableName, $schema)
    {
        $paths = [];
        $basePath = "/db/$databaseId/$tableName";

        // Collection endpoints
        $paths[$basePath] = [
            'get' => [
                'summary' => "List $tableName",
                'description' => "Retrieve a paginated list of $tableName records",
                'tags' => [$tableName],
                'parameters' => [
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Offset'],
                    ['$ref' => '#/components/parameters/Fields'],
                    ['$ref' => '#/components/parameters/Sort']
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'metadata' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total_records' => ['type' => 'integer'],
                                                'limit' => ['type' => 'integer'],
                                                'offset' => ['type' => 'integer'],
                                                'count' => ['type' => 'integer']
                                            ]
                                        ],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => "#/components/schemas/$tableName"]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    '429' => ['$ref' => '#/components/responses/RateLimitExceeded']
                ]
            ],
            'post' => [
                'summary' => "Create $tableName",
                'description' => "Create a new $tableName record",
                'tags' => [$tableName],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => "#/components/schemas/$tableName"]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Record created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/$tableName"]
                            ]
                        ]
                    ],
                    '400' => ['$ref' => '#/components/responses/BadRequest'],
                    '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    '403' => ['$ref' => '#/components/responses/PermissionDenied']
                ]
            ]
        ];

        // Single record endpoints
        $paths["$basePath/{id}"] = [
            'get' => [
                'summary' => "Get $tableName by ID",
                'description' => "Retrieve a single $tableName record",
                'tags' => [$tableName],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/$tableName"]
                            ]
                        ]
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound']
                ]
            ],
            'put' => [
                'summary' => "Update $tableName",
                'description' => "Update an existing $tableName record",
                'tags' => [$tableName],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => "#/components/schemas/$tableName"]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Record updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/$tableName"]
                            ]
                        ]
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound']
                ]
            ],
            'delete' => [
                'summary' => "Delete $tableName",
                'description' => "Delete a $tableName record",
                'tags' => [$tableName],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Record deleted successfully'
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound']
                ]
            ]
        ];

        return $paths;
    }

    /**
     * Get common parameters
     * 
     * @return array
     */
    private function getCommonParameters()
    {
        return [
            'Limit' => [
                'name' => 'limit',
                'in' => 'query',
                'description' => 'Maximum number of records to return',
                'schema' => [
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 500
                ]
            ],
            'Offset' => [
                'name' => 'offset',
                'in' => 'query',
                'description' => 'Number of records to skip',
                'schema' => [
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0
                ]
            ],
            'Fields' => [
                'name' => 'fields',
                'in' => 'query',
                'description' => 'Comma-separated list of fields to return',
                'schema' => [
                    'type' => 'string'
                ],
                'example' => 'id,name,email'
            ],
            'Sort' => [
                'name' => 'sort',
                'in' => 'query',
                'description' => 'Sort fields (prefix with - for DESC)',
                'schema' => [
                    'type' => 'string'
                ],
                'example' => '-created_at,name'
            ]
        ];
    }
}
