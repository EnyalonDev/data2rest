<?php

namespace App\Core;

/**
 * Advanced Query Filter Builder
 * 
 * Provides advanced filtering capabilities for API queries including:
 * - Comparison operators (gt, gte, lt, lte, eq, ne)
 * - IN and NOT IN operators
 * - BETWEEN operator
 * - NULL checks
 * - LIKE patterns
 * 
 * Usage Examples:
 * ?age[gt]=18
 * ?price[lte]=100
 * ?status[in]=active,pending
 * ?created_at[between]=2024-01-01,2024-12-31
 * ?name[not]=null
 * 
 * @package App\Core
 * @version 1.0.0
 */
class QueryFilterBuilder
{
    /**
     * Supported operators and their SQL equivalents
     */
    private const OPERATORS = [
        'eq' => '=',
        'ne' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE',
        'not' => 'IS NOT',
        'in' => 'IN',
        'between' => 'BETWEEN'
    ];

    /**
     * Parse query parameters and build WHERE clauses
     * 
     * @param array $params Query parameters
     * @param array $validColumns Valid column names
     * @param object $adapter Database adapter for quoting
     * @return array ['where' => array of SQL conditions, 'values' => array of bound values]
     */
    public static function buildFilters($params, $validColumns, $adapter)
    {
        $where = [];
        $values = [];

        foreach ($params as $key => $value) {
            // Skip non-filter params
            if (in_array($key, ['limit', 'offset', 'fields', 'sort', 'api_key'])) {
                continue;
            }

            // Parse field[operator] syntax
            if (preg_match('/^([a-zA-Z0-9_]+)\[([a-zA-Z]+)\]$/', $key, $matches)) {
                $field = $matches[1];
                $operator = $matches[2];

                if (!in_array($field, $validColumns)) {
                    continue; // Skip invalid columns
                }

                $result = self::buildCondition($field, $operator, $value, $adapter);
                if ($result) {
                    $where[] = $result['condition'];
                    $values = array_merge($values, $result['values']);
                }
            } else {
                // Simple equality filter
                if (in_array($key, $validColumns)) {
                    $qKey = $adapter->quoteName($key);

                    // Check for LIKE pattern
                    if (is_string($value) && strpos($value, '%') !== false) {
                        $where[] = "t.$qKey LIKE ?";
                        $values[] = $value;
                    } else {
                        $where[] = "t.$qKey = ?";
                        $values[] = $value;
                    }
                }
            }
        }

        return ['where' => $where, 'values' => $values];
    }

    /**
     * Build a single filter condition
     * 
     * @param string $field Field name
     * @param string $operator Operator
     * @param mixed $value Value(s)
     * @param object $adapter Database adapter
     * @return array|null ['condition' => SQL string, 'values' => array]
     */
    private static function buildCondition($field, $operator, $value, $adapter)
    {
        if (!isset(self::OPERATORS[$operator])) {
            return null;
        }

        $qField = $adapter->quoteName($field);
        $sqlOp = self::OPERATORS[$operator];

        switch ($operator) {
            case 'in':
                // Parse comma-separated values
                $values = array_map('trim', explode(',', $value));
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                return [
                    'condition' => "t.$qField IN ($placeholders)",
                    'values' => $values
                ];

            case 'between':
                // Parse comma-separated range
                $range = array_map('trim', explode(',', $value));
                if (count($range) !== 2) {
                    return null;
                }
                return [
                    'condition' => "t.$qField BETWEEN ? AND ?",
                    'values' => $range
                ];

            case 'not':
                // Handle IS NULL / IS NOT NULL
                if (strtolower($value) === 'null') {
                    return [
                        'condition' => "t.$qField IS NOT NULL",
                        'values' => []
                    ];
                }
                return [
                    'condition' => "t.$qField != ?",
                    'values' => [$value]
                ];

            case 'like':
                // Ensure value has wildcards
                if (strpos($value, '%') === false) {
                    $value = "%$value%";
                }
                return [
                    'condition' => "t.$qField LIKE ?",
                    'values' => [$value]
                ];

            default:
                // Standard comparison operators
                return [
                    'condition' => "t.$qField $sqlOp ?",
                    'values' => [$value]
                ];
        }
    }

    /**
     * Parse sort parameter
     * 
     * Supports: ?sort=name,-created_at (ASC by name, DESC by created_at)
     * 
     * @param string|null $sortParam Sort parameter
     * @param array $validColumns Valid column names
     * @param object $adapter Database adapter
     * @return string SQL ORDER BY clause
     */
    public static function buildSort($sortParam, $validColumns, $adapter)
    {
        if (!$sortParam) {
            return '';
        }

        $sorts = explode(',', $sortParam);
        $orderBy = [];

        foreach ($sorts as $sort) {
            $sort = trim($sort);
            $direction = 'ASC';

            // Check for DESC prefix (-)
            if (strpos($sort, '-') === 0) {
                $direction = 'DESC';
                $sort = substr($sort, 1);
            }

            // Validate column
            if (in_array($sort, $validColumns)) {
                $qSort = $adapter->quoteName($sort);
                $orderBy[] = "t.$qSort $direction";
            }
        }

        return !empty($orderBy) ? ' ORDER BY ' . implode(', ', $orderBy) : '';
    }
}
