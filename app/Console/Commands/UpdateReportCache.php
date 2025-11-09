<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateReportCache extends Command
{
    protected $signature = 'report:update-cache {--date=} {--force}';
    protected $description = 'Update report cache tables for faster reporting';

    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $force = $this->option('force');
        
        $this->info("🚀 Updating report cache for date: {$date->format('Y-m-d')}");
        
        try {
            DB::beginTransaction();
            
            // 1. Update Transaction Cache
            $this->updateTransactionCache($date, $force);
            
            // 2. Update Daily Sales Summary
            $this->updateDailySalesSummary($date, $force);
            
            DB::commit();
            
            $this->info("✅ Report cache updated successfully!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Failed to update report cache: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    private function updateTransactionCache($date, $force)
    {
        $this->info("📊 Updating transaction cache...");
        
        // Delete existing cache for the date if force update
        if ($force) {
            DB::table('report_transaction_cache')
                ->whereDate('order_date', $date)
                ->delete();
        }
        
        // Get orders for the date
        $orders = DB::table('orders as o')
            ->leftJoin('customers as c', 'o.id_customer', '=', 'c.id_customer')
            ->whereDate('o.created_at', $date)
            ->select(
                'o.id_order',
                'o.order_number',
                DB::raw('DATE(o.created_at) as order_date'),
                DB::raw('TIME(o.created_at) as order_time'),
                DB::raw('COALESCE(c.name, "Guest") as customer_name'),
                'o.table_number',
                'o.order_type',
                'o.status',
                'o.total_amount'
            )
            ->get();
        
        foreach ($orders as $order) {
            // Get payment method
            $payment = DB::table('payments')
                ->where('id_order', $order->id_order)
                ->first();
            
            // Get items
            $items = DB::table('order_items as oi')
                ->leftJoin('products as p', 'oi.id_product', '=', 'p.id_product')
                ->where('oi.id_order', $order->id_order)
                ->select(
                    DB::raw('COALESCE(p.name, "Unknown") as name'),
                    'oi.quantity'
                )
                ->get();
            
            $itemsDetail = $items->map(function($item) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity
                ];
            })->toArray();
            
            // Insert or update cache
            DB::table('report_transaction_cache')->updateOrInsert(
                ['id_order' => $order->id_order],
                [
                    'order_number' => $order->order_number,
                    'order_date' => $order->order_date,
                    'order_time' => $order->order_time,
                    'customer_name' => $order->customer_name,
                    'table_number' => $order->table_number,
                    'order_type' => $order->order_type,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $payment->payment_method ?? 'cash',
                    'items_count' => count($items),
                    'items_detail' => json_encode($itemsDetail),
                    'updated_at' => now()
                ]
            );
        }
        
        $this->info("   ✓ Cached {$orders->count()} transactions");
    }
    
    private function updateDailySalesSummary($date, $force)
    {
        $this->info("📊 Updating daily sales summary...");
        
        $summary = DB::table('orders')
            ->whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = "completed" THEN discount_amount ELSE 0 END) as total_discount,
                SUM(CASE WHEN status = "completed" THEN tax_amount ELSE 0 END) as total_tax,
                AVG(CASE WHEN status = "completed" THEN total_amount ELSE NULL END) as average_order_value
            ')
            ->first();
        
        DB::table('report_sales_daily')->updateOrInsert(
            ['report_date' => $date->format('Y-m-d')],
            [
                'total_orders' => $summary->total_orders ?? 0,
                'completed_orders' => $summary->completed_orders ?? 0,
                'cancelled_orders' => $summary->cancelled_orders ?? 0,
                'total_revenue' => $summary->total_revenue ?? 0,
                'total_discount' => $summary->total_discount ?? 0,
                'total_tax' => $summary->total_tax ?? 0,
                'average_order_value' => $summary->average_order_value ?? 0,
                'updated_at' => now()
            ]
        );
        
        $this->info("   ✓ Daily summary updated");
    }
}

