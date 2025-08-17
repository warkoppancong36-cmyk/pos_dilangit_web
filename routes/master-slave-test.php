<?php

use App\Http\Controllers\Api\MasterSlaveTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Master-Slave Testing Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/master-slave-test')->group(function () {
    
    // Connection testing
    Route::get('/test-connections', [MasterSlaveTestController::class, 'testConnections']);
    Route::get('/database-stats', [MasterSlaveTestController::class, 'getDatabaseStats']);
    
    // Transaction Log Testing
    Route::post('/create-log', [MasterSlaveTestController::class, 'createTestLog']);
    Route::post('/bulk-create-logs', [MasterSlaveTestController::class, 'bulkCreateTestLogs']);
    
    // Read operations (from slave)
    Route::get('/logs', [MasterSlaveTestController::class, 'getLogs']);
    Route::get('/analytics', [MasterSlaveTestController::class, 'getAnalytics']);
    Route::get('/error-logs', [MasterSlaveTestController::class, 'getErrorLogs']);
    
    // Read from master (for consistency)
    Route::get('/consistent-logs', [MasterSlaveTestController::class, 'getConsistentLogs']);
});
