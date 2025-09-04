<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckPurchaseItems extends Command
{
    protected $signature = 'debug:purchase-items';
    protected $description = 'Check purchase_items table';

    public function handle()
    {
        $this->info('=== CHECKING PURCHASE_ITEMS TABLE ===');
        
        // Check if table exists
        if (!Schema::hasTable('purchase_items')) {
            $this->error('❌ Table purchase_items does NOT exist!');
            return;
        }
        
        $this->info('✅ Table purchase_items exists');
        
        // Get columns
        $columns = Schema::getColumnListing('purchase_items');
        $this->info('Columns: ' . implode(', ', $columns));
        
        // Count records
        $count = DB::table('purchase_items')->count();
        $activeCount = DB::table('purchase_items')->whereNull('deleted_at')->count();
        $this->info("Total records: {$count}");
        $this->info("Active (non-deleted) records: {$activeCount}");
        
        if ($activeCount > 0) {
            // Sample active data
            $sample = DB::table('purchase_items')
                ->whereNull('deleted_at')
                ->limit(3)
                ->get()
                ->toArray();
            
            $this->info("\nSample ACTIVE data:");
            foreach ($sample as $item) {
                $this->line(json_encode($item, JSON_PRETTY_PRINT));
            }
        } else {
            $this->error("⚠️  NO ACTIVE PURCHASE ITEMS FOUND! All are soft deleted.");
        }
        
        // Check if suppliers table exists
        if (Schema::hasTable('suppliers')) {
            $suppliersCount = DB::table('suppliers')->count();
            $this->info("✅ Suppliers table exists with {$suppliersCount} records");
        } else {
            $this->error('❌ Suppliers table does NOT exist!');
        }
        
        // Check if items table exists  
        if (Schema::hasTable('items')) {
            $itemsCount = DB::table('items')->count();
            $this->info("✅ Items table exists with {$itemsCount} records");
        } else {
            $this->error('❌ Items table does NOT exist!');
        }
    }
}
