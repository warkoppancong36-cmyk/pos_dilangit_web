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
                    DB::raw('SUM(orders.total_amount) as total_spent')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.order_date', [$startDate, $endDate])
                ->groupBy('customers.id_customer', 'customers.name')
                ->orderBy('total_spent', 'desc')
                ->limit(20)
                ->get();

            return $this->successResponse([
                'summary' => [
                    'total_orders' => $salesSummary->total_orders ?: 0,
                    'total_revenue' => number_format($salesSummary->total_revenue ?: 0, 0, ',', '.'),
                    'average_order' => number_format($salesSummary->average_order ?: 0, 0, ',', '.'),
                    'total_items' => $salesSummary->total_items ?: 0,
                    'completed_orders' => $salesSummary->completed_orders ?: 0,
                    'cancelled_orders' => $salesSummary->cancelled_orders ?: 0
                ],
                'daily_sales' => $dailySales->map(function($item) {
                    return [
                        'date' => $item->date,
                        'total' => $item->total ?: 0
                    ];
                }),
                'top_products' => $topProducts->map(function($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->total_sold ?: 0,
                        'revenue' => number_format($item->total_revenue ?: 0, 0, ',', '.')
                    ];
                }),
                'top_customers' => $salesByCustomer->map(function($item) {
                    return [
                        'name' => $item->name,
                        'orders' => $item->total_orders ?: 0,
                        'revenue' => number_format($item->total_spent ?: 0, 0, ',', '.')
                    ];
                })
            ], 'Sales report retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve sales report: ' . $e->getMessage());
        }
    }

    /**
     * Get purchase report
     */
    public function purchaseReport(Request $request): JsonResponse
    {
        try {
            $startDate = $this->getStartDate($request);
            $endDate = $this->getEndDate($request);

            // Purchase summary
            $purchaseSummary = Purchase::select(
                    DB::raw('COUNT(*) as total_purchases'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('AVG(total_amount) as avg_purchase_value'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_purchases'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_purchases'),
                    DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_purchases')
                )
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->first();

            // Daily purchases breakdown
            $dailyPurchases = Purchase::select(
                    DB::raw('DATE(purchase_date) as date'),
                    DB::raw('COUNT(*) as purchase_count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('AVG(total_amount) as avg_purchase_value')
                )
                ->where('status', '!=', 'cancelled')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(purchase_date)'))
                ->orderBy('date')
                ->get();

            // Purchases by status
            $purchasesByStatus = Purchase::select(
                    'status',
                    DB::raw('COUNT(*) as purchase_count'),
                    DB::raw('SUM(total_amount) as total_amount')
                )
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('status')
                ->get();

            // Top purchased items
            $topItems = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id_purchase')
                ->join('items', 'purchase_items.item_id', '=', 'items.id_item')
                ->select(
                    'items.name',
                    'items.id_item as id',
                    DB::raw('SUM(purchase_items.quantity_ordered) as total_purchased'),
                    DB::raw('SUM(purchase_items.total_cost) as total_amount'),
                    DB::raw('AVG(purchase_items.unit_cost) as avg_unit_cost')
                )
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('items.id_item', 'items.name')
                ->orderBy('total_amount', 'desc')
                ->limit(20)
                ->get();

            // Purchases by supplier
            $purchasesBySupplier = Purchase::join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id_supplier')
                ->select(
                    'suppliers.name',
                    'suppliers.id_supplier as id',
                    DB::raw('COUNT(*) as total_purchases'),
                    DB::raw('SUM(purchases.total_amount) as total_amount')
                )
                ->where('purchases.status', '!=', 'cancelled')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->groupBy('suppliers.id_supplier', 'suppliers.name')
                ->orderBy('total_amount', 'desc')
                ->limit(20)
                ->get();

            return $this->successResponse([
                'summary' => $purchaseSummary,
                'daily_purchases' => $dailyPurchases,
                'status_breakdown' => $purchasesByStatus,
                'top_items' => $topItems,
                'top_suppliers' => $purchasesBySupplier,
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days' => $startDate->diffInDays($endDate) + 1
                ]
            ], 'Purchase report retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve purchase report: ' . $e->getMessage());
        }
    }

    /**
     * Get start date from request
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
                    'amount' => $latestOrder->total_amount,
                    'status' => $latestOrder->status
                ] : null
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error getting test data: ' . $e->getMessage());
        }
    }
}
