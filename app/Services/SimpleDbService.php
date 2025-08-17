<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimpleDbService
{
    /**
     * Write operations - always use master
     */
    public static function writeQuery(callable $callback)
    {
        try {
            return DB::connection('mysql_master')->transaction($callback);
        } catch (\Exception $e) {
            Log::error('Master DB Write Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Read operations - use slave with fallback to master
     */
    public static function readQuery(callable $callback)
    {
        try {
            // Try slave first
            return DB::connection('mysql_slave_1')->transaction($callback);
        } catch (\Exception $e) {
            Log::warning('Slave DB Read Error, falling back to master: ' . $e->getMessage());
            
            try {
                // Fallback to master
                return DB::connection('mysql_master')->transaction($callback);
            } catch (\Exception $masterError) {
                Log::error('Master DB Read Error: ' . $masterError->getMessage());
                throw $masterError;
            }
        }
    }

    /**
     * Force read from master (for consistency after writes)
     */
    public static function readFromMaster(callable $callback)
    {
        try {
            return DB::connection('mysql_master')->transaction($callback);
        } catch (\Exception $e) {
            Log::error('Master DB Read Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test all connections
     */
    public static function testConnections()
    {
        $results = [];
        
        $connections = ['mysql', 'mysql_master', 'mysql_slave_1', 'mysql_slave_2'];
        
        foreach ($connections as $connection) {
            try {
                DB::connection($connection)->getPdo();
                $results[$connection] = 'Connected';
            } catch (\Exception $e) {
                $results[$connection] = 'Failed: ' . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Get database statistics
     */
    public static function getDatabaseStats($connection = 'mysql_master')
    {
        try {
            $stats = DB::connection($connection)->select("
                SELECT 
                    TABLE_NAME,
                    TABLE_ROWS,
                    DATA_LENGTH,
                    INDEX_LENGTH,
                    (DATA_LENGTH + INDEX_LENGTH) as TOTAL_SIZE
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TOTAL_SIZE DESC
            ");
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Database Stats Error: ' . $e->getMessage());
            return [];
        }
    }
}
