<?php

namespace App\Console\Commands;

use App\Services\SimpleDbService;
use Illuminate\Console\Command;

class TestMasterSlaveCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'master-slave:test 
                            {--connections : Test all database connections}
                            {--create= : Create test logs (specify count)}
                            {--read : Read test logs}
                            {--stats : Show database statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Test master-slave database functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Master-Slave Database Testing Tool');
        $this->newLine();

        if ($this->option('connections')) {
            $this->testConnections();
        }

        if ($this->option('create')) {
            $count = (int) $this->option('create');
            $this->createTestLogs($count);
        }

        if ($this->option('read')) {
            $this->readTestLogs();
        }

        if ($this->option('stats')) {
            $this->showDatabaseStats();
        }

        // If no options, show help
        if (!$this->option('connections') && !$this->option('create') && !$this->option('read') && !$this->option('stats')) {
            $this->showHelp();
        }
    }

    /**
     * Test all database connections
     */
    protected function testConnections()
    {
        $this->info('🔌 Testing Database Connections...');
        $this->newLine();

        $connections = SimpleDbService::testConnections();

        foreach ($connections as $connection => $status) {
            if (str_contains($status, 'Connected')) {
                $this->line("✅ {$connection}: <fg=green>{$status}</>");
            } else {
                $this->line("❌ {$connection}: <fg=red>{$status}</>");
            }
        }

        $this->newLine();
    }

    /**
     * Create test logs
     */
    protected function createTestLogs(int $count)
    {
        $this->info("📝 Creating {$count} test logs...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $logsData = [];
        for ($i = 0; $i < $count; $i++) {
            $logsData[] = [
                'transaction_id' => uniqid('cmd_test_'),
                'method' => 'COMMAND',
                'endpoint' => '/console/master-slave-test',
                'url' => 'artisan master-slave:test',
                'user_id' => null,
                'username' => 'console',
                'user_role' => 'system',
                'request_headers' => json_encode([]),
                'request_payload' => json_encode(['test_batch' => $i + 1]),
                'request_ip' => '127.0.0.1',
                'user_agent' => 'Laravel Console',
                'response_status' => 200,
                'response_payload' => json_encode(['message' => 'Console test log ' . ($i + 1)]),
                'execution_time' => rand(10, 100) / 1000,
                'memory_usage' => memory_get_peak_usage(),
                'session_id' => 'console_session',
                'device_type' => 'server',
                'browser' => 'console',
                'platform' => PHP_OS,
                'transaction_type' => 'test',
                'module' => 'console_test',
                'action' => 'create_test_logs',
                'entity_type' => 'transaction_log',
                'is_successful' => true,
                'created_at' => now(),
                'created_date' => now()->toDateString()
            ];
            
            $progressBar->advance();
        }

        try {
            SimpleDbService::writeQuery(function() use ($logsData) {
                return \DB::connection('mysql_master')
                    ->table('transaction_logs')
                    ->insert($logsData);
            });

            $progressBar->finish();
            $this->newLine();
            $this->info("✅ Successfully created {$count} test logs in master database");
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("❌ Failed to create test logs: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Read test logs
     */
    protected function readTestLogs()
    {
        $this->info('📖 Reading test logs from slave...');

        try {
            $logs = SimpleDbService::readQuery(function() {
                return \DB::connection('mysql_slave_1')
                    ->table('transaction_logs')
                    ->where('module', 'console_test')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(['transaction_id', 'action', 'created_at', 'execution_time']);
            });

            if ($logs->isEmpty()) {
                $this->warn('📭 No test logs found');
                return;
            }

            $this->table(
                ['Transaction ID', 'Action', 'Created At', 'Execution Time'],
                $logs->map(function($log) {
                    return [
                        $log->transaction_id,
                        $log->action,
                        $log->created_at,
                        $log->execution_time . 's'
                    ];
                })->toArray()
            );

            $this->info("✅ Found {$logs->count()} test logs (read from slave)");
        } catch (\Exception $e) {
            $this->error("❌ Failed to read test logs: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Show database statistics
     */
    protected function showDatabaseStats()
    {
        $this->info('📊 Database Statistics...');

        try {
            $stats = SimpleDbService::getDatabaseStats('mysql_master');

            if (empty($stats)) {
                $this->warn('📭 No statistics available');
                return;
            }

            $tableStats = array_slice($stats, 0, 10); // Top 10 tables

            $this->table(
                ['Table Name', 'Rows', 'Data Size', 'Index Size', 'Total Size'],
                array_map(function($stat) {
                    return [
                        $stat->TABLE_NAME,
                        number_format($stat->TABLE_ROWS ?? 0),
                        $this->formatBytes($stat->DATA_LENGTH ?? 0),
                        $this->formatBytes($stat->INDEX_LENGTH ?? 0),
                        $this->formatBytes($stat->TOTAL_SIZE ?? 0)
                    ];
                }, $tableStats)
            );

            $this->info("✅ Statistics retrieved from master database");
        } catch (\Exception $e) {
            $this->error("❌ Failed to get database stats: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Show help information
     */
    protected function showHelp()
    {
        $this->info('🔧 Master-Slave Test Commands:');
        $this->newLine();
        
        $this->line('Test database connections:');
        $this->line('  <fg=yellow>php artisan master-slave:test --connections</>');
        $this->newLine();
        
        $this->line('Create test logs:');
        $this->line('  <fg=yellow>php artisan master-slave:test --create=10</>');
        $this->newLine();
        
        $this->line('Read test logs:');
        $this->line('  <fg=yellow>php artisan master-slave:test --read</>');
        $this->newLine();
        
        $this->line('Show database statistics:');
        $this->line('  <fg=yellow>php artisan master-slave:test --stats</>');
        $this->newLine();
        
        $this->line('Run all tests:');
        $this->line('  <fg=yellow>php artisan master-slave:test --connections --create=5 --read --stats</>');
        $this->newLine();
    }
}
