<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class KitchenController extends Controller
{
    public function getKitchenOrders(Request $request)
    {
        try {
            $forceSource = $request->input('source');
            
            if ($forceSource === 'legacy') {
                return $this->getKitchenOrdersFromOrdersTable($request);
            }
            
            if ($forceSource === 'new') {
                return $this->getKitchenOrdersFromNewTable($request);
            }
            
            $useNewTable = Schema::hasTable('kitchen_orders');
            
            if ($useNewTable) {
                $hasNewTableData = KitchenOrder::count() > 0;
                
                if ($hasNewTableData) {
                    return $this->getKitchenOrdersFromNewTable($request);
                }
            }
            
            return $this->getKitchenOrdersFromOrdersTable($request);
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

    private function getKitchenOrdersFromNewTable(Request $request)
    {
        $query = KitchenOrder::with(['items', 'order.customer']);

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

        if ($request->has('status')) {
            $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled', 'all'];
            $status = $request->status;
            
            if ($status === 'all') {
            } elseif (in_array($status, $validStatuses)) {
                $query->where('status', $status);
            }
        }
        
        if ($request->boolean('not_printed', false)) {
            $query->whereNull('printed_at');
        }
        
        if ($request->boolean('printed_only', false)) {
            $query->whereNotNull('printed_at');
        }

        $query->orderBy('created_at', 'desc');

        $limit = $request->input('limit', 50);
        $limit = min($limit, 100);
        $kitchenOrders = $query->limit($limit)->get();

        $transformedOrders = $kitchenOrders->map(function ($kitchenOrder) {
            $items = $kitchenOrder->items->map(function ($item) {
                return [
                    'id_order_item' => $item->id_order_item,
                    'id_kitchen_order_item' => $item->id_kitchen_order_item,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'variant_name' => $item->variant_name,
                    'customizations' => $item->customizations,
                    'notes' => $item->notes,
                    'status' => $item->status,
                ];
            })->values();

            return [
                'id' => $kitchenOrder->id_kitchen_order,
                'order_number' => $kitchenOrder->order_number,
                'transaction_id' => $kitchenOrder->id_order,
                'customer_name' => $kitchenOrder->customer_name ?? 'Walk-in Customer',
                'table_number' => $kitchenOrder->table_number,
                'order_type' => $kitchenOrder->order_type,
                'created_at' => $kitchenOrder->created_at->toIso8601String(),
                'created_by_station' => $kitchenOrder->created_by_station ?? 'kasir',
                'kitchen_status' => $kitchenOrder->status,
                'kitchen_acknowledged_at' => $kitchenOrder->acknowledged_at?->toIso8601String(),
                'kitchen_completed_at' => $kitchenOrder->completed_at?->toIso8601String(),
                'printed_at' => $kitchenOrder->printed_at?->toIso8601String(),
                'items' => $items,
                'elapsed_time' => $kitchenOrder->elapsed_time,
                'status_text' => $kitchenOrder->status_text,
                'status_color' => $kitchenOrder->status_color,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedOrders,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'count' => $transformedOrders->count(),
                'source' => 'kitchen_orders_table',
                'filters' => [
                    'since_timestamp' => $request->since_timestamp,
                    'status' => $request->status,
                    'limit' => $limit,
                ],
            ],
        ]);
    }

    private function getKitchenOrdersFromOrdersTable(Request $request)
    {
        $query = Order::with(['orderItems.product', 'customer', 'user'])
            ->whereHas('orderItems', function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->where('available_in_kitchen', true);
                });
            })
            ->whereNotNull('kitchen_status');

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

        if ($request->has('status')) {
            $validStatuses = ['pending', 'in_progress', 'completed'];
            if (in_array($request->status, $validStatuses)) {
                $query->where('kitchen_status', $request->status);
            }
        }

        $query->orderBy('created_at', 'desc');

        $limit = $request->input('limit', 50);
        $limit = min($limit, 100);
        $orders = $query->limit($limit)->get();

        $kitchenOrders = $orders->map(function ($order) {
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
                'transaction_id' => $order->id_order,
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
                'source' => 'orders_table',
                'filters' => [
                    'since_timestamp' => $request->since_timestamp,
                    'status' => $request->status,
                    'limit' => $limit,
                ],
            ],
        ]);
    }

    public function updateKitchenOrderStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
                'staff_name' => 'nullable|string|max:255',
                'mark_printed' => 'nullable|boolean',
            ]);

            $useNewTable = Schema::hasTable('kitchen_orders');
            
            if ($useNewTable) {
                return $this->updateKitchenOrderStatusNewTable($request, $id);
            } else {
                return $this->updateKitchenOrderStatusLegacy($request, $id);
            }
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

    private function updateKitchenOrderStatusNewTable(Request $request, $id)
    {
        $kitchenOrder = KitchenOrder::find($id);
        
        if (!$kitchenOrder) {
            $kitchenOrder = KitchenOrder::where('order_number', $id)->first();
        }

        if (!$kitchenOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Kitchen order not found',
                'debug' => ['id' => $id],
            ], 404);
        }

        $oldStatus = $kitchenOrder->status;
        $kitchenOrder->status = $request->status;

        if ($request->status === 'in_progress' && !$kitchenOrder->acknowledged_at) {
            $kitchenOrder->acknowledged_at = now();
        } elseif ($request->status === 'completed') {
            if (!$kitchenOrder->acknowledged_at) {
                $kitchenOrder->acknowledged_at = now();
            }
            $kitchenOrder->completed_at = now();
        }

        if ($request->mark_printed && !$kitchenOrder->printed_at) {
            $kitchenOrder->printed_at = now();
        }

        $kitchenOrder->save();

        Log::info('Kitchen order status updated (new table)', [
            'kitchen_order_id' => $kitchenOrder->id_kitchen_order,
            'order_number' => $kitchenOrder->order_number,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'staff_name' => $request->staff_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Order status updated to {$request->status}",
            'data' => [
                'id' => $kitchenOrder->id_kitchen_order,
                'order_number' => $kitchenOrder->order_number,
                'kitchen_status' => $kitchenOrder->status,
                'kitchen_acknowledged_at' => $kitchenOrder->acknowledged_at?->toIso8601String(),
                'kitchen_completed_at' => $kitchenOrder->completed_at?->toIso8601String(),
                'printed_at' => $kitchenOrder->printed_at?->toIso8601String(),
            ],
        ]);
    }

    private function updateKitchenOrderStatusLegacy(Request $request, $id)
    {
        $order = Order::where('id_order', $id)
            ->orWhere('order_number', $id)
            ->firstOrFail();

        $oldStatus = $order->kitchen_status;
        $order->kitchen_status = $request->status;

        if ($request->status === 'in_progress' && !$order->kitchen_acknowledged_at) {
            $order->kitchen_acknowledged_at = now();
        } elseif ($request->status === 'completed') {
            if (!$order->kitchen_acknowledged_at) {
                $order->kitchen_acknowledged_at = now();
            }
            $order->kitchen_completed_at = now();
        }

        $order->save();

        Log::info('Kitchen order status updated (legacy)', [
            'order_id' => $order->id_order,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'staff_name' => $request->staff_name,
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
    }

    public function createKitchenOrder(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id_order',
                'items' => 'required|array|min:1',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.variant_name' => 'nullable|string',
                'items.*.notes' => 'nullable|string',
                'items.*.id_order_item' => 'nullable|integer',
                'station' => 'nullable|string|in:bar,kasir',
            ]);

            if (!Schema::hasTable('kitchen_orders')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kitchen orders table not available. Please run migrations.',
                ], 500);
            }

            $order = Order::findOrFail($request->order_id);
            $station = $request->input('station', 'kasir');

            // Use findOrCreateForOrder to add to existing kitchen order if available
            $kitchenOrder = KitchenOrder::findOrCreateForOrder($order, $request->items, $station);

            $kitchenOrder->load('items'); //untuk item

            Log::info('Kitchen order updated/created for existing order', [
                'kitchen_order_id' => $kitchenOrder->id_kitchen_order,
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'total_items_count' => $kitchenOrder->items()->count(),
                'station' => $station,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kitchen order created successfully',
                'data' => [
                    'id' => $kitchenOrder->id_kitchen_order,
                    'order_number' => $kitchenOrder->order_number,
                    'status' => $kitchenOrder->status,
                    'created_at' => $kitchenOrder->created_at->toIso8601String(),
                    'items' => $kitchenOrder->items->map(function ($item) {
                        return [
                            'id_kitchen_order_item' => $item->id_kitchen_order_item,
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'variant_name' => $item->variant_name,
                            'notes' => $item->notes,
                            'status' => $item->status,
                        ];
                    }),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating kitchen order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create kitchen order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsPrinted($id)
    {
        try {
            if (!Schema::hasTable('kitchen_orders')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kitchen orders table not available',
                ], 500);
            }

            $kitchenOrder = KitchenOrder::findOrFail($id);
            
            if ($kitchenOrder->printed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already printed',
                    'data' => [
                        'printed_at' => $kitchenOrder->printed_at->toIso8601String(),
                    ],
                ], 400);
            }

            $kitchenOrder->markAsPrinted();

            Log::info('Kitchen order marked as printed', [
                'kitchen_order_id' => $kitchenOrder->id_kitchen_order,
                'order_number' => $kitchenOrder->order_number,
                'printed_at' => $kitchenOrder->printed_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order marked as printed',
                'data' => [
                    'id' => $kitchenOrder->id_kitchen_order,
                    'order_number' => $kitchenOrder->order_number,
                    'printed_at' => $kitchenOrder->printed_at->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kitchen order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error marking kitchen order as printed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark order as printed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
