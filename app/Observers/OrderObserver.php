<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->updateReportCache($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Update cache when order status changes or amount changes
        if ($order->wasChanged(['status', 'total_amount', 'discount_amount', 'tax_amount'])) {
            $this->updateReportCache($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // Remove from cache when order is deleted
        DB::table('report_transaction_cache')
            ->where('id_order', $order->id_order)
            ->delete();
            
        $this->updateDailySummary($order->created_at);
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        $this->updateReportCache($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        DB::table('report_transaction_cache')
            ->where('id_order', $order->id_order)
            ->delete();
            
        $this->updateDailySummary($order->created_at);
    }
    
    /**
     * Update report transaction cache for this order
     */
    private function updateReportCache(Order $order): void
    {
        try {
            // Get customer name
            $customer = DB::table('customers')
                ->where('id_customer', $order->id_customer)
                ->first();
            
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
                    'order_date' => Carbon::parse($order->created_at)->format('Y-m-d'),
                    'order_time' => Carbon::parse($order->created_at)->format('H:i:s'),
                    'customer_name' => $customer->name ?? 'Guest',
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
            
            // Update daily summary
            $this->updateDailySummary($order->created_at);
            
        } catch (\Exception $e) {
            \Log::error('Failed to update report cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Update daily sales summary
     */
    private function updateDailySummary($date): void
    {
        try {
            $date = Carbon::parse($date);
            
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
            
        } catch (\Exception $e) {
            \Log::error('Failed to update daily summary: ' . $e->getMessage());
        }
    }
}

