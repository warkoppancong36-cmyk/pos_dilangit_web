<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KitchenController extends Controller
{
    /**
     * Get kitchen orders untuk Kitchen Display System
     * Polling endpoint untuk fetch orders yang perlu diproses kitchen
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Query Parameters:
     * - since_timestamp: Filter orders created after this timestamp (ISO 8601)
     * - status: Filter by kitchen_status (pending, in_progress, completed)
     * - limit: Max orders to return (default: 50)
     */
    public function getKitchenOrders(Request $request)
    {
        try {
            // Query orders yang memiliki kitchen items
            $query = Order::with(['orderItems.product', 'customer', 'user'])
                ->whereHas('orderItems', function ($q) {
                    // Filter hanya orders yang memiliki item untuk kitchen
                    $q->whereHas('product', function ($productQuery) {
                        $productQuery->where('available_in_kitchen', true);
                    });
                })
                ->whereNotNull('kitchen_status'); // Hanya orders dengan kitchen status

            // Filter by timestamp (untuk polling - hanya fetch orders baru)
            if ($request->has('since_timestamp')) {
                try {
                    $sinceTimestamp = Carbon::parse($request->since_timestamp);
                    $query->where('created_at', '>', $sinceTimestamp);
                } catch (\Exception $e) {
                    Log::warning('Invalid since_timestamp format', [
                        'timestamp' => $request->since_timestamp,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Filter by kitchen status
            if ($request->has('status')) {
                $validStatuses = ['pending', 'in_progress', 'completed'];
                if (in_array($request->status, $validStatuses)) {
                    $query->where('kitchen_status', $request->status);
                }
            }

            // Ordering - terbaru dulu
            $query->orderBy('created_at', 'desc');

            // Limit
            $limit = $request->input('limit', 50);
            $limit = min($limit, 100); // Max 100 untuk mencegah overload
            $orders = $query->limit($limit)->get();

            // Transform data - hanya kirim kitchen-relevant items
            $kitchenOrders = $orders->map(function ($order) {
                // Filter hanya items yang perlu diproses kitchen
                $kitchenItems = $order->orderItems
                    ->filter(function ($item) {
                        return $item->product && $item->product->available_in_kitchen;
                    })
                    ->map(function ($item) {
                        return [
                            'id_order_item' => $item->id_order_item,
                            'product_name' => $item->item_name,
                            'quantity' => $item->quantity,
                            'variant_name' => $item->variant ? $item->variant->variant_name : null,
                            'customizations' => $item->customizations,
                            'notes' => $item->notes,
                            'status' => $item->status,
                        ];
                    })
                    ->values();

                return [
                    'id' => $order->id_order,
                    'order_number' => $order->order_number,
                    'transaction_id' => $order->id_order, // Alias untuk compatibility
                    'customer_name' => $order->customer ? $order->customer->name : ($order->customer_info['name'] ?? 'Walk-in Customer'),
                    'table_number' => $order->table_number,
                    'order_type' => $order->order_type,
                    'created_at' => $order->created_at->toIso8601String(),
                    'created_by_station' => $order->created_by_station ?? 'kasir',
                    'kitchen_status' => $order->kitchen_status,
                    'kitchen_acknowledged_at' => $order->kitchen_acknowledged_at?->toIso8601String(),
                    'kitchen_completed_at' => $order->kitchen_completed_at?->toIso8601String(),
                    'items' => $kitchenItems,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $kitchenOrders,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'count' => $kitchenOrders->count(),
                    'filters' => [
                        'since_timestamp' => $request->since_timestamp,
                        'status' => $request->status,
                        'limit' => $limit,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching kitchen orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kitchen orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update kitchen order status
     * Untuk kitchen staff accept/complete orders
     *
     * @param Request $request
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse
     *
     * Request Body:
     * - status: "in_progress" | "completed"
     * - staff_name: (optional) Nama staff yang handle
     */
    public function updateKitchenOrderStatus(Request $request, $id)
    {
        try {
            // Validasi request
            $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
                'staff_name' => 'nullable|string|max:255',
            ]);

            // Find order - support both ID and order_number
            $order = Order::where('id_order', $id)
                ->orWhere('order_number', $id)
                ->firstOrFail();

            // Update status
            $oldStatus = $order->kitchen_status;
            $order->kitchen_status = $request->status;

            // Update timestamps based on status
            if ($request->status === 'in_progress' && !$order->kitchen_acknowledged_at) {
                $order->kitchen_acknowledged_at = now();
            } elseif ($request->status === 'completed') {
                if (!$order->kitchen_acknowledged_at) {
                    $order->kitchen_acknowledged_at = now();
                }
                $order->kitchen_completed_at = now();
            }

            $order->save();

            // Log activity
            Log::info('Kitchen order status updated', [
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'staff_name' => $request->staff_name,
                'acknowledged_at' => $order->kitchen_acknowledged_at,
                'completed_at' => $order->kitchen_completed_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status}",
                'data' => [
                    'id' => $order->id_order,
                    'order_number' => $order->order_number,
                    'kitchen_status' => $order->kitchen_status,
                    'kitchen_acknowledged_at' => $order->kitchen_acknowledged_at?->toIso8601String(),
                    'kitchen_completed_at' => $order->kitchen_completed_at?->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'debug' => [
                    'id' => $id,
                    'type' => gettype($id),
                    'searched_by' => 'id_order or order_number',
                ],
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating kitchen order status', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
