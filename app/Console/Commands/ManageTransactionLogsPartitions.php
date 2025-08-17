<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManageTransactionLogsPartitions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'partitions:manage-transaction-logs 
                            {action : The action to perform (add|drop|list|cleanup)}
                            {--months=3 : Number of months for add action}
                            {--retention=24 : Retention period in months for cleanup}';

    /**
     * The console command description.
     */
    protected $description = 'Manage transaction_logs table partitions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'add':
                $this->addPartitions();
                break;
            case 'drop':
                $this->dropOldPartitions();
                break;
            case 'list':
                $this->listPartitions();
                break;
            case 'cleanup':
                $this->cleanupOldPartitions();
                break;
            default:
                $this->error('Invalid action. Use: add, drop, list, or cleanup');
                return 1;
        }

        return 0;
    }

    /**
     * Add new partitions for future months
     */
    private function addPartitions()
    {
        $months = $this->option('months');
        $currentDate = Carbon::now();

        $this->info("Adding {$months} future partitions...");

        for ($i = 1; $i <= $months; $i++) {
            $futureDate = $currentDate->copy()->addMonths($i);
            $partitionValue = $futureDate->format('Ym');
            $nextMonthDate = $futureDate->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $partitionName = "p{$partitionValue}";

            try {
                // Check if partition already exists
                $exists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.PARTITIONS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'transaction_logs' 
                    AND PARTITION_NAME = ?
                ", [$partitionName]);

                if ($exists[0]->count > 0) {
                    $this->warn("Partition {$partitionName} already exists, skipping...");
                    continue;
                }

                // Add new partition before the MAXVALUE partition
                DB::statement("
                    ALTER TABLE transaction_logs 
                    REORGANIZE PARTITION p_future INTO (
                        PARTITION {$partitionName} VALUES LESS THAN ('{$nextMonthDate}'),
                        PARTITION p_future VALUES LESS THAN (MAXVALUE)
                    )
                ");

                $this->info("✓ Added partition: {$partitionName} for {$futureDate->format('Y-m')}");

            } catch (\Exception $e) {
                $this->error("Failed to add partition {$partitionName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Drop specific old partitions
     */
    private function dropOldPartitions()
    {
        $retention = $this->option('retention');
        $cutoffDate = Carbon::now()->subMonths($retention);
        $cutoffValue = $cutoffDate->format('Ym');

        $this->warn("This will drop partitions older than {$cutoffDate->format('Y-m')}");
        
        if (!$this->confirm('Are you sure you want to proceed?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Get old partitions
        $oldPartitions = DB::select("
            SELECT PARTITION_NAME, PARTITION_DESCRIPTION
            FROM information_schema.PARTITIONS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'transaction_logs' 
            AND PARTITION_NAME IS NOT NULL
            AND PARTITION_NAME != 'p_future'
            AND CAST(SUBSTRING(PARTITION_NAME, 2) AS UNSIGNED) < ?
            ORDER BY PARTITION_NAME
        ", [$cutoffValue]);

        if (empty($oldPartitions)) {
            $this->info('No old partitions found to drop.');
            return;
        }

        foreach ($oldPartitions as $partition) {
            try {
                DB::statement("ALTER TABLE transaction_logs DROP PARTITION {$partition->PARTITION_NAME}");
                $this->info("✓ Dropped partition: {$partition->PARTITION_NAME}");
            } catch (\Exception $e) {
                $this->error("Failed to drop partition {$partition->PARTITION_NAME}: " . $e->getMessage());
            }
        }
    }

    /**
     * List all existing partitions
     */
    private function listPartitions()
    {
        $partitions = DB::select("
            SELECT 
                PARTITION_NAME,
                PARTITION_DESCRIPTION,
                TABLE_ROWS,
                AVG_ROW_LENGTH,
                DATA_LENGTH,
                INDEX_LENGTH,
                CREATE_TIME
            FROM information_schema.PARTITIONS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'transaction_logs' 
            AND PARTITION_NAME IS NOT NULL
            ORDER BY PARTITION_NAME
        ");

        if (empty($partitions)) {
            $this->warn('No partitions found.');
            return;
        }

        $this->info('Transaction Logs Table Partitions:');
        $this->line('');

        $headers = ['Partition', 'Range', 'Rows', 'Data Size (MB)', 'Index Size (MB)', 'Created'];
        $rows = [];

        foreach ($partitions as $partition) {
            $dataSize = round($partition->DATA_LENGTH / 1024 / 1024, 2);
            $indexSize = round($partition->INDEX_LENGTH / 1024 / 1024, 2);
            
            $rows[] = [
                $partition->PARTITION_NAME,
                $partition->PARTITION_DESCRIPTION,
                number_format($partition->TABLE_ROWS),
                $dataSize,
                $indexSize,
                $partition->CREATE_TIME
            ];
        }

        $this->table($headers, $rows);

        // Show total statistics
        $totalRows = array_sum(array_column($partitions, 'TABLE_ROWS'));
        $totalDataSize = round(array_sum(array_column($partitions, 'DATA_LENGTH')) / 1024 / 1024, 2);
        $totalIndexSize = round(array_sum(array_column($partitions, 'INDEX_LENGTH')) / 1024 / 1024, 2);

        $this->line('');
        $this->info("Total Partitions: " . count($partitions));
        $this->info("Total Rows: " . number_format($totalRows));
        $this->info("Total Data Size: {$totalDataSize} MB");
        $this->info("Total Index Size: {$totalIndexSize} MB");
    }

    /**
     * Cleanup old partitions automatically based on retention period
     */
    private function cleanupOldPartitions()
    {
        $retention = $this->option('retention');
        $this->info("Cleaning up partitions older than {$retention} months...");
        
        $this->dropOldPartitions();
        $this->addPartitions();
        
        $this->info('Partition cleanup completed.');
    }
}
