<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Models\TransactionLog;
use Carbon\Carbon;

class CleanupTransactionLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup-transactions {--days= : Number of days to keep logs} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old transaction logs based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $retentionDays = $this->option('days') ?: Config::get('transaction_log.retention_days', 90);
        $isDryRun = $this->option('dry-run');
        
        if ($retentionDays <= 0) {
            $this->error('Retention days must be greater than 0');
            return 1;
        }
        
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        $this->info("Cleaning up transaction logs older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})");
        
        // Count logs to be deleted
        $logsToDelete = TransactionLog::where('created_at', '<', $cutoffDate)->count();
        
        if ($logsToDelete === 0) {
            $this->info('No logs found to clean up.');
            return 0;
        }
        
        $this->info("Found {$logsToDelete} logs to clean up.");
        
        if ($isDryRun) {
            $this->warn('DRY RUN: No logs were actually deleted.');
            
            // Show some statistics
            $this->table(['Module', 'Count'], 
                TransactionLog::where('created_at', '<', $cutoffDate)
                    ->selectRaw('module, COUNT(*) as count')
                    ->groupBy('module')
                    ->get()
                    ->map(function ($log) {
                        return [$log->module ?: 'Unknown', $log->count];
                    })
                    ->toArray()
            );
            
            return 0;
        }
        
        if (!$this->confirm("Are you sure you want to delete {$logsToDelete} transaction logs?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Perform cleanup in batches to avoid memory issues
        $batchSize = 1000;
        $totalDeleted = 0;
        
        $this->output->progressStart($logsToDelete);
        
        do {
            $deleted = TransactionLog::where('created_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();
            
            $totalDeleted += $deleted;
            $this->output->progressAdvance($deleted);
            
            // Small delay to prevent overwhelming the database
            usleep(10000); // 10ms
            
        } while ($deleted > 0);
        
        $this->output->progressFinish();
        
        $this->info("Successfully deleted {$totalDeleted} transaction logs.");
        
        // Show remaining log statistics
        $remainingLogs = TransactionLog::count();
        $this->info("Remaining transaction logs: {$remainingLogs}");
        
        if ($remainingLogs > 0) {
            $oldestLog = TransactionLog::orderBy('created_at')->first();
            $newestLog = TransactionLog::orderBy('created_at', 'desc')->first();
            
            $this->info("Date range: {$oldestLog->created_at->format('Y-m-d')} to {$newestLog->created_at->format('Y-m-d')}");
        }
        
        return 0;
    }
}
