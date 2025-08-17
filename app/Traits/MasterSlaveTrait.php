<?php

namespace App\Traits;

use App\Services\SimpleDbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait MasterSlaveTrait
{
    /**
     * Create data using master connection
     */
    protected function createWithMaster(array $data, string $table)
    {
        return SimpleDbService::writeQuery(function() use ($data, $table) {
            return DB::table($table)->insertGetId($data);
        });
    }

    /**
     * Update data using master connection
     */
    protected function updateWithMaster(int $id, array $data, string $table, string $primaryKey = 'id')
    {
        return SimpleDbService::writeQuery(function() use ($id, $data, $table, $primaryKey) {
            return DB::table($table)->where($primaryKey, $id)->update($data);
        });
    }

    /**
     * Delete data using master connection
     */
    protected function deleteWithMaster(int $id, string $table, string $primaryKey = 'id')
    {
        return SimpleDbService::writeQuery(function() use ($id, $table, $primaryKey) {
            return DB::table($table)->where($primaryKey, $id)->delete();
        });
    }

    /**
     * Read data using slave connection
     */
    protected function readWithSlave(string $table, array $conditions = [], array $columns = ['*'])
    {
        return SimpleDbService::readQuery(function() use ($table, $conditions, $columns) {
            $query = DB::table($table)->select($columns);
            
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
            
            return $query->get();
        });
    }

    /**
     * Read single record using slave connection
     */
    protected function findWithSlave(int $id, string $table, string $primaryKey = 'id', array $columns = ['*'])
    {
        return SimpleDbService::readQuery(function() use ($id, $table, $primaryKey, $columns) {
            return DB::table($table)->select($columns)->where($primaryKey, $id)->first();
        });
    }

    /**
     * Read from master for consistency after write
     */
    protected function readFromMaster(string $table, array $conditions = [], array $columns = ['*'])
    {
        return SimpleDbService::readFromMaster(function() use ($table, $conditions, $columns) {
            $query = DB::table($table)->select($columns);
            
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
            
            return $query->get();
        });
    }

    /**
     * Test database connections
     */
    protected function testDbConnections()
    {
        return SimpleDbService::testConnections();
    }

    /**
     * Get table statistics
     */
    protected function getTableStats()
    {
        return SimpleDbService::getDatabaseStats();
    }

    /**
     * Log master-slave operations for debugging
     */
    protected function logMasterSlaveOperation(string $operation, string $table, array $data = [])
    {
        if (config('app.debug')) {
            \Log::info("Master-Slave Operation: {$operation} on {$table}", [
                'data' => $data,
                'timestamp' => now(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
        }
    }
}
