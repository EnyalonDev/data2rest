<?php

namespace App\Core;

use PDO;

/**
 * Bulk Operations Manager
 * 
 * Handles batch operations for create, update, and delete.
 * Provides transaction support and detailed error reporting.
 * 
 * Features:
 * - Batch create/update/delete
 * - Transaction support
 * - Partial success handling
 * - Detailed error reporting per operation
 * - Performance optimization
 * 
 * @package App\Core
 * @version 1.0.0
 */
class BulkOperationsManager
{
    private $db;
    private $maxBatchSize = 100;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Execute bulk operations
     * 
     * @param array $operations Array of operations
     * @param string $table Table name
     * @param object $adapter Database adapter
     * @return array Results with success/error details
     */
    public function execute($operations, $table, $adapter)
    {
        if (empty($operations)) {
            return [
                'success' => false,
                'message' => 'No operations provided',
                'results' => []
            ];
        }

        if (count($operations) > $this->maxBatchSize) {
            return [
                'success' => false,
                'message' => "Batch size exceeds maximum of {$this->maxBatchSize}",
                'results' => []
            ];
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        // Start transaction
        $this->db->beginTransaction();

        try {
            foreach ($operations as $index => $operation) {
                $result = $this->executeOperation($operation, $table, $adapter, $index);
                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            // Commit if all succeeded or partial success is allowed
            $this->db->commit();

            return [
                'success' => $errorCount === 0,
                'message' => "Processed {$successCount} operations successfully, {$errorCount} failed",
                'summary' => [
                    'total' => count($operations),
                    'success' => $successCount,
                    'failed' => $errorCount
                ],
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
                'summary' => [
                    'total' => count($operations),
                    'success' => 0,
                    'failed' => count($operations)
                ],
                'results' => $results
            ];
        }
    }

    /**
     * Execute single operation
     * 
     * @param array $operation Operation details
     * @param string $table Table name
     * @param object $adapter Database adapter
     * @param int $index Operation index
     * @return array Operation result
     */
    private function executeOperation($operation, $table, $adapter, $index)
    {
        $method = strtolower($operation['method'] ?? 'create');
        $data = $operation['data'] ?? [];
        $id = $operation['id'] ?? null;

        try {
            switch ($method) {
                case 'create':
                case 'post':
                    return $this->bulkCreate($table, $data, $adapter, $index);

                case 'update':
                case 'put':
                case 'patch':
                    if (!$id) {
                        throw new \Exception('ID required for update operation');
                    }
                    return $this->bulkUpdate($table, $id, $data, $adapter, $index);

                case 'delete':
                    if (!$id) {
                        throw new \Exception('ID required for delete operation');
                    }
                    return $this->bulkDelete($table, $id, $adapter, $index);

                default:
                    throw new \Exception("Unsupported method: $method");
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'index' => $index,
                'method' => $method,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk create operation
     * 
     * @param string $table Table name
     * @param array $data Record data
     * @param object $adapter Database adapter
     * @param int $index Operation index
     * @return array
     */
    private function bulkCreate($table, $data, $adapter, $index)
    {
        if (empty($data)) {
            throw new \Exception('No data provided for create');
        }

        $qTable = $adapter->quoteName($table);
        $columns = array_keys($data);
        $qColumns = array_map([$adapter, 'quoteName'], $columns);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO $qTable (" . implode(', ', $qColumns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        $insertedId = $this->db->lastInsertId();

        return [
            'success' => true,
            'index' => $index,
            'method' => 'create',
            'id' => $insertedId,
            'data' => array_merge(['id' => $insertedId], $data)
        ];
    }

    /**
     * Bulk update operation
     * 
     * @param string $table Table name
     * @param int $id Record ID
     * @param array $data Update data
     * @param object $adapter Database adapter
     * @param int $index Operation index
     * @return array
     */
    private function bulkUpdate($table, $id, $data, $adapter, $index)
    {
        if (empty($data)) {
            throw new \Exception('No data provided for update');
        }

        $qTable = $adapter->quoteName($table);
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $qColumn = $adapter->quoteName($column);
            $sets[] = "$qColumn = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = "UPDATE $qTable SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Record with ID $id not found");
        }

        return [
            'success' => true,
            'index' => $index,
            'method' => 'update',
            'id' => $id,
            'updated_fields' => array_keys($data)
        ];
    }

    /**
     * Bulk delete operation
     * 
     * @param string $table Table name
     * @param int $id Record ID
     * @param object $adapter Database adapter
     * @param int $index Operation index
     * @return array
     */
    private function bulkDelete($table, $id, $adapter, $index)
    {
        $qTable = $adapter->quoteName($table);
        $sql = "DELETE FROM $qTable WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Record with ID $id not found");
        }

        return [
            'success' => true,
            'index' => $index,
            'method' => 'delete',
            'id' => $id
        ];
    }

    /**
     * Set maximum batch size
     * 
     * @param int $size Maximum size
     * @return void
     */
    public function setMaxBatchSize($size)
    {
        $this->maxBatchSize = max(1, min(1000, $size));
    }

    /**
     * Get maximum batch size
     * 
     * @return int
     */
    public function getMaxBatchSize()
    {
        return $this->maxBatchSize;
    }
}
