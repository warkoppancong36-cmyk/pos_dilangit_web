<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\OrderItem;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get sales report
     */
    public function salesReport(Request $request): JsonResponse
    {
        try {
            $startDate = $this->getStartDate($request);
            $endDate = $this->getEndDate($request);
            
            // Quick test to see if any orders exist in date range
            $orderCount = Order::whereBetween('order_date', [$startDate, $endDate])->count();

            // Sales summary
            $salesSummary = Order::select(
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('CASE WHEN COUNT(*) > 0 THEN SUM(total_amount)/COUNT(*) ELSE 0 END as average_order'),
                    DB::raw('SUM(order_items.quantity) as total_items'),
                    DB::raw('SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                    DB::raw('SUM(CASE WHEN orders.status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders')
                )
                ->leftJoin('order_items', 'orders.id_order', '=', 'order_items.id_order')
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->first();

                        // Daily sales trend
            $dailySales = Order::select(
                    DB::raw('DATE(order_date) as date'),
                    DB::raw('SUM(total_amount) as total')
                )
                ->whereBetween('order_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->groupBy(DB::raw('DATE(order_date)'))
                ->orderBy('date')
                ->get();

            // Sales by order type
            $salesByType = Order::select(
                    'order_type',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_revenue')
                )
                ->where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->groupBy('order_type')
                ->get();

            // Top selling products
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.id_order', '=', 'orders.id_order')
                ->join('products', 'order_items.id_product', '=', 'products.id_product')
                ->select(
                    'products.name',
                    'products.id_product as id',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.total_price) as total_revenue'),
                    DB::raw('AVG(order_items.unit_price) as avg_price')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->groupBy('products.id_product', 'products.name')
                ->orderBy('total_revenue', 'desc')
                ->limit(20)
                ->get();

            // Sales by customer (if customer data exists)
            $salesByCustomer = Order::join('customers', 'orders.id_customer', '=', 'customers.id_customer')
                ->select(
                    'customers.name',
                    'customers.id_customer as id',
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(orders.total_amount) as total_spent'),
                    DB::raw('AVG(orders.total_amount) as avg_order_value'),
                    DB::raw('MIN(orders.order_date) as first_order'),
                    DB::raw('MAX(orders.order_date) as last_order')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->groupBy('customers.id_customer', 'customers.name')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            // Sales by hour (peak hours analysis)
            $salesByHour = Order::select(
                    DB::raw('HOUR(order_date) as hour'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as avg_order_value')
                )
                ->where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->groupBy(DB::raw('HOUR(order_date)'))
                ->orderBy('hour')
                ->get();

            // Sales by day of week
            $salesByDayOfWeek = Order::select(
                    DB::raw('DAYOFWEEK(order_date) as day_of_week'),
                    DB::raw('CASE DAYOFWEEK(order_date)
                        WHEN 1 THEN "Minggu"
                        WHEN 2 THEN "Senin" 
                        WHEN 3 THEN "Selasa"
                        WHEN 4 THEN "Rabu"
                        WHEN 5 THEN "Kamis"
                        WHEN 6 THEN "Jumat"
                        WHEN 7 THEN "Sabtu"
                    END as day_name'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as avg_order_value')
                )
                ->where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->groupBy(DB::raw('DAYOFWEEK(order_date)'), DB::raw('CASE DAYOFWEEK(order_date)
                        WHEN 1 THEN "Minggu"
                        WHEN 2 THEN "Senin" 
                        WHEN 3 THEN "Selasa"
                        WHEN 4 THEN "Rabu"
                        WHEN 5 THEN "Kamis"
                        WHEN 6 THEN "Jumat"
                        WHEN 7 THEN "Sabtu"
                    END'))
                ->orderBy(DB::raw('DAYOFWEEK(order_date)'))
                ->get();

            // Payment method analysis - Join dengan table payments
            $totalOrdersForPercentage = Order::where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->count();

            $paymentMethods = DB::table('payments')
                ->join('orders', 'payments.id_order', '=', 'orders.id_order')
                ->select(
                    'payments.payment_method',
                    DB::raw('COUNT(payments.id_payment) as order_count'),
                    DB::raw('SUM(payments.amount) as total_revenue'),
                    DB::raw('AVG(payments.amount) as avg_order_value'),
                    DB::raw('ROUND((COUNT(payments.id_payment) * 100.0 / ' . $totalOrdersForPercentage . '), 2) as percentage')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->where('payments.status', 'paid')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->whereNull('orders.deleted_at')
                ->groupBy('payments.payment_method')
                ->orderBy('total_revenue', 'desc')
                ->get();

            // Category performance
            $categoryPerformance = DB::table('order_items')
                ->join('orders', 'order_items.id_order', '=', 'orders.id_order')
                ->join('products', 'order_items.id_product', '=', 'products.id_product')
                ->join('categories', 'products.category_id', '=', 'categories.id_category')
                ->select(
                    'categories.name as category_name',
                    'categories.id_category as id',
                    DB::raw('COUNT(DISTINCT order_items.id_order) as orders_with_category'),
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.total_price) as total_revenue'),
                    DB::raw('AVG(order_items.unit_price) as avg_price')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->whereNull('orders.deleted_at')
                ->groupBy('categories.id_category', 'categories.name')
                ->orderBy('total_revenue', 'desc')
                ->get();

            // Customer behavior analysis
            $customerBehavior = [
                'new_customers' => Customer::whereBetween('created_at', [$startDate, $endDate])->count(),
                'returning_customers' => Order::select('id_customer')
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('order_date', [$startDate, $endDate])
                    ->groupBy('id_customer')
                    ->havingRaw('COUNT(*) > 1')
                    ->get()->count(),
                'one_time_customers' => Order::select('id_customer')
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('order_date', [$startDate, $endDate])
                    ->groupBy('id_customer')
                    ->havingRaw('COUNT(*) = 1')
                    ->get()->count()
            ];

            // Growth comparison (vs previous period)
            $periodLength = Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)) + 1;
            $previousStartDate = Carbon::parse($startDate)->subDays($periodLength)->format('Y-m-d');
            $previousEndDate = Carbon::parse($startDate)->subDay()->format('Y-m-d');

            $previousPeriodSales = Order::where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$previousStartDate, $previousEndDate])
                ->sum('total_amount');

            $currentPeriodSales = $salesSummary->total_revenue ?: 0;
            $growthPercentage = $previousPeriodSales > 0 
                ? round((($currentPeriodSales - $previousPeriodSales) / $previousPeriodSales) * 100, 2)
                : 0;

            return $this->successResponse([
                'summary' => [
                    'total_orders' => $salesSummary->total_orders ?: 0,
                    'total_revenue' => $this->formatRupiah($salesSummary->total_revenue),
                    'average_order' => $this->formatRupiah($salesSummary->average_order),
                    'total_items' => $salesSummary->total_items ?: 0,
                    'completed_orders' => $salesSummary->completed_orders ?: 0,
                    'cancelled_orders' => $salesSummary->cancelled_orders ?: 0,
                    'growth_percentage' => $growthPercentage,
                    'previous_period_revenue' => $this->formatRupiah($previousPeriodSales)
                ],
                'daily_sales' => $dailySales->map(function($item) {
                    return [
                        'date' => $item->date,
                        'total' => $this->formatRupiah($item->total)
                    ];
                }),
                'sales_by_type' => $salesByType->map(function($item) {
                    return [
                        'type' => $item->order_type,
                        'order_count' => $item->order_count ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue)
                    ];
                }),
                'peak_hours' => $salesByHour->map(function($item) {
                    return [
                        'hour' => $item->hour,
                        'hour_display' => sprintf('%02d:00', $item->hour),
                        'order_count' => $item->order_count ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue),
                        'avg_order' => $this->formatRupiah($item->avg_order_value)
                    ];
                }),
                'sales_by_day' => $salesByDayOfWeek->map(function($item) {
                    return [
                        'day_name' => $item->day_name,
                        'order_count' => $item->order_count ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue),
                        'avg_order' => $this->formatRupiah($item->avg_order_value)
                    ];
                }),
                'payment_methods' => $paymentMethods->map(function($item) {
                    return [
                        'method' => $item->payment_method,
                        'order_count' => $item->order_count ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue),
                        'avg_order' => $this->formatRupiah($item->avg_order_value),
                        'percentage' => $item->percentage ?: 0
                    ];
                }),
                'category_performance' => $categoryPerformance->map(function($item) {
                    return [
                        'category' => $item->category_name,
                        'orders_count' => $item->orders_with_category ?: 0,
                        'quantity_sold' => $item->total_quantity ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue),
                        'avg_price' => $this->formatRupiah($item->avg_price)
                    ];
                }),
                'customer_behavior' => [
                    'new_customers' => $customerBehavior['new_customers'],
                    'returning_customers' => $customerBehavior['returning_customers'],
                    'one_time_customers' => $customerBehavior['one_time_customers'],
                    'retention_rate' => $customerBehavior['returning_customers'] + $customerBehavior['one_time_customers'] > 0 
                        ? round(($customerBehavior['returning_customers'] / ($customerBehavior['returning_customers'] + $customerBehavior['one_time_customers'])) * 100, 2)
                        : 0
                ],
                'top_products' => $topProducts->map(function($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->total_sold ?: 0,
                        'revenue' => $this->formatRupiah($item->total_revenue),
                        'avg_price' => $this->formatRupiah($item->avg_price)
                    ];
                }),
                'top_customers' => $salesByCustomer->map(function($item) {
                    return [
                        'name' => $item->name,
                        'orders' => $item->total_orders ?: 0,
                        'revenue' => $this->formatRupiah($item->total_spent),
                        'avg_order' => $this->formatRupiah($item->avg_order_value),
                        'first_order' => $item->first_order,
                        'last_order' => $item->last_order,
                        'customer_lifetime' => Carbon::parse($item->first_order)->diffInDays(Carbon::parse($item->last_order))
                    ];
                })
            ], 'Enhanced sales report with business analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve sales report: ' . $e->getMessage());
        }
    }

    /**
     * Enhanced Purchase Report with Business Intelligence
     */
    public function purchaseReport(Request $request): JsonResponse
    {
        try {
            $startDate = $this->getStartDate($request);
            $endDate = $this->getEndDate($request);

            // Enhanced Purchase Summary with Cost Analysis
            $purchaseSummary = Purchase::select(
                    DB::raw('COUNT(*) as total_purchases'),
                    DB::raw('COALESCE(SUM(total_amount), 0) as total_amount'),
                    DB::raw('COALESCE(AVG(total_amount), 0) as avg_purchase_value'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_purchases'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_purchases'),
                    DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_purchases'),
                    DB::raw('COALESCE(SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END), 0) as completed_amount'),
                    DB::raw('COALESCE(SUM(CASE WHEN status = "pending" THEN total_amount ELSE 0 END), 0) as pending_amount')
                )
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->first();

            // Purchase vs Sales Cost Analysis
            $totalSalesRevenue = Order::where('status', '!=', 'cancelled')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->sum('total_amount');
            
            $totalPurchaseCost = $purchaseSummary->completed_amount ?? 0;
            $totalSalesRevenue = $totalSalesRevenue ?? 0;
            
            // Safe calculation untuk cost of sales ratio
            $costOfSalesRatio = 0;
            if ($totalSalesRevenue > 0 && is_numeric($totalPurchaseCost) && is_numeric($totalSalesRevenue)) {
                $ratio = ($totalPurchaseCost / $totalSalesRevenue) * 100;
                $costOfSalesRatio = is_finite($ratio) ? round($ratio, 2) : 0;
            }

            // Stock Movement Analysis - Items that need frequent reordering
            $frequentlyOrderedItems = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id_purchase')
                ->join('items', 'purchase_items.item_id', '=', 'items.id_item')
                ->select(
                    'items.name',
                    'items.id_item as id',
                    DB::raw('COUNT(purchase_items.id_purchase_item) as order_frequency'),
                    DB::raw('SUM(purchase_items.quantity_ordered) as total_quantity'),
                    DB::raw('AVG(purchase_items.quantity_ordered) as avg_order_quantity'),
                    DB::raw('SUM(purchase_items.total_cost) as total_cost'),
                    DB::raw('AVG(purchase_items.unit_cost) as avg_unit_cost'),
                    DB::raw('MAX(purchases.purchase_date) as last_ordered'),
                    DB::raw('MIN(purchases.purchase_date) as first_ordered')
                )
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('items.id_item', 'items.name')
                ->havingRaw('COUNT(purchase_items.id_purchase_item) > 1')
                ->orderBy('order_frequency', 'desc')
                ->limit(20)
                ->get();

            // Cost Trend Analysis - Price changes over time
            $costTrendAnalysis = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id_purchase')
                ->join('items', 'purchase_items.item_id', '=', 'items.id_item')
                ->select(
                    'items.name',
                    'items.id_item as id',
                    DB::raw('MIN(purchase_items.unit_cost) as lowest_cost'),
                    DB::raw('MAX(purchase_items.unit_cost) as highest_cost'),
                    DB::raw('AVG(purchase_items.unit_cost) as avg_cost'),
                    DB::raw('(MAX(purchase_items.unit_cost) - MIN(purchase_items.unit_cost)) as cost_variance'),
                    DB::raw('ROUND(((MAX(purchase_items.unit_cost) - MIN(purchase_items.unit_cost)) / MIN(purchase_items.unit_cost)) * 100, 2) as price_volatility_percent')
                )
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('items.id_item', 'items.name')
                ->havingRaw('COUNT(purchase_items.id_purchase_item) > 1')
                ->orderBy('price_volatility_percent', 'desc')
                ->get();

            // Supplier Performance Analysis
            $supplierPerformance = DB::table('purchases')
                ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id_supplier')
                ->select(
                    'suppliers.name',
                    'suppliers.id_supplier as id',
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(purchases.total_amount) as total_spent'),
                    DB::raw('AVG(purchases.total_amount) as avg_order_value'),
                    DB::raw('SUM(CASE WHEN purchases.status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                    DB::raw('SUM(CASE WHEN purchases.status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders'),
                    DB::raw('ROUND((SUM(CASE WHEN purchases.status = "completed" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate'),
                    DB::raw('AVG(DATEDIFF(purchases.updated_at, purchases.purchase_date)) as avg_delivery_days')
                )
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('suppliers.id_supplier', 'suppliers.name')
                ->orderBy('total_spent', 'desc')
                ->get();

            // Monthly Purchase Pattern Analysis
            $monthlyPattern = Purchase::select(
                    DB::raw('MONTH(purchase_date) as month'),
                    DB::raw('YEAR(purchase_date) as year'),
                    DB::raw('COUNT(*) as purchase_count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('AVG(total_amount) as avg_purchase')
                )
                ->where('status', '!=', 'cancelled')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy(DB::raw('YEAR(purchase_date)'), DB::raw('MONTH(purchase_date)'))
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            // Critical Stock Analysis - Items running low vs purchase patterns
            $criticalStockAnalysis = DB::table('inventory')
                ->join('items', 'inventory.id_item', '=', 'items.id_item')
                ->leftJoin(
                    DB::raw('(SELECT item_id, MAX(purchase_date) as last_purchase_date, AVG(quantity_ordered) as avg_purchase_qty 
                             FROM purchase_items 
                             JOIN purchases ON purchase_items.purchase_id = purchases.id_purchase 
                             WHERE purchases.status != "cancelled" 
                             GROUP BY item_id) as last_purchases'),
                    'items.id_item', '=', 'last_purchases.item_id'
                )
                ->select(
                    'items.name',
                    'items.id_item as id',
                    'inventory.current_stock',
                    'inventory.reorder_level',
                    'last_purchases.last_purchase_date',
                    'last_purchases.avg_purchase_qty',
                    DB::raw('CASE 
                        WHEN inventory.current_stock <= inventory.reorder_level THEN "Critical"
                        WHEN inventory.current_stock <= (inventory.reorder_level * 1.5) THEN "Low"
                        ELSE "Normal"
                    END as stock_status'),
                    DB::raw('DATEDIFF(NOW(), last_purchases.last_purchase_date) as days_since_last_purchase')
                )
                ->whereRaw('inventory.current_stock <= (inventory.reorder_level * 2)')
                ->orderBy('inventory.current_stock', 'asc')
                ->get();

            // Purchase Efficiency Metrics
            $totalPurchaseCount = Purchase::whereBetween('purchase_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->count();
                
            $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
            
            $avgItemsQuery = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id_purchase')
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('purchases.id_purchase')
                ->selectRaw('COUNT(*) as items_count')
                ->get();
                
            $distinctItemTypes = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id_purchase')
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->distinct('item_id')
                ->count();

            $efficiencyMetrics = [
                'order_frequency' => $totalDays > 0 ? $totalPurchaseCount / $totalDays : 0,
                'avg_items_per_order' => $avgItemsQuery->count() > 0 ? ($avgItemsQuery->avg('items_count') ?? 0) : 0,
                'cost_per_item_type' => $distinctItemTypes > 0 && is_numeric($purchaseSummary->total_amount) ? 
                    ($purchaseSummary->total_amount ?? 0) / $distinctItemTypes : 0
            ];

            // Growth comparison with previous period
            $periodLength = $endDate->diffInDays($startDate) + 1;
            $previousStartDate = Carbon::parse($startDate)->subDays($periodLength);
            $previousEndDate = Carbon::parse($startDate)->subDay();

            $previousPeriodPurchases = Purchase::where('status', '!=', 'cancelled')
                ->whereBetween('purchase_date', [$previousStartDate, $previousEndDate])
                ->sum('total_amount');
            
            $previousPeriodPurchases = $previousPeriodPurchases ?? 0;

            // Safe calculation untuk purchase growth
            $purchaseGrowth = 0;
            if ($previousPeriodPurchases > 0 && is_numeric($purchaseSummary->completed_amount) && is_numeric($previousPeriodPurchases)) {
                $currentAmount = $purchaseSummary->completed_amount ?? 0;
                $growth = (($currentAmount - $previousPeriodPurchases) / $previousPeriodPurchases) * 100;
                $purchaseGrowth = is_finite($growth) ? round($growth, 2) : 0;
            }

            return $this->successResponse([
                'summary' => [
                    'total_purchases' => (int)($purchaseSummary->total_purchases ?? 0),
                    'total_amount' => $this->formatRupiah($purchaseSummary->total_amount ?? 0),
                    'completed_amount' => $this->formatRupiah($purchaseSummary->completed_amount ?? 0),
                    'pending_amount' => $this->formatRupiah($purchaseSummary->pending_amount ?? 0),
                    'avg_purchase_value' => $this->formatRupiah($purchaseSummary->avg_purchase_value ?? 0),
                    'completed_purchases' => (int)($purchaseSummary->completed_purchases ?? 0),
                    'pending_purchases' => (int)($purchaseSummary->pending_purchases ?? 0),
                    'cancelled_purchases' => (int)($purchaseSummary->cancelled_purchases ?? 0),
                    'cost_of_sales_ratio' => is_numeric($costOfSalesRatio) && !is_nan($costOfSalesRatio) ? $costOfSalesRatio : 0,
                    'purchase_growth' => is_numeric($purchaseGrowth) && !is_nan($purchaseGrowth) ? $purchaseGrowth : 0,
                    'previous_period_amount' => $this->formatRupiah($previousPeriodPurchases ?? 0)
                ],
                'frequently_ordered_items' => $frequentlyOrderedItems->map(function($item) {
                    return [
                        'name' => $item->name,
                        'order_frequency' => $item->order_frequency,
                        'total_quantity' => $item->total_quantity,
                        'avg_order_quantity' => round($item->avg_order_quantity, 2),
                        'total_cost' => $this->formatRupiah($item->total_cost),
                        'avg_unit_cost' => $this->formatRupiah($item->avg_unit_cost),
                        'last_ordered' => $item->last_ordered,
                        'days_between_orders' => Carbon::parse($item->first_ordered)->diffInDays(Carbon::parse($item->last_ordered)) / max(1, $item->order_frequency - 1)
                    ];
                }),
                'cost_trend_analysis' => $costTrendAnalysis->map(function($item) {
                    return [
                        'name' => $item->name,
                        'lowest_cost' => $this->formatRupiah($item->lowest_cost),
                        'highest_cost' => $this->formatRupiah($item->highest_cost),
                        'avg_cost' => $this->formatRupiah($item->avg_cost),
                        'cost_variance' => $this->formatRupiah($item->cost_variance),
                        'price_volatility_percent' => $item->price_volatility_percent
                    ];
                }),
                'supplier_performance' => $supplierPerformance->map(function($item) {
                    return [
                        'name' => $item->name,
                        'total_orders' => $item->total_orders,
                        'total_spent' => $this->formatRupiah($item->total_spent),
                        'avg_order_value' => $this->formatRupiah($item->avg_order_value),
                        'completion_rate' => $item->completion_rate,
                        'avg_delivery_days' => round($item->avg_delivery_days ?: 0, 1),
                        'cancelled_orders' => $item->cancelled_orders
                    ];
                }),
                'monthly_pattern' => $monthlyPattern->map(function($item) {
                    return [
                        'period' => $item->year . '-' . sprintf('%02d', $item->month),
                        'purchase_count' => $item->purchase_count,
                        'total_amount' => $this->formatRupiah($item->total_amount),
                        'avg_purchase' => $this->formatRupiah($item->avg_purchase)
                    ];
                }),
                'critical_stock_analysis' => $criticalStockAnalysis->map(function($item) {
                    return [
                        'name' => $item->name,
                        'current_stock' => $item->current_stock,
                        'reorder_level' => $item->reorder_level,
                        'stock_status' => $item->stock_status,
                        'last_purchase_date' => $item->last_purchase_date,
                        'days_since_last_purchase' => $item->days_since_last_purchase,
                        'avg_purchase_qty' => round($item->avg_purchase_qty ?: 0, 2),
                        'recommended_action' => $this->getRecommendedAction($item->stock_status, $item->days_since_last_purchase)
                    ];
                }),
                'efficiency_metrics' => [
                    'orders_per_day' => round($efficiencyMetrics['order_frequency'], 2),
                    'avg_items_per_order' => round($efficiencyMetrics['avg_items_per_order'] ?: 0, 2),
                    'cost_per_item_type' => $this->formatRupiah($efficiencyMetrics['cost_per_item_type'])
                ],
                'business_insights' => $this->generatePurchaseInsights($supplierPerformance, $criticalStockAnalysis, $costTrendAnalysis, $purchaseGrowth),
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days' => $periodLength
                ]
            ], 'Comprehensive purchase analytics report retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve purchase report: ' . $e->getMessage());
        }
    }

    /**
     * Get recommended action for stock management
     */
    private function getRecommendedAction($stockStatus, $daysSinceLastPurchase): string
    {
        if ($stockStatus === 'Critical') {
            return 'ORDER IMMEDIATELY - Stock critically low';
        } elseif ($stockStatus === 'Low' && $daysSinceLastPurchase > 30) {
            return 'REORDER SOON - Stock low and last order was over 30 days ago';
        } elseif ($daysSinceLastPurchase > 60) {
            return 'REVIEW USAGE - No purchase in 60+ days, check if still needed';
        } else {
            return 'MONITOR - Stock within acceptable range';
        }
    }

    /**
     * Generate business insights and recommendations
     */
    private function generatePurchaseInsights($suppliers, $criticalStock, $costTrends, $growth): array
    {
        $insights = [];
        
        // Supplier insights
        $bestSupplier = $suppliers->first();
        if ($bestSupplier && $bestSupplier->completion_rate > 90) {
            $insights[] = "✅ Supplier terbaik: {$bestSupplier->name} dengan completion rate {$bestSupplier->completion_rate}%";
        }
        
        // Critical stock insights
        $criticalCount = $criticalStock->where('stock_status', 'Critical')->count();
        if ($criticalCount > 0) {
            $insights[] = "⚠️ {$criticalCount} item dalam status stok kritis - perlu segera direstock";
        }
        
        // Cost trend insights
        $highVolatilityItems = $costTrends->where('price_volatility_percent', '>', 20)->count();
        if ($highVolatilityItems > 0) {
            $insights[] = "📈 {$highVolatilityItems} item mengalami fluktuasi harga >20% - pertimbangkan negosiasi kontrak";
        }
        
        // Growth insights
        if ($growth > 10) {
            $insights[] = "📊 Pembelian meningkat {$growth}% - pertimbangkan bulk purchasing untuk efisiensi";
        } elseif ($growth < -10) {
            $insights[] = "📉 Pembelian menurun {$growth}% - review usage patterns dan optimasi inventory";
        }
        
        return $insights;
    }

    /**
     * Format currency to Indonesian Rupiah format
     */
    private function formatRupiah($amount): string
    {
        // Super aggressive handling of all possible invalid values
        if (is_null($amount) || $amount === '' || $amount === false || !is_numeric($amount)) {
            return 'Rp 0';
        }
        
        // Convert to number and check again
        $amount = floatval($amount);
        
        // Check for NaN, infinite, or negative infinity after conversion
        if (is_nan($amount) || is_infinite($amount)) {
            return 'Rp 0';
        }
        
        // Ensure we have a valid number
        if ($amount < 0) {
            $amount = 0;
        }
        
        try {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        } catch (\Exception $e) {
            return 'Rp 0';
        }
    }

    /**
     * Get start date for filtering
     */
    private function getStartDate(Request $request): Carbon
    {
        if ($request->has('start_date')) {
            return Carbon::parse($request->get('start_date'))->startOfDay();
        } elseif ($request->has('month')) {
            return Carbon::parse($request->get('month') . '-01')->startOfMonth();
        } else {
            // Default to current month
            return Carbon::now()->startOfMonth();
        }
    }

    /**
     * Get end date from request
     */
    private function getEndDate(Request $request): Carbon
    {
        if ($request->has('end_date')) {
            return Carbon::parse($request->get('end_date'))->endOfDay();
        } elseif ($request->has('month')) {
            return Carbon::parse($request->get('month') . '-01')->endOfMonth();
        } else {
            // Default to current month
            return Carbon::now()->endOfMonth();
        }
    }

    /**
     * Test data endpoint for debugging
     */
    public function testData(): JsonResponse
    {
        try {
            $orderCount = Order::count();
            $augustOrders = Order::whereBetween('order_date', ['2025-08-01', '2025-08-31'])->count();
            $latestOrder = Order::latest()->first();
            
            return $this->successResponse([
                'total_orders' => $orderCount,
                'august_orders' => $augustOrders,
                'latest_order' => $latestOrder ? [
                    'id' => $latestOrder->id_order,
                    'date' => $latestOrder->order_date,
                    'amount' => $this->formatRupiah($latestOrder->total_amount),
                    'status' => $latestOrder->status
                ] : null
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error getting test data: ' . $e->getMessage());
        }
    }
}
