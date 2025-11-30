<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductItem;
// use App\Models\Variant; // DISABLED - Variant system removed
use App\Models\Customer;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Bank;
use App\Models\InventoryMovement;
use App\Models\CashTransaction;
use App\Models\CashRegister;
use App\Exports\OrdersExport;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PosController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get current active orders for POS
     */
    public function getActiveOrders(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['customer', 'user', 'orderItems.product', 'payments'])
                ->active()
                ->orderBy('created_at', 'desc');

            // Filter by order type
            if ($request->filled('order_type')) {
                $query->byOrderType($request->order_type);
            }

            // Filter by table number
            if ($request->filled('table_number')) {
                $query->where('table_number', $request->table_number);
            }

            // Handle pagination - support 'all' to get all records
            $perPage = $request->get('per_page', 20);
            if ($perPage === 'all') {
                $orders = $query->get();
                // Convert to paginator-like structure for consistency
                $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                    $orders,
                    $orders->count(),
                    $orders->count(),
                    1,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
            } else {
                $orders = $query->paginate($perPage);
            }

            // Add daily order sequence to each order and transform order items
            $orders->getCollection()->transform(function ($order) {
                $order->daily_order_sequence = $order->getDailyOrderSequence();
                
                // Transform order items to include kitchen and bar availability
                $order->orderItems->transform(function ($item) {
                    $item->available_kitchen = $item->product ? $item->product->available_in_kitchen : null;
                    $item->available_bar = $item->product ? $item->product->available_in_bar : null;
                    return $item;
                });
                
                return $order;
            });

            return $this->paginatedResponse($orders, 'Active orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve active orders: ' . $e->getMessage());
        }
    }

    /**
     * Get all orders for transaction history
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            // Log request parameters for debugging
            \Log::info('POS getOrders request parameters:', $request->all());
            
            // Enable query logging for debugging
            \DB::enableQueryLog();
            
            // Check if this is a lightweight export request
            $isLightExport = $request->boolean('light_export', false);
            
            // For lightweight export, only load essential relations
            if ($isLightExport) {
                $query = Order::with(['customer:id_customer,name', 'payments:id_payment,id_order,payment_method,amount'])
                    ->withCount('orderItems as items_count')
                    ->select('id_order', 'order_number', 'id_customer', 'order_type', 'status', 'total_amount', 'created_at')
                    ->orderBy('created_at', 'desc');
            } else {
                $query = Order::with(['customer', 'user', 'orderItems.product', 'payments'])
                    ->orderBy('created_at', 'desc');
            }

            // Search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Table number filter
            if ($request->filled('table_number')) {
                $query->where('table_number', 'like', "%{$request->table_number}%");
            }

            // Category filter - filter by product category
            if ($request->filled('category_id')) {
                $query->whereHas('orderItems.product', function($q) use ($request) {
                    $q->where('id_category', $request->category_id);
                });
            }

            // Payment method filter
            if ($request->filled('payment_method')) {
                $query->whereHas('payments', function($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                });
            }

            // Payment status filter
            if ($request->filled('payment_status')) {
                $paymentStatus = $request->payment_status;
                if ($paymentStatus === 'paid') {
                    // Filter orders that are fully paid
                    $query->whereRaw('(
                        SELECT COALESCE(SUM(amount), 0) 
                        FROM payments 
                        WHERE payments.id_order = orders.id_order 
                        AND payments.status = "completed"
                    ) >= orders.total_amount');
                } elseif ($paymentStatus === 'unpaid') {
                    // Filter orders that are not fully paid
                    $query->whereRaw('(
                        SELECT COALESCE(SUM(amount), 0) 
                        FROM payments 
                        WHERE payments.id_order = orders.id_order 
                        AND payments.status = "completed"
                    ) < orders.total_amount');
                } elseif ($paymentStatus === 'pending') {
                    // Filter orders with pending payments
                    $query->whereHas('payments', function($q) {
                        $q->where('status', 'pending');
                    });
                }
            }

            // Date range filter - only apply if not empty
            if ($request->filled('date_from')) {
                \Log::info('Filtering date_from:', ['date_from' => $request->date_from]);
                $query->whereDate('created_at', '>=', $request->date_from);
            }


            if ($request->filled('date_to')) {
                \Log::info('Filtering date_to:', ['date_to' => $request->date_to]);
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Handle pagination - support 'all' or large numbers to get all records
            $perPage = $request->get('per_page', 50);

            // Apply default 30 days filter ONLY for regular display (not export)
            // Export should respect exact user filters, including empty dates
            $isExport = $perPage === 'all' || (is_numeric($perPage) && $perPage >= 999999);

            if (!$isExport && !$request->has('date_from') && !$request->has('date_to')) {
                \Log::info('Regular display - No date parameters - applying default 30 days filter');
                $query->whereDate('created_at', '>=', now()->subDays(30));
            } elseif ($isExport) {
                \Log::info('Export mode - respecting exact user date filters (may be empty)');
            } else {
                \Log::info('Regular display - Date parameters present - not applying default filter');
            }

            if ($perPage === 'all' || (is_numeric($perPage) && $perPage >= 999999)) {
                \Log::info('Getting all records without pagination', ['per_page' => $perPage]);
                $orders = $query->get();
                // Convert to paginator-like structure for consistency
                $totalCount = $orders->count();
                $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                    $orders,
                    $totalCount,
                    max($totalCount, 1), // Prevent division by zero - minimum 1
                    1,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
            } else {
                \Log::info('Using pagination', ['per_page' => $perPage]);
                $orders = $query->paginate($perPage);
            }

            // Log the SQL queries for debugging
            $queries = \DB::getQueryLog();
            \Log::info('SQL queries executed:', $queries);
            
            // Add daily order sequence to each order and transform order items
            $orders->getCollection()->transform(function ($order) {
                $order->daily_order_sequence = $order->getDailyOrderSequence();
                
                // Transform order items to include kitchen and bar availability
                $order->orderItems->transform(function ($item) {
                    $item->available_kitchen = $item->product ? $item->product->available_in_kitchen : null;
                    $item->available_bar = $item->product ? $item->product->available_in_bar : null;
                    return $item;
                });
                
                return $order;
            });

            return $this->paginatedResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve orders: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction history for export using CACHED DATA (SUPER FAST)
     */
    public function getTransactionHistoryForExport(Request $request): JsonResponse
    {
        try {
            \Log::info('🚀 Fast export - Getting transaction history from CACHE');
            
            $whereConditions = [];
            
            // Date filters
            if ($request->filled('start_date')) {
                $whereConditions[] = ['order_date', '>=', $request->start_date];
            }
            
            if ($request->filled('end_date')) {
                $whereConditions[] = ['order_date', '<=', $request->end_date];
            }
            
            // Month filter (format: YYYY-MM)
            if ($request->filled('month')) {
                $whereConditions[] = [DB::raw('DATE_FORMAT(order_date, "%Y-%m")'), '=', $request->month];
            }
            
            // Hour filters - need to parse from order_time
            $query = DB::table('report_transaction_cache');
            
            foreach ($whereConditions as $condition) {
                $query->where($condition[0], $condition[1], $condition[2] ?? null);
            }
            
            if ($request->filled('hour_start') && $request->filled('hour_end')) {
                $query->whereRaw('HOUR(order_time) BETWEEN ? AND ?', [
                    $request->hour_start,
                    $request->hour_end
                ]);
            }
            
            // Limit
            $limit = min((int)$request->get('per_page', 500), 1000);
            
            $startTime = microtime(true);
            
            $results = $query
                ->orderBy('order_date', 'desc')
                ->orderBy('order_time', 'desc')
                ->limit($limit)
                ->get();
            
            // Parse items_detail JSON
            foreach ($results as $result) {
                $result->items = json_decode($result->items_detail, true) ?? [];
                unset($result->items_detail); // Remove raw JSON
            }
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            \Log::info("✅ Cache query executed in {$executionTime}ms, fetched " . count($results) . " records");
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction history retrieved successfully from cache',
                'data' => $results,
                'meta' => [
                    'total' => count($results),
                    'execution_time_ms' => $executionTime,
                    'limit' => $limit,
                    'source' => 'cache'
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Failed to get transaction history from cache:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction history: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Create new order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'table_number' => 'nullable|string|max:20',
            'guest_count' => 'nullable|integer|min:1',
            'id_customer' => 'nullable|exists:customers,id_customer',
            'customer_info' => 'nullable|array',
            'customer_info.name' => 'required_with:customer_info|string|max:255',
            'customer_info.phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // If this request is a split (contains original_order_id), ensure the original order is not already paid
            if ($request->filled('original_order_id')) {
                $orig = Order::with('payments')->find($request->original_order_id);
                if ($orig) {
                    $paidAmount = (float) $orig->payments()->whereIn('status', ['paid', 'completed'])->sum('amount');
                    if ($paidAmount > 0 || ($orig->is_paid ?? false)) {
                        return $this->errorResponse('Split bill only allowed for orders that are not yet paid', 422);
                    }
                }
            }

            DB::beginTransaction();

            $order = Order::create([
                'id_customer' => $request->id_customer,
                'id_user' => Auth::id(),
                'order_type' => $request->order_type,
                'status' => 'draft',
                'table_number' => $request->table_number,
                'guest_count' => $request->guest_count ?? 1,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'service_charge' => 0,
                'total_amount' => 0,
                'notes' => $request->notes,
                'customer_info' => $request->customer_info,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            $order->load(['customer', 'user', 'orderItems', 'payments']);

            return $this->createdResponse($order, 'Order created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Add item to order
     */
    public function addItem(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_product' => 'nullable|exists:products,id_product',
            'id_package' => 'nullable|exists:packages,id_package',
            'item_type' => 'required|in:product,package',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Handle package order
            if ($request->item_type === 'package') {
                $package = \App\Models\Package::with(['items.product.productItems.item.inventory'])->find($request->id_package);
                if (!$package) {
                    throw new \Exception("Package not found for package ID: {$request->id_package}");
                }
                
                // Check if package is active
                if (!$package->is_active) {
                    throw new \Exception("Paket {$package->name} tidak aktif");
                }
                
                // Check stock availability for all items in package
                foreach ($package->items as $packageItem) {
                    $product = $packageItem->product;
                    $requiredQty = $packageItem->quantity * $request->quantity;
                    
                    $canProduce = $this->calculateProductAvailability($product, $requiredQty);
                    
                    if (!$canProduce['available']) {
                        throw new \Exception("Stock tidak mencukupi untuk item '{$product->name}' dalam paket. {$canProduce['message']}");
                    }
                }
                
                \Log::info('Creating package order item', [
                    'order_id' => $order->id_order,
                    'package_id' => $request->id_package,
                    'package_name' => $package->name,
                    'quantity' => $request->quantity,
                    'user_id' => Auth::id(),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                // Create order item for package
                $orderItem = OrderItem::create([
                    'id_order' => $order->id_order,
                    'id_package' => $request->id_package,
                    'package_name' => $package->name,
                    'item_type' => 'package',
                    'item_name' => $package->name,
                    'item_sku' => $package->sku ?? 'PKG-' . $request->id_package,
                    'quantity' => $request->quantity,
                    'unit_price' => $package->package_price,
                    'total_price' => $package->package_price * $request->quantity,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'discount_type' => $request->discount_type,
                    'discount_percentage' => $request->discount_percentage ?? 0,
                    'notes' => $request->notes ?? null,
                ]);
                
                // Consume items for each product in package
                foreach ($package->items as $packageItem) {
                    $product = $packageItem->product;
                    $requiredQty = $packageItem->quantity * $request->quantity;
                    
                    $this->consumeRecipeItems($product, $requiredQty, $request->cashier_id, $order->id_order, $order->order_type);
                }
                
            } else {
                // Handle regular product order
                $product = Product::with(['productItems.item.inventory'])->find($request->id_product);
                if (!$product) {
                    throw new \Exception("Product not found for product ID: {$request->id_product}");
                }

                // Check stock availability based on recipe/items
                $canProduce = $this->calculateProductAvailability($product, $request->quantity);
                
                if (!$canProduce['available']) {
                    throw new \Exception("Stock tidak mencukupi untuk produk: {$product->name}. {$canProduce['message']}");
                }

                \Log::info('Creating order item', [
                    'order_id' => $order->id_order,
                    'product_id' => $request->id_product,
                    'product_name' => $product->name,
                    'quantity' => $request->quantity,
                    'user_id' => Auth::id(),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                // Create order item
                $orderItem = OrderItem::create([
                    'id_order' => $order->id_order,
                    'id_product' => $request->id_product,
                    'item_type' => 'product',
                    'item_name' => $product->name,
                    'item_sku' => $product->sku ?? 'NO-SKU',
                    'quantity' => $request->quantity,
                    'unit_price' => $request->unit_price ?? $product->unit_price,
                    'total_price' => $request->total_price ?? $product->total_price,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'discount_type' => $request->discount_type,
                    'discount_percentage' => $request->discount_percentage ?? 0,
                    'notes' => $request->notes ?? null,
                ]);

                // Update inventory based on recipe consumption
                $this->consumeRecipeItems($product, $request->quantity, $request->cashier_id, $order->id_order, $order->order_type);
            }

            // Manually recalculate order totals since calculateTotals() is disabled
            $orderItems = $order->orderItems;
            $subtotal = $orderItems->sum('total_price');
            $totalDiscount = $orderItems->sum('discount_amount');
            $totalAmount = $subtotal - $totalDiscount;

            // Update order table with new totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount
            ]);

            DB::commit();

            $order->load(['orderItems.product', 'orderItems.package']);

            return $this->successResponse($orderItem, $request->item_type === 'package' ? 'Package added to order successfully' : 'Item added to order successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to add item: ' . $e->getMessage());
        }
    }

    /**
     * Update item quantity in order
     */
    public function updateItem(Request $request, Order $order, OrderItem $orderItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $oldQuantity = $orderItem->quantity;
            $newQuantity = $request->quantity;
            $quantityDiff = $newQuantity - $oldQuantity;

            // Check inventory if increasing quantity
            if ($quantityDiff > 0) {
                $inventory = Inventory::where('id_product', $orderItem->id_product)
                    ->first();

                if (!$inventory) {
                    // Create inventory if not exists
                    $inventory = Inventory::create([
                        'id_product' => $orderItem->id_product,
                        'current_stock' => 1000, // Default stock
                        'reserved_stock' => $quantityDiff,
                        'reorder_level' => 10
                    ]);
                } else if ($inventory->available_stock < $quantityDiff) {
                    return $this->errorResponse('Insufficient stock available', 400);
                } else {
                    // Update reserved stock
                    $inventory->update([
                        'reserved_stock' => $inventory->reserved_stock + $quantityDiff
                    ]);
                }
            } else if ($quantityDiff < 0) {
                // Release reserved stock
                $inventory = Inventory::where('id_product', $orderItem->id_product)
                    ->first();

                if ($inventory) {
                    $inventory->update([
                        'reserved_stock' => max(0, $inventory->reserved_stock + $quantityDiff)
                    ]);
                }
            }

            // Update order item
            $orderItem->updateQuantity($newQuantity);
            $orderItem->notes = $request->notes;
            $orderItem->discount_amount = $request->discount_amount ?? $orderItem->discount_amount;
            $orderItem->discount_type = $request->discount_type ?? $orderItem->discount_type;
            $orderItem->discount_percentage = $request->discount_percentage ?? $orderItem->discount_percentage;
            $orderItem->save();

            // Manually recalculate order totals since calculateTotals() is disabled
            $orderItems = $order->orderItems;
            $subtotal = $orderItems->sum('total_price');
            $totalDiscount = $orderItems->sum('discount_amount');
            $totalAmount = $subtotal - $totalDiscount;

            // Update order table with new totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount
            ]);

            DB::commit();

            $order->load(['orderItems.product']);

            return $this->successResponse($orderItem, 'Item updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to update item: ' . $e->getMessage());
        }
    }

    /**
     * Update discount for specific order item
     */
    public function updateItemDiscount(Request $request, Order $order, OrderItem $orderItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Apply discount using the model method
            if ($request->discount_type === 'percentage') {
                $orderItem->applyDiscount($request->discount_percentage, 'percentage');
            } else if ($request->discount_type === 'fixed') {
                $orderItem->applyDiscount($request->discount_amount, 'fixed');
            } else {
                // Clear discount
                $orderItem->discount_amount = 0;
                $orderItem->discount_type = null;
                $orderItem->discount_percentage = 0;
                $orderItem->calculateTotalPrice();
                $orderItem->save();
                // $orderItem->order->calculateTotals(); // DISABLED - tax system not used yet
            }

            // Manually recalculate order totals since calculateTotals() is disabled
            $orderItems = $order->orderItems;
            $subtotal = $orderItems->sum('total_price');
            $totalDiscount = $orderItems->sum('discount_amount');
            $totalAmount = $subtotal - $totalDiscount;

            // Update order table with new totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount
            ]);

            DB::commit();

            $order->load(['orderItems.product']);

            return $this->successResponse([
                'order_item' => $orderItem->fresh(),
                'order' => $order->fresh()
            ], 'Item discount updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to update item discount: ' . $e->getMessage());
        }
    }

    /**
     * Remove item from order
     */
    public function removeItem(Order $order, OrderItem $orderItem): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get product to restore recipe items
            $product = Product::with(['productItems.item.inventory'])->find($orderItem->id_product);
            
            if ($product) {
                // Restore stock based on recipe consumption (reverse the consumption)
                $this->restoreRecipeItems($product, $orderItem->quantity, Auth::id(), $order->id_order);
            } else {
                \Log::warning("Product not found when removing item: {$orderItem->id_product}");
                
                // Fallback: Release reserved stock for direct inventory (old method)
                $inventory = Inventory::where('id_product', $orderItem->id_product)
                    ->when($orderItem->id_variant, function($query) use ($orderItem) {
                        return $query->where('id_variant', $orderItem->id_variant);
                    })
                    ->first();

                if ($inventory) {
                    $inventory->update([
                        'reserved_stock' => max(0, $inventory->reserved_stock - $orderItem->quantity)
                    ]);
                }
            }

            // Remove item
            $orderItem->delete();

            // Recalculate order totals
            // $order->calculateTotals(); // DISABLED - tax system not used yet

            // Manually recalculate order totals since calculateTotals() is disabled
            $orderItems = $order->orderItems;
            $subtotal = $orderItems->sum('total_price');
            $totalDiscount = $orderItems->sum('discount_amount');
            $totalAmount = $subtotal - $totalDiscount;

            // Update order table with new totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount
            ]);

            DB::commit();

            return $this->deletedResponse('Item removed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to remove item: ' . $e->getMessage());
        }
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            if ($request->discount_type === 'percentage') {
                if ($request->discount_value > 100) {
                    return $this->errorResponse('Percentage discount cannot exceed 100%', 400);
                }
                $discountAmount = ($order->subtotal * $request->discount_value) / 100;
            } else {
                $discountAmount = $request->discount_value;
                if ($discountAmount > $order->subtotal) {
                    return $this->errorResponse('Discount amount cannot exceed subtotal', 400);
                }
            }

            $order->update([
                'discount_type' => $request->discount_type,
                'discount_amount' => $discountAmount,
            ]);

            // $order->calculateTotals(); // DISABLED - tax system not used yet

            // Manually recalculate total_amount since calculateTotals() is disabled
            $totalAmount = $order->subtotal - $discountAmount;
            $order->update([
                'total_amount' => $totalAmount
            ]);

            return $this->successResponse($order, 'Discount applied successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply discount: ' . $e->getMessage());
        }
    }

    /**
     * Apply tax to order
     */
    public function applyTax(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tax_type' => 'required|in:percentage,fixed',
            'tax_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $subtotalAfterDiscount = $order->subtotal - $order->discount_amount;
            
            if ($request->tax_type === 'percentage') {
                if ($request->tax_value > 100) {
                    return $this->errorResponse('Percentage tax cannot exceed 100%', 400);
                }
                $taxAmount = ($subtotalAfterDiscount * $request->tax_value) / 100;
            } else {
                $taxAmount = $request->tax_value;
            }

            $order->update([
                'tax_type' => $request->tax_type,
                'tax_amount' => $taxAmount,
            ]);

            // $order->calculateTotals(); // DISABLED - tax system not used yet

            return $this->successResponse($order, 'Tax applied successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply tax: ' . $e->getMessage());
        }
    }

    /**
     * Process payment for order
     */
    public function processPayment(Request $request, Order $order): JsonResponse
    {
        // Handle backward compatibility: accept both 'amount' and 'paid_amount'
        if ($request->has('paid_amount') && !$request->has('amount')) {
            $request->merge(['amount' => $request->paid_amount]);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cash,card,kartu,credit_card,debit_card,digital_wallet,ewallet,bank_transfer,qris,gopay,grabfood,ovo,dana,shopeefood,gofood,other',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:50', // optional bank/biller info (legacy/free-text)
            'bank_id' => 'nullable|exists:banks,id_bank', // prefer master bank reference
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Map frontend payment method to database enum values
            $paymentMethodMap = [
                'tunai' => 'cash',  // Flutter app sends 'tunai' but we need 'cash'
                'ewallet' => 'gopay', // Flutter app sends 'ewallet' map to 'gopay' (default e-wallet)
                'card' => 'kartu',
                // 'kartu' stays as 'kartu' - now supported in database enum
                'digital_wallet' => 'gopay', // default digital wallet
                // Add other mappings as needed
            ];
            
            $dbPaymentMethod = $paymentMethodMap[$request->payment_method] ?? $request->payment_method;

            // Check if payment amount doesn't exceed remaining amount
            $remainingAmount = $order->remaining_amount;
            
            // For cash payments, allow overpayment (customer gets change)
            // For non-cash payments, amount must not exceed remaining balance
            if ($dbPaymentMethod !== 'cash' && $request->amount > $remainingAmount) {
                return $this->errorResponse('Payment amount exceeds remaining balance', 400);
            }
            
            // For all payment methods, amount must be positive
            if ($request->amount <= 0) {
                return $this->errorResponse('Payment amount must be greater than zero', 400);
            }
            
            // Calculate actual payment amount (don't exceed remaining for recording)
            $actualPaymentAmount = min($request->amount, $remainingAmount);
            $changeAmount = 0;
            if ($dbPaymentMethod === 'cash' && $request->amount > $remainingAmount) {
                $changeAmount = $request->amount - $remainingAmount;
            }
            // Create payment record
            $paymentNumber = $this->generatePaymentNumber();
            // Resolve bank information. Support either bank_id (preferred) or free-text 'bank'.
            $paymentBank = null;
            $paymentDetailsBank = null;

            if ($request->filled('bank_id')) {
                try {
                    $bankRecord = Bank::find($request->bank_id);
                    if ($bankRecord) {
                        $paymentBank = $bankRecord->code;
                        $paymentDetailsBank = [
                            'id' => $bankRecord->id_bank,
                            'code' => $bankRecord->code,
                            'name' => $bankRecord->name,
                        ];
                    }
                } catch (\Exception $e) {
                    // ignore lookup failure
                }
            } else {
                // Normalize bank free-text value for consistent reporting
                $paymentBank = $this->normalizeBank($request->bank ?? null);
                // Try to resolve to master bank by code or normalized name
                try {
                    $bankRecord = Bank::where('code', $paymentBank)
                        ->orWhereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$paymentBank])
                        ->first();
                    if ($bankRecord) {
                        $paymentBank = $bankRecord->code;
                        $paymentDetailsBank = [
                            'id' => $bankRecord->id_bank,
                            'code' => $bankRecord->code,
                            'name' => $bankRecord->name,
                        ];
                    }
                } catch (\Exception $e) {
                    // ignore - bank master may not exist or query failed
                }
            }
            $payment = Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => $paymentNumber,
                'payment_method' => $dbPaymentMethod,
                'amount' => $actualPaymentAmount,
                'reference_number' => $request->reference_number,
                'change_amount' => $changeAmount,
                'payment_details' => ['bank' => $paymentDetailsBank ?? ($request->bank ?? null)],
                'payment_bank' => $paymentBank,
                'status' => 'paid',
                'notes' => $request->notes,
                'processed_by' => Auth::id(),
                'payment_date' => now(),
            ]);

            // Update order status if fully paid
            if ($order->fresh()->is_paid) {
                if ($order->status === 'draft') {
                    $order->updateStatus('pending');
                }
                
                // Move reserved stock to actual stock reduction
                foreach ($order->orderItems as $item) {
                    $inventory = Inventory::where('id_product', $item->id_product)
                        ->when($item->id_variant, function($query) use ($item) {
                            return $query->where('id_variant', $item->id_variant);
                        })
                        ->first();

                    if ($inventory) {
                        $inventory->update([
                            'current_stock' => $inventory->current_stock - $item->quantity,
                            'reserved_stock' => $inventory->reserved_stock - $item->quantity,
                        ]);

                        // Create inventory movement
                        \App\Models\InventoryMovement::create([
                            'id_inventory' => $inventory->id_inventory,
                            'movement_type' => 'out',
                            'quantity' => $item->quantity,
                            'stock_before' => $inventory->current_stock + $item->quantity,
                            'stock_after' => $inventory->current_stock,
                            'unit_cost' => $inventory->average_cost,
                            'total_cost' => $inventory->average_cost * $item->quantity,
                            'reference_type' => 'order',
                            'reference_id' => $order->id_order,
                            'notes' => "Penjualan order #{$order->order_number}",
                            'movement_date' => now(),
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            DB::commit();

            $order->load(['payments', 'orderItems']);

            // Calculate change amount (only for cash payments)
       

            return $this->successResponse([
                'payment' => $payment,
                'order' => $order,
                'change_amount' => $changeAmount,
                'paid_amount' => $request->amount,
                'actual_payment_recorded' => $actualPaymentAmount
            ], 'Payment processed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to process payment: ' . $e->getMessage());
        }
    }



    /**
     * Get detailed order information for mobile API
     */
    public function getOrderDetails($orderId): JsonResponse
    {
        try {
            $order = Order::with([
                'customer',
                'user',
                'orderItems.product.category',
                'payments'
            ])->find($orderId);

            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Calculate order statistics
            $totalItems = $order->orderItems->sum('quantity');
            $totalPaid = $order->payments->where('status', 'paid')->sum('amount');
            $remainingAmount = max(0, $order->total_amount - $totalPaid);
            
            // Format order items for mobile
            $formattedItems = $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id_order_item,
                    'product_id' => $item->id_product,
                    'product_name' => $item->item_name,
                    'product_sku' => $item->item_sku,
                    'category' => $item->product && $item->product->category ? [
                        'id' => $item->product->category->id_category,
                        'name' => $item->product->category->name
                    ] : null,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'formatted_unit_price' => 'Rp ' . number_format($item->unit_price, 0, ',', '.'),
                    'formatted_total_price' => 'Rp ' . number_format($item->total_price, 0, ',', '.'),
                    'notes' => $item->notes,
                    'available_kitchen' => $item->product ? $item->product->available_in_kitchen : null,
                    'available_bar' => $item->product ? $item->product->available_in_bar : null,
                    'image_url' => $item->product ? $item->product->image_url : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            });

            // Format payments for mobile
            $formattedPayments = $order->payments->map(function ($payment) {
                return [
                    'id' => $payment->id_payment,
                    'payment_number' => $payment->payment_number,
                    'payment_method' => $payment->payment_method,
                    'payment_method_label' => $this->getPaymentMethodLabel($payment->payment_method),
                    'amount' => (float) $payment->amount,
                    'formatted_amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                    'cash_received' => $payment->cash_received ? (float) $payment->cash_received : null,
                    'change_amount' => $payment->change_amount ? (float) $payment->change_amount : null,
                    'formatted_cash_received' => $payment->cash_received ? 'Rp ' . number_format($payment->cash_received, 0, ',', '.') : null,
                    'formatted_change_amount' => $payment->change_amount ? 'Rp ' . number_format($payment->change_amount, 0, ',', '.') : null,
                    'reference_number' => $payment->reference_number,
                    'payment_bank' => $payment->payment_bank,
                    'bank' => $payment->payment_details['bank'] ?? null,
                    'status' => $payment->status,
                    'status_label' => $this->getPaymentStatusLabel($payment->status),
                    'notes' => $payment->notes,
                    'payment_date' => $payment->payment_date,
                    'created_at' => $payment->created_at
                ];
            });

            // Prepare comprehensive order data for mobile
            $orderData = [
                'id' => $order->id_order,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'order_type_label' => $this->getOrderTypeLabel($order->order_type),
                'status' => $order->status,
                'status_label' => $this->getOrderStatusLabel($order->status),
                'table_number' => $order->table_number,
                'guest_count' => (int) $order->guest_count,
                
                // Customer information
                'customer' => $order->customer ? [
                    'id' => $order->customer->id_customer,
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                    'address' => $order->customer->address
                ] : null,
                
                // Cashier information
                'cashier' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email
                ] : null,
                
                // Financial information
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'discount_type' => $order->discount_type,
                'tax_amount' => (float) ($order->tax_amount ?? 0),
                'service_charge' => (float) ($order->service_charge ?? 0),
                'total_amount' => (float) $order->total_amount,
                'total_paid' => (float) $totalPaid,
                'remaining_amount' => (float) $remainingAmount,
                
                // Formatted financial information
                'formatted_subtotal' => 'Rp ' . number_format($order->subtotal, 0, ',', '.'),
                'formatted_discount_amount' => 'Rp ' . number_format($order->discount_amount, 0, ',', '.'),
                'formatted_tax_amount' => 'Rp ' . number_format($order->tax_amount ?? 0, 0, ',', '.'),
                'formatted_service_charge' => 'Rp ' . number_format($order->service_charge ?? 0, 0, ',', '.'),
                'formatted_total_amount' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                'formatted_total_paid' => 'Rp ' . number_format($totalPaid, 0, ',', '.'),
                'formatted_remaining_amount' => 'Rp ' . number_format($remainingAmount, 0, ',', '.'),
                
                // Order statistics
                'total_items' => (int) $totalItems,
                'item_count' => $order->orderItems->count(),
                'payment_count' => $order->payments->count(),
                'is_paid' => $remainingAmount <= 0,
                'is_partial_paid' => $totalPaid > 0 && $remainingAmount > 0,
                
                // Order items and payments
                'items' => $formattedItems,
                'payments' => $formattedPayments,
                
                // Additional information
                'notes' => $order->notes,
                'customer_info' => $order->customer_info,
                
                // Order sequence information
                'daily_order_sequence' => $order->daily_order_sequence,
                
                // Timestamps
                'order_date' => $order->order_date,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'completed_at' => $order->completed_at,
                
                // Formatted timestamps for display
                'formatted_order_date' => $order->order_date ? $order->order_date->format('d/m/Y H:i') : null,
                'formatted_created_at' => $order->created_at->format('d/m/Y H:i'),
                'formatted_updated_at' => $order->updated_at->format('d/m/Y H:i'),
                'formatted_completed_at' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : null,
                
                // Time ago format for mobile
                'created_at_human' => $order->created_at->diffForHumans(),
                'updated_at_human' => $order->updated_at->diffForHumans(),
            ];

            return $this->successResponse($orderData, 'Order details retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Error retrieving order details: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'stack_trace' => $e->getTraceAsString()
            ]);

            return $this->serverErrorResponse('Failed to retrieve order details: ' . $e->getMessage());
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,preparing,ready,completed,cancelled,paid',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,credit_card,debit_card,kartu,qris,digital_wallet,ewallet,gopay,grabpay,ovo,dana,shopeepay,bank_transfer,gofood,grabfood,shopeefood,other',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Log the update request
            \Log::info('Updating order status', [
                'order_id' => $order->id_order,
                'old_status' => $order->status,
                'new_status' => $request->status,
                'discount_amount' => $request->discount_amount,
                'tax_amount' => $request->tax_amount,
                'payment_method' => $request->payment_method,
                'paid_amount' => $request->paid_amount
            ]);
            
            // Map mobile app status "paid" to database order status "completed"
            $orderStatus = $request->status;
            if ($request->status === 'paid') {
                $orderStatus = 'completed';
                \Log::info('Status mapped from "paid" to "completed" for order table');
            }
            
            // Update order status
            $order->updateStatus($orderStatus, Auth::id());
            
            // Log after status update
            \Log::info('Status updated', [
                'order_id' => $order->id_order,
                'current_status' => $order->status,
                'mapped_status' => $orderStatus
            ]);
            
            // Update additional fields if provided
            $updateData = [];
            if ($request->has('discount_amount')) {
                $updateData['discount_amount'] = $request->discount_amount;
            }
            if ($request->has('tax_amount')) {
                $updateData['tax_amount'] = $request->tax_amount;
            }
            
            // Update order with additional data
            if (!empty($updateData)) {
                $order->update($updateData);
                
                // Manually recalculate total_amount without using calculateTotals()
                $subtotal = $order->subtotal;
                $discountAmount = $order->discount_amount;
                $taxAmount = $order->tax_amount ?? 0;
                $serviceCharge = $order->service_charge ?? 0;
                
                $newTotalAmount = $subtotal + $taxAmount + $serviceCharge - $discountAmount;
                $order->update(['total_amount' => $newTotalAmount]);
                
                \Log::info('Additional fields updated', [
                    'order_id' => $order->id_order,
                    'update_data' => $updateData,
                    'new_total' => $newTotalAmount
                ]);
            }

            // Reload order data to ensure response has latest values
            $order = $order->fresh(['user', 'customer', 'orderItems.product', 'payments']);
            
            \Log::info('Final order data', [
                'order_id' => $order->id_order,
                'final_status' => $order->status,
                'final_discount' => $order->discount_amount,
                'final_total' => $order->total_amount
            ]);

            return $this->successResponse($order, 'Order updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Edit/Update existing order
     */
    public function editOrder(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_type' => 'sometimes|in:dine_in,takeaway,delivery',
            'table_number' => 'sometimes|nullable|string|max:20',
            'guest_count' => 'sometimes|nullable|integer|min:1',
            'id_customer' => 'sometimes|nullable|exists:customers,id_customer',
            'customer_info' => 'sometimes|nullable|array',
            'customer_info.name' => 'required_with:customer_info|string|max:255',
            'customer_info.phone' => 'nullable|string|max:20',
            'notes' => 'sometimes|nullable|string|max:500',
            'discount_type' => 'sometimes|nullable|in:percentage,fixed',
            'discount_value' => 'sometimes|nullable|numeric|min:0',
            'items' => 'sometimes|array',
            'items.*.id_product' => 'required_with:items|exists:products,id_product',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Check if order can be edited
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return $this->errorResponse('Cannot edit completed or cancelled orders', 422);
        }

        try {
            DB::beginTransaction();

            // Update order basic information
            $updateData = [];
            if ($request->has('order_type')) $updateData['order_type'] = $request->order_type;
            if ($request->has('table_number')) $updateData['table_number'] = $request->table_number;
            if ($request->has('guest_count')) $updateData['guest_count'] = $request->guest_count;
            if ($request->has('id_customer')) $updateData['id_customer'] = $request->id_customer;
            if ($request->has('customer_info')) $updateData['customer_info'] = $request->customer_info;
            if ($request->has('notes')) $updateData['notes'] = $request->notes;

            if (!empty($updateData)) {
                $order->update($updateData);
            }

            // Update discount if provided
            if ($request->has('discount_type') && $request->has('discount_value')) {
                $discountAmount = 0;
                if ($request->discount_type === 'percentage') {
                    if ($request->discount_value > 100) {
                        return $this->errorResponse('Percentage discount cannot exceed 100%', 400);
                    }
                    $discountAmount = ($order->subtotal * $request->discount_value) / 100;
                } else {
                    $discountAmount = $request->discount_value;
                    if ($discountAmount > $order->subtotal) {
                        return $this->errorResponse('Discount amount cannot exceed subtotal', 400);
                    }
                }

                $order->update([
                    'discount_type' => $request->discount_type,
                    'discount_amount' => $discountAmount,
                ]);
            }

            // Update order items if provided
            if ($request->has('items')) {
                // Release all current reserved stock
                foreach ($order->orderItems as $existingItem) {
                    $inventory = Inventory::where('id_product', $existingItem->id_product)->first();
                    if ($inventory) {
                        $inventory->update([
                            'reserved_stock' => max(0, $inventory->reserved_stock - $existingItem->quantity)
                        ]);
                    }
                }

                // Delete existing items
                $order->orderItems()->delete();

                // Add new items
                foreach ($request->items as $itemData) {
                    $product = Product::with(['productItems.item.inventory'])->find($itemData['id_product']);
                    if (!$product) {
                        throw new \Exception("Product not found for product ID: {$itemData['id_product']}");
                    }

                    // Check stock availability
                    $canProduce = $this->calculateProductAvailability($product, $itemData['quantity']);
                    if (!$canProduce['available']) {
                        throw new \Exception("Stock tidak mencukupi untuk produk: {$product->name}. {$canProduce['message']}");
                    }

                    // Create new order item
                    OrderItem::create([
                        'id_order' => $order->id_order,
                        'id_product' => $itemData['id_product'],
                        'item_name' => $product->name,
                        'item_sku' => $product->sku ?? 'NO-SKU',
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    // Reserve new stock
                    $inventory = Inventory::where('id_product', $itemData['id_product'])->first();
                    if ($inventory) {
                        $inventory->update([
                            'reserved_stock' => $inventory->reserved_stock + $itemData['quantity']
                        ]);
                    }
                }
            }

            // Recalculate order totals
            // $order->calculateTotals(); // DISABLED - tax system not used yet

            DB::commit();

            $order->load(['customer', 'user', 'orderItems.product', 'payments']);

            return $this->successResponse($order, 'Order updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        try {
            DB::beginTransaction();
            \Log::info('Cancelling order', [
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'requested_by' => Auth::id(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Release stock back to inventory for each order item
            foreach ($order->orderItems as $orderItem) {
                $product = Product::with(['productItems.item.inventory'])->find($orderItem->id_product);
                
                if (!$product) {
                    \Log::warning("Product not found for order item: {$orderItem->id_product}");
                    continue;
                }

                // Restore stock based on recipe consumption (reverse the consumption)
                $this->restoreRecipeItems($product, $orderItem->quantity, Auth::id(), $order->id_order);
            }

            // Update order status
            $order->updateStatus('cancelled', Auth::id());

            DB::commit();

            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->serverErrorResponse('Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Get products for POS (with inventory info)
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            // Get products
            $query = Product::with(['category', 'inventory'])
                ->where('active', true)
                ->where('status', 'published');

            // Search by name or SKU
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by station availability
            if ($request->filled('station')) {
                $station = $request->station;
                if ($station === 'kitchen') {
                    $query->where('available_in_kitchen', true);
                } elseif ($station === 'bar') {
                    $query->where('available_in_bar', true);
                } elseif ($station === 'both') {
                    $query->where('available_in_kitchen', true)
                          ->where('available_in_bar', true);
                }
            }

            $products = $query->orderBy('name')->get();

            // Add stock information - VARIANT SYSTEM REMOVED, use product inventory directly
            $products->each(function($product) {
                // Check if product has recipe (ProductItems)
                $productItems = \App\Models\ProductItem::where('product_id', $product->id_product)->get();
                
                $availableStock = 0;
                $isDisabled = false;
                $stockStatus = 'available';
                
                if ($productItems->count() > 0) {
                    // Product has recipe - calculate stock based on available ingredients
                    $minProducible = PHP_INT_MAX;
                    $canProduce = true;
                    
                    foreach ($productItems as $productItem) {
                        $itemInventory = \App\Models\Inventory::where('id_item', $productItem->item_id)->first();
                        
                        if ($itemInventory) {
                            // Calculate how many products can be made from this specific item
                            $quantityNeeded = $productItem->quantity_needed > 0 ? $productItem->quantity_needed : 1;
                            $possibleFromThisItem = floor($itemInventory->available_stock / $quantityNeeded);
                            $minProducible = min($minProducible, $possibleFromThisItem);
                        } else {
                            $canProduce = false;
                            $minProducible = 0;
                            break;
                        }
                    }
                    
                    $availableStock = $canProduce ? ($minProducible == PHP_INT_MAX ? 0 : $minProducible) : 0;
                } else {
                    // No recipe - use default stock 0 for finished products
                    $availableStock = 0;
                }
                
                // Determine stock status and disabled state
                if ($availableStock <= 0) {
                    $isDisabled = true;
                    $stockStatus = 'stok habis';
                } elseif ($availableStock < 0) {
                    $isDisabled = true;
                    $stockStatus = 'stok habis';
                }
                
                $product->stock_info = [
                    'current_stock' => $availableStock,
                    'available_stock' => $availableStock,
                    'is_available' => $availableStock > 0
                ];
                
                // Add disabled status and stock status
                $product->is_disabled = $isDisabled;
                $product->stock_status = $stockStatus;
                
                // Add type indicator for products
                $product->item_type = 'product';
            });

            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products: ' . $e->getMessage());
        }
    }

     /**
     * Get products for POS (with inventory info)
     */
    public function getProductsNonOnline(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['category', 'inventory'])
                ->where('active', true)
                ->where('status', 'published');
                
            // Only exclude 'online' category if no specific category_id is requested
            if (!$request->filled('category_id')) {
                $query->whereHas('category', function($q) {
                    $q->where('name', '!=', 'online')
                      ->where('name', '!=', 'Online')
                      ->where('name', '!=', 'ONLINE');
                });
            }

            // Search by name or SKU
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by station availability
            if ($request->filled('station')) {
                $station = $request->station;
                if ($station === 'kitchen') {
                    $query->where('available_in_kitchen', true);
                } elseif ($station === 'bar') {
                    $query->where('available_in_bar', true);
                } elseif ($station === 'both') {
                    $query->where('available_in_kitchen', true)
                          ->where('available_in_bar', true);
                }
            }

            $products = $query->orderBy('name')->get();

            // Add stock information - VARIANT SYSTEM REMOVED, use product inventory directly
            $products->each(function($product) {
                // Check if product has recipe (ProductItems)
                $productItems = \App\Models\ProductItem::where('product_id', $product->id_product)->get();
                
                if ($productItems->count() > 0) {
                    // Product has recipe - calculate stock based on available ingredients
                    $minProducible = PHP_INT_MAX;
                    $canProduce = true;
                    
                    foreach ($productItems as $productItem) {
                        $itemInventory = \App\Models\Inventory::where('id_item', $productItem->item_id)->first();
                        
                        if ($itemInventory) {
                            // Calculate how many products can be made from this specific item
                            $quantityNeeded = $productItem->quantity_needed > 0 ? $productItem->quantity_needed : 1;
                            $possibleFromThisItem = floor($itemInventory->available_stock / $quantityNeeded);
                            $minProducible = min($minProducible, $possibleFromThisItem);
                        } else {
                            $canProduce = false;
                            $minProducible = 0;
                            break;
                        }
                    }
                    
                    $availableStock = $canProduce ? ($minProducible == PHP_INT_MAX ? 0 : $minProducible) : 0;
                    
                    $product->stock_info = [
                        'current_stock' => $availableStock,
                        'available_stock' => $availableStock,
                        'is_available' => $availableStock > 0
                    ];
                } else {
                    
                    // No inventory record - create one with default stock for finished products
                    $product->stock_info = [
                        'current_stock' => 0, // Default stock for finished products
                        'available_stock' => 0,
                        'is_available' => true
                    ];
                }
                
                // Add type indicator for products
                $product->item_type = 'product';
            });

            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products: ' . $e->getMessage());
        }
    }

    /**
     * Get POS statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $today = today();
            
            $stats = [
                'today_orders' => Order::today()->count(),
                'today_sales' => (float) Order::today()->sum('total_amount'),
                'active_orders' => Order::active()->count(),
                'pending_orders' => Order::byStatus('pending')->count(),
                'completed_orders_today' => Order::today()->completed()->count(),
                'payment_methods_today' => Payment::today()
                    ->completed()
                    ->select('payment_method')
                    ->selectRaw('COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->get()
                    ->keyBy('payment_method'),
                'hourly_sales_today' => Order::today()
                    ->completed()
                    ->selectRaw('HOUR(completed_at) as hour, COUNT(*) as orders, SUM(total_amount) as sales')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get(),
            ];

            return $this->successResponse($stats, 'POS statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }

    /**
     * Direct POS transaction - create order and process payment in one go
     */
    public function processDirectPayment(Request $request): JsonResponse
    {
        // Check if this is a pending order creation (Open Bill)
        $isPendingOrder = $request->has('payment_method') && $request->payment_method === 'pending';
        
        if ($isPendingOrder) {
            return $this->createPendingOrder($request);
        }

        $validator = Validator::make($request->all(), [
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'nullable|integer',
            'cart_items.*.package_id' => 'nullable|integer',
            'cart_items.*.item_type' => 'required|string|in:product,package',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.unit_price' => 'required|numeric|min:0',
            'cart_items.*.subtotal' => 'required|numeric|min:0',
            'cart_items.*.notes' => 'nullable|string|max:255',
            'cart_items.*.discount_amount' => 'nullable|numeric|min:0',
            'cart_items.*.discount_type' => 'nullable|string|in:fixed,percentage',
            'cart_items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'order_type' => 'required|string|in:dine_in,takeaway,delivery',
            'customer_id' => 'nullable|integer|exists:customers,id_customer',
            'table_number' => 'nullable|max:50', // Accept both string and integer
            'payment_method' => 'required|string|in:cash,card,kartu,qris,digital_wallet,ewallet,bank_transfer,pending,tunai,gopay,grabpay,shopeepay,ovo,dana,gofood,grabfood,shopeefood', // Added online payment methods
            'subtotal_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:amount,percentage,none', // Added 'none' for Flutter compatibility
            'discount_code' => 'nullable|string', // Added for Flutter compatibility
            'discount_code_id' => 'nullable|integer', // Added for Flutter compatibility
            'applied_promotions' => 'nullable|array', // Added for Flutter compatibility
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'change_amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'cashier_id' => 'required|integer',
            'transaction_date' => 'nullable|string', // Added for Flutter compatibility
            'bank' => 'nullable|string|max:50', // optional bank/biller info for qris/card/bank_transfer
            'bank_id' => 'nullable|integer|exists:banks,id_bank', // optional master bank reference from mobile
        ]);

        if ($validator->fails()) {
            Log::warning('Payment validation failed', [
                'errors' => $validator->errors(),
                'request_data' => $request->all()
            ]);
            return $this->validationErrorResponse($validator->errors());
        }

        // Additional validation: ensure correct ID is present based on item_type
        foreach ($request->cart_items as $index => $item) {
            $itemType = $item['item_type'] ?? 'product';
            
            if ($itemType === 'package') {
                if (empty($item['package_id']) || $item['package_id'] == 0) {
                    return $this->errorResponse("Item #{$index}: package_id diperlukan untuk item bertipe 'package'", 422);
                }
            } else {
                if (empty($item['product_id']) || $item['product_id'] == 0) {
                    return $this->errorResponse("Item #{$index}: product_id diperlukan untuk item bertipe 'product'", 422);
                }
            }
        }

        // Validate payment amount for cash transactions (handle both 'cash' and 'tunai')
        if (in_array($request->payment_method, ['cash', 'tunai']) && $request->paid_amount < $request->total_amount) {
            return $this->errorResponse('Jumlah bayar tidak mencukupi untuk pembayaran tunai', 422);
        }

        // Map frontend payment method to database enum values
        $paymentMethodMap = [
            'tunai' => 'cash',  // Flutter app sends 'tunai' but we need 'cash'
            'ewallet' => 'gopay', // Flutter app sends 'ewallet' map to 'gopay' (default e-wallet)
            'card' => 'credit_card',
            // 'kartu' stays as 'kartu' - now supported in database enum
            'digital_wallet' => 'gopay', // default digital wallet
            // Add other mappings as needed
        ];
        
        $dbPaymentMethod = $paymentMethodMap[$request->payment_method] ?? $request->payment_method;

        try {
            DB::beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $request->order_type,
                'status' => 'completed',
                'table_number' => $request->table_number, // Added table_number
                'id_user' => $request->cashier_id, // FIXED: use correct column name
                'id_customer' => $request->customer_id,
                'subtotal' => $request->subtotal_amount,
                'discount_amount' => $request->discount_amount ?? 0,
                'discount_type' => $request->discount_type,
                'tax_amount' => $request->tax_amount ?? 0,
                'total_amount' => $request->total_amount,
                'notes' => $request->notes,
                'order_date' => now(),
            ]);

            // Create order items and check inventory
            foreach ($request->cart_items as $index => $item) {
                $itemType = $item['item_type'] ?? 'product';
                
                \Log::info("Processing cart item", [
                    'index' => $index,
                    'item_type' => $itemType,
                    'product_id' => $item['product_id'] ?? null,
                    'package_id' => $item['package_id'] ?? null
                ]);
                
                if ($itemType === 'package') {
                    // Handle package purchase
                    $packageId = $item['package_id'] ?? null;
                    
                    if (!$packageId) {
                        throw new \Exception("Package ID tidak ditemukan pada item index {$index}");
                    }
                    
                    $package = \App\Models\Package::find($packageId);
                    
                    if (!$package) {
                        throw new \Exception("Paket tidak ditemukan dengan ID: {$packageId}");
                    }
                    
                    // Check if package is active
                    if (!$package->is_active) {
                        throw new \Exception("Paket '{$package->name}' tidak aktif");
                    }
                    
                    // Load package items with their products eagerly
                    $package->load(['items.product.productItems.item.inventory']);
                    
                    \Log::info("Package found", [
                        'package_id' => $package->id_package,
                        'package_name' => $package->name,
                        'has_items' => $package->items ? $package->items->count() : 0
                    ]);
                    
                    \Log::info("Package found", [
                        'package_id' => $package->id_package,
                        'package_name' => $package->name,
                        'has_items' => $package->items ? $package->items->count() : 0
                    ]);
                    
                    // Check stock availability for package
                    if ($package->track_stock && ($package->stock < $item['quantity'])) {
                        throw new \Exception("Stock paket tidak mencukupi untuk: {$package->name}. Tersedia: {$package->stock}");
                    }
                    
                    // Create order item for package
                    OrderItem::create([
                        'id_order' => $order->id_order,
                        'id_package' => $packageId,
                        'item_type' => 'package',
                        'package_name' => $package->name ?? 'Unknown Package',
                        'item_name' => $package->name ?? 'Unknown Package',
                        'item_sku' => $package->sku ?? 'NO-SKU',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['subtotal'],
                        'notes' => $item['notes'] ?? null,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'discount_type' => $item['discount_type'] ?? null,
                        'discount_percentage' => $item['discount_percentage'] ?? null,
                        'subtotal_before_discount' => $item['quantity'] * $item['unit_price'],
                    ]);
                    
                    // Reduce package stock if track_stock is enabled
                    if ($package->track_stock) {
                        $package->decrement('stock', $item['quantity']);
                    }
                    
                    // Process each product in the package and consume their ingredients
                    if ($package->items && $package->items->count() > 0) {
                        foreach ($package->items as $packageItem) {
                            // Validate package item has product_id
                            if (!$packageItem->id_product) {
                                \Log::warning("Package item tidak memiliki id_product", [
                                    'package_id' => $package->id_package,
                                    'package_item_id' => $packageItem->id_package_item ?? 'unknown'
                                ]);
                                continue;
                            }
                            
                            // Get the product
                            $product = $packageItem->product;
                            
                            if (!$product) {
                                \Log::error("Product tidak ditemukan dalam paket", [
                                    'package_id' => $package->id_package,
                                    'package_item_id' => $packageItem->id_package_item ?? 'unknown',
                                    'product_id' => $packageItem->id_product,
                                    'message' => 'Product dengan ID ' . $packageItem->id_product . ' tidak ada di database'
                                ]);
                                // Skip this item instead of throwing error
                                continue;
                            }
                            
                            \Log::info("Processing package product", [
                                'product_id' => $product->id_product,
                                'product_name' => $product->name ?? 'Unknown',
                                'quantity_in_package' => $packageItem->quantity
                            ]);
                            
                            // Calculate total quantity needed (package quantity * product quantity in package)
                            $totalQuantity = $item['quantity'] * $packageItem->quantity;
                            
                            // Consume recipe items for this product
                            try {
                                $this->consumeRecipeItems($product, $totalQuantity, $request->cashier_id, $order->id_order, $order->order_type);
                            } catch (\Exception $e) {
                                \Log::error("Error consuming recipe items", [
                                    'product_id' => $product->id_product,
                                    'product_name' => $product->name,
                                    'error' => $e->getMessage()
                                ]);
                                // Continue processing other items
                            }
                        }
                    } else {
                        \Log::warning("Paket tidak memiliki item produk", [
                            'package_id' => $package->id_package,
                            'package_name' => $package->name
                        ]);
                    }
                    
                } else {
                    // Handle regular product purchase
                    $productId = $item['product_id'] ?? null;
                    
                    if (!$productId) {
                        throw new \Exception("Product ID tidak ditemukan pada item index {$index}");
                    }
                    
                    $product = Product::with(['productItems.item.inventory'])->find($productId);
                    
                    if (!$product) {
                        throw new \Exception("Produk tidak ditemukan dengan ID: {$productId}");
                    }

                    // Check stock availability based on recipe/items
                    $canProduce = $this->calculateProductAvailability($product, $item['quantity']);
                    
                    if (!$canProduce['available']) {
                        throw new \Exception("Stock tidak mencukupi untuk produk: {$product->name}. {$canProduce['message']}");
                    }

                    // Create order item
                    OrderItem::create([
                        'id_order' => $order->id_order,
                        'id_product' => $productId,
                        'item_type' => 'product',
                        'item_name' => $product->name ?? 'Unknown Product',
                        'item_sku' => $product->sku ?? 'NO-SKU',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['subtotal'],
                        'notes' => $item['notes'] ?? null,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'discount_type' => $item['discount_type'] ?? null,
                        'discount_percentage' => $item['discount_percentage'] ?? null,
                        'subtotal_before_discount' => $item['quantity'] * $item['unit_price'],
                    ]);

                    // Update inventory based on recipe consumption
                    $this->consumeRecipeItems($product, $item['quantity'], $request->cashier_id, $order->id_order, $order->order_type);
                }
            }

            // If this payment is a split from an existing order, adjust the original order
            if ($request->filled('original_order_id') && $request->filled('selected_items')) {
                try {
                    $originalOrder = Order::with('orderItems')->find($request->original_order_id);
                    if ($originalOrder) {
                        \Log::info("Processing split bill: reducing items on original order {$originalOrder->id_order}", [
                            'selected_items' => $request->selected_items
                        ]);

                        foreach ($request->selected_items as $sel) {
                            $selId = $sel['id'] ?? null; // could be order item id
                            $selProductId = $sel['product_id'] ?? ($sel['id_product'] ?? null);
                            $selQty = (int) ($sel['quantity'] ?? ($sel['qty'] ?? 0));

                            if ($selQty <= 0) continue;

                            // Find matching order item on original order
                            $foundItem = null;
                            if ($selId) {
                                $foundItem = $originalOrder->orderItems->firstWhere('id_order_item', $selId);
                            }
                            if (!$foundItem && $selProductId) {
                                $foundItem = $originalOrder->orderItems->firstWhere('id_product', $selProductId);
                            }

                            // Fallback: match by item name or SKU or price if still not found
                            if (!$foundItem) {
                                $possibleName = trim($sel['menu_name'] ?? $sel['item_name'] ?? ($sel['name'] ?? ''));
                                $possiblePrice = isset($sel['price']) ? floatval($sel['price']) : null;
                                $foundItem = $originalOrder->orderItems->first(function($it) use ($possibleName, $possiblePrice) {
                                    if ($possibleName && strcasecmp(trim($it->item_name), $possibleName) === 0) return true;
                                    if ($possiblePrice !== null && floatval($it->unit_price) == $possiblePrice) return true;
                                    return false;
                                });
                            }

                            if (!$foundItem) {
                                // As last resort try to find any item with enough quantity
                                $foundItem = $originalOrder->orderItems->firstWhere('quantity', '>=', $selQty);
                            }

                            if (!$foundItem) {
                                \Log::warning('Selected split item not found on original order', ['sel' => $sel, 'original_order' => $originalOrder->id_order]);
                                continue;
                            }

                            $origQty = (int) $foundItem->quantity;
                            $qtyToRemove = min($origQty, $selQty);

                                // Adjust inventory reserved quantities for the items moved in the split
                                try {
                                    $productForReserve = \App\Models\Product::with(['productItems.item.inventory'])->find($foundItem->id_product);
                                    if ($productForReserve) {
                                        if ($productForReserve->productItems->isEmpty()) {
                                            // Direct inventory on product itself
                                            $directInv = Inventory::where('id_item', $productForReserve->id_product)->first();
                                            if ($directInv) {
                                                $newReserved = max(0, ($directInv->reserved_stock ?? 0) - $qtyToRemove);
                                                $directInv->update(['reserved_stock' => $newReserved]);
                                                \Log::info('Adjusted reserved_stock for direct product inventory during split', [
                                                    'inventory_id' => $directInv->id_inventory,
                                                    'before' => ($directInv->reserved_stock + $qtyToRemove) ?? null,
                                                    'after' => $newReserved,
                                                    'qty_removed' => $qtyToRemove
                                                ]);
                                            }
                                        } else {
                                            // Product has recipe items -> reduce reserved_stock of ingredient inventories
                                            foreach ($productForReserve->productItems as $pItem) {
                                                $inv = Inventory::where('id_item', $pItem->item_id)->first();
                                                if ($inv) {
                                                    $reserveReduce = ($pItem->quantity_needed ?? 1) * $qtyToRemove;
                                                    $newReserved = max(0, ($inv->reserved_stock ?? 0) - $reserveReduce);
                                                    $inv->update(['reserved_stock' => $newReserved]);
                                                    \Log::info('Adjusted reserved_stock for recipe item during split', [
                                                        'inventory_id' => $inv->id_inventory,
                                                        'item_id' => $pItem->item_id,
                                                        'before' => ($inv->reserved_stock + $reserveReduce) ?? null,
                                                        'after' => $newReserved,
                                                        'qty_removed' => $reserveReduce
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Failed to adjust inventory reserved_stock during split: ' . $e->getMessage());
                                }

                            // Determine per-unit price
                            $unitPrice = $foundItem->unit_price ?? ($foundItem->total_price / max(1, $foundItem->quantity));
                            $removedTotalPrice = $unitPrice * $qtyToRemove;

                            // Pro-rate discount from the order item if present
                            $removedDiscount = 0;
                            if (!empty($foundItem->discount_amount) && $foundItem->quantity > 0) {
                                $removedDiscount = ($foundItem->discount_amount / $foundItem->quantity) * $qtyToRemove;
                            }

                            // Reduce or delete the original order item
                            if ($qtyToRemove >= $origQty) {
                                $foundItem->delete();
                            } else {
                                $foundItem->quantity = $origQty - $qtyToRemove;
                                $foundItem->total_price = max(0, $foundItem->total_price - $removedTotalPrice);
                                $foundItem->discount_amount = max(0, ($foundItem->discount_amount ?? 0) - $removedDiscount);
                                $foundItem->save();
                            }
                        }

                        // Recalculate original order totals
                        $origSubtotal = $originalOrder->orderItems()->sum('total_price');
                        $origDiscount = $originalOrder->orderItems()->sum('discount_amount');
                        $origTotal = $origSubtotal - $origDiscount;

                        $originalOrder->update([
                            'subtotal' => $origSubtotal,
                            'discount_amount' => $origDiscount,
                            'total_amount' => $origTotal,
                        ]);

                        \Log::info('Original order updated after split', [
                            'original_order_id' => $originalOrder->id_order,
                            'subtotal' => $origSubtotal,
                            'discount_amount' => $origDiscount,
                            'total_amount' => $origTotal
                        ]);
                    } else {
                        \Log::warning('Original order not found for split bill', ['original_order_id' => $request->original_order_id]);
                    }
                } catch (\Exception $e) {
                    // Log but do not abort the whole transaction; best-effort update
                    \Log::error('Failed to adjust original order for split bill: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }

            // Create payment record
            $paymentNumber = $this->generatePaymentNumber();
            // Resolve bank information. Support either bank_id (preferred) or free-text 'bank'.
            $paymentBank = null;
            $paymentDetailsBank = null;

            if ($request->filled('bank_id')) {
                try {
                    $bankRecord = Bank::find($request->bank_id);
                    if ($bankRecord) {
                        $paymentBank = $bankRecord->code;
                        $paymentDetailsBank = [
                            'id' => $bankRecord->id_bank,
                            'code' => $bankRecord->code,
                            'name' => $bankRecord->name,
                        ];
                    }
                } catch (\Exception $e) {
                    // ignore lookup failure
                }
            } else {
                // Normalize bank free-text value for consistent reporting
                $paymentBank = $this->normalizeBank($request->bank ?? null);
                // Try to resolve to master bank by code or normalized name
                try {
                    $bankRecord = Bank::where('code', $paymentBank)
                        ->orWhereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$paymentBank])
                        ->first();
                    if ($bankRecord) {
                        $paymentBank = $bankRecord->code;
                        $paymentDetailsBank = [
                            'id' => $bankRecord->id_bank,
                            'code' => $bankRecord->code,
                            'name' => $bankRecord->name,
                        ];
                    }
                } catch (\Exception $e) {
                    // ignore - bank master may not exist or query failed
                }
            }

            $payment = Payment::create([
                'id_order' => $order->id_order,
                'payment_number' => $paymentNumber,
                'payment_method' => $dbPaymentMethod,
                'amount' => $request->paid_amount,
                'cash_received' => $request->payment_method === 'cash' ? $request->paid_amount : null,
                'change_amount' => $request->payment_method === 'cash' ? $request->change_amount : null,
                'payment_details' => ['bank' => $paymentDetailsBank ?? ($request->bank ?? null)],
                'payment_bank' => $paymentBank,
                'reference_number' => $request->reference_number,
                'status' => 'paid',
                'notes' => $request->notes,
                'processed_by' => $request->cashier_id,
                'payment_date' => now(),
            ]);

            // Create cash transaction for cash drawer integration
            $this->createCashTransaction($order, $payment, $request);

            DB::commit();

            // Load order with relationships for response
            $order->load(['orderItems.product', 'payments', 'user']);

            // Log successful transaction
            \Log::info("POS Direct Payment processed successfully", [
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'payment_method' => $request->payment_method,
                'cashier_id' => $request->cashier_id
            ]);

            return $this->successResponse([
                'id' => $order->id_order,
                'transaction_number' => $order->order_number,
                'table_number' => $order->table_number,
                'total_amount' => $order->total_amount,
                'discount_amount' => $request->discount_amount,
                'paid_amount' => $request->paid_amount,
                'change_amount' => $request->change_amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $order->notes,
                'status' => $order->status,
                'daily_order_sequence' => $order->daily_order_sequence,
                'created_at' => $order->created_at,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'product_name' => $item->item_type === 'package' 
                            ? ($item->package_name ?? $item->item_name) 
                            : ($item->product ? $item->product->name : $item->item_name),
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->total_price,
                        'notes' => $item->notes,
                        'item_type' => $item->item_type ?? 'product',
                        'available_kitchen' => $item->product ? $item->product->available_in_kitchen : null,
                        'available_bar' => $item->product ? $item->product->available_in_bar : null,
                    ];
                }),
                'cashier' => $order->user ? [
                    'name' => $order->user->name,
                    'email' => $order->user->email
                ] : null
            ], 'Pembayaran berhasil diproses', 201);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error processing POS direct payment: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Gagal memproses pembayaran: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ', Line: ' . $e->getLine() . ')', 500);
        }
    }

    /**
     * Generate unique order number with daily sequence
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        
        // Get today's start and end
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        
        // Count orders created today to get the next sequence
        $todayOrderCount = Order::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        
        // Next sequence number for today
        $sequence = str_pad($todayOrderCount + 1, 4, '0', STR_PAD_LEFT);
        
        $orderNumber = $prefix . $date . $sequence;
        
        // Double check if order number already exists (safety check)
        while (Order::where('order_number', $orderNumber)->exists()) {
            $todayOrderCount++;
            $sequence = str_pad($todayOrderCount + 1, 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $date . $sequence;
        }
        
        return $orderNumber;
    }

    /**
     * Generate unique payment number
     */
    private function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $paymentNumber = $prefix . $date . $random;
        
        // Check if payment number already exists
        while (Payment::where('payment_number', $paymentNumber)->exists()) {
            $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $paymentNumber = $prefix . $date . $random;
        }
        
        return $paymentNumber;
    }

    /**
     * Normalize bank input into canonical bank code for reporting
     */
    private function normalizeBank(?string $bank): ?string
    {
        if (empty($bank)) return null;

        $b = strtolower(trim($bank));
        // Remove non-alphanumeric characters to make matching easier
        $b = preg_replace('/[^a-z0-9]/', '', $b);

        $map = [
            // Mandiri
            'mandiri' => 'mandiri',
            'bankmandiri' => 'mandiri',
            'mandiribank' => 'mandiri',
            'mnd' => 'mandiri',
            // BNI
            'bni' => 'bni',
            'bankbni' => 'bni',
            // BCA
            'bca' => 'bca',
            'bankbca' => 'bca',
            // BRI
            'bri' => 'bri',
            'bankbri' => 'bri',
            // BTN
            'btn' => 'btn',
            'bankbtn' => 'btn',
            // Other common banks - add as needed
            'cimb' => 'cimb',
            'permata' => 'permata',
            'panin' => 'panin',
            'mega' => 'mega',
            'danamon' => 'danamon',
        ];

        return $map[$b] ?? $b;
    }

    /**
     * Get available quantity for a product based on its recipe
     */
    private function getProductAvailableQuantity(Product $product): int
    {
        // Ensure product has productItems loaded
        if (!$product->relationLoaded('productItems')) {
            $product->load(['productItems.item.inventory']);
        }
        
        // If product has no recipe items, check direct inventory
        if (!$product->productItems || $product->productItems->isEmpty()) {
            $directInventory = Inventory::where('id_item', $product->id_product)->first();
            return $directInventory ? (int) $directInventory->available_stock : 0;
        }

        // For products with recipe, calculate based on ingredients
        $minQuantity = PHP_INT_MAX;
        
        foreach ($product->productItems as $productItem) {
            $inventory = $productItem->item->inventory ?? null;
            
            if (!$inventory) {
                return 0; // No inventory for ingredient
            }
            
            $quantityNeeded = max(1, $productItem->quantity_needed);
            $possibleQuantity = floor($inventory->available_stock / $quantityNeeded);
            
            $minQuantity = min($minQuantity, $possibleQuantity);
        }
        
        return ($minQuantity == PHP_INT_MAX) ? 0 : (int) $minQuantity;
    }

    /**
     * Calculate product availability based on recipe items
     */
    private function calculateProductAvailability(Product $product, int $requestedQuantity): array
    {
        // If product has no recipe items, it's a simple product - check if it has direct inventory
        if ($product->productItems->isEmpty()) {
            $directInventory = Inventory::where('id_item', $product->id_product)->first();
            if ($directInventory) {
                $available = $directInventory->current_stock >= $requestedQuantity;
                return [
                    'available' => $available,
                    'message' => $available ? '' : "Tersedia: {$directInventory->current_stock}, dibutuhkan: {$requestedQuantity}"
                ];
            } else {
                return [
                    'available' => false,
                    'message' => "Tidak ada inventory untuk produk ini"
                ];
            }
        }

        // Product has recipe - check each ingredient
        $canProduceQuantity = PHP_INT_MAX;
        $limitingFactor = null;

        foreach ($product->productItems as $productItem) {
            $item = $productItem->item;
            $inventory = $item->inventory;

            if (!$inventory) {
                return [
                    'available' => false,
                    'message' => "Item '{$item->name}' tidak memiliki inventory"
                ];
            }

            $requiredQuantity = $productItem->quantity_needed * $requestedQuantity;
            $availableQuantity = $inventory->current_stock;
            
            if ($availableQuantity < $requiredQuantity) {
                $maxCanProduce = floor($availableQuantity / $productItem->quantity_needed);
                if ($maxCanProduce < $canProduceQuantity) {
                    $canProduceQuantity = $maxCanProduce;
                    $limitingFactor = $item->name;
                }
            }
        }

        if ($canProduceQuantity < $requestedQuantity) {
            return [
                'available' => false,
                'message' => "Tersedia: {$canProduceQuantity}, dibutuhkan: {$requestedQuantity}. Terbatas oleh item: {$limitingFactor}"
            ];
        }

        return [
            'available' => true,
            'message' => ''
        ];
    }

    /**
     * Consume recipe items from inventory
     */
    private function consumeRecipeItems(Product $product, int $quantity, int $userId, int $orderId = null, string $orderType = 'takeaway'): void
    {
        // Ensure product has productItems loaded
        if (!$product->relationLoaded('productItems')) {
            $product->load(['productItems.item.inventory']);
        }
        
        // If product has no recipe items, try to consume directly
        if (!$product->productItems || $product->productItems->isEmpty()) {
            $directInventory = Inventory::where('id_item', $product->id_product)->first();
            if ($directInventory) {
                $stockBefore = $directInventory->current_stock;
                $stockAfter = $stockBefore - $quantity;
                
                $directInventory->update([
                    'current_stock' => $stockAfter,
                ]);

                // Create inventory movement
                InventoryMovement::create([
                    'id_inventory' => $directInventory->id_inventory,
                    'movement_type' => 'out',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $directInventory->average_cost ?? 0,
                    'total_cost' => ($directInventory->average_cost ?? 0) * $quantity,
                    'reference_type' => 'pos_sale',
                    'reference_id' => $orderId,
                    'notes' => "POS Sale - Direct product consumption (Order Type: {$orderType})",
                    'movement_date' => now(),
                    'created_by' => $userId,
                ]);
            }
            return;
        }

        // Consume recipe items
        foreach ($product->productItems as $productItem) {
            // Ensure productItem has item loaded
            if (!$productItem->relationLoaded('item')) {
                $productItem->load('item.inventory');
            }
            
            $item = $productItem->item;
            
            // Check if item exists
            if (!$item) {
                \Log::error("Item not found for ProductItem", [
                    'product_item_id' => $productItem->id_product_item ?? 'unknown',
                    'product_id' => $product->id_product,
                    'product_name' => $product->name ?? 'Unknown',
                    'item_id' => $productItem->item_id ?? 'unknown'
                ]);
                continue; // Skip this item
            }
            
            // Ensure item has inventory loaded
            if (!$item->relationLoaded('inventory')) {
                $item->load('inventory');
            }
            
            $inventory = $item->inventory;
            
            // Debug logging
            \Log::info("Processing item: " . ($item->name ?? 'Unknown') . " (ID: {$item->id_item})");
            \Log::info("Recipe quantity: {$productItem->quantity_needed}, Order quantity: {$quantity}");
            \Log::info("Item is_takeaway: " . ($item->is_takeaway ? "Yes" : "No"));
            \Log::info("Order type: {$orderType}");
            \Log::info("Inventory found: " . ($inventory ? "Yes (ID: {$inventory->id_inventory})" : "No"));
            
            if ($inventory) {
                // Check if we should consume this item based on takeaway rule
                $shouldConsume = true;
                if ($item->is_takeaway && $orderType !== 'takeaway') {
                    $shouldConsume = false;
                    \Log::info("Skipping consumption - item is takeaway only but order type is {$orderType}");
                }
                
                if ($shouldConsume) {
                    $consumeQuantity = $productItem->quantity_needed * $quantity;
                    $stockBefore = $inventory->current_stock;
                    $stockAfter = $stockBefore - $consumeQuantity;
                    
                    \Log::info("Stock before: {$stockBefore}, Consume: {$consumeQuantity}, Stock after: {$stockAfter}");
                    
                    // Only update if there's actual consumption
                    if ($consumeQuantity > 0) {
                        $inventory->update([
                            'current_stock' => $stockAfter,
                        ]);
                        
                        // Verify update
                        $inventory->refresh();
                        \Log::info("Stock after update: {$inventory->current_stock}");

                        // Create inventory movement
                        InventoryMovement::create([
                            'id_inventory' => $inventory->id_inventory,
                            'movement_type' => 'out',
                            'quantity' => $consumeQuantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'unit_cost' => $inventory->average_cost ?? 0,
                            'total_cost' => ($inventory->average_cost ?? 0) * $consumeQuantity,
                            'reference_type' => 'pos_sale',
                            'reference_id' => $orderId,
                            'notes' => "POS Sale - Recipe consumption for product: " . ($product->name ?? 'Unknown') . " (Item: " . ($item->name ?? 'Unknown') . ", Order Type: {$orderType})",
                            'movement_date' => now(),
                            'created_by' => $userId,
                        ]);
                        
                        \Log::info("Inventory movement created successfully");
                    } else {
                        \Log::warning("Skipping inventory update - consume quantity is 0");
                    }
                } else {
                    \Log::info("Item consumption skipped due to takeaway-only restriction");
                }
            } else {
                \Log::warning("No inventory found for item: {$item->name} (ID: {$item->id_item})");
            }
        }
    }

    /**
     * Restore recipe items to inventory (when order is cancelled)
     */
    private function restoreRecipeItems(Product $product, int $quantity, int $userId, int $orderId): void
    {
        // If product has no recipe items, try to restore directly
        if ($product->productItems->isEmpty()) {
            $directInventory = Inventory::where('id_item', $product->id_product)->first();
            if ($directInventory) {
                $stockBefore = $directInventory->current_stock;
                $stockAfter = $stockBefore + $quantity;
                
                $directInventory->update([
                    'current_stock' => $stockAfter,
                ]);
                
                // Create inventory movement for direct restoration
                InventoryMovement::create([
                    'id_inventory' => $directInventory->id_inventory,
                    'movement_type' => 'in',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $directInventory->average_cost ?? 0,
                    'total_cost' => ($directInventory->average_cost ?? 0) * $quantity,
                    'reference_type' => 'order_cancellation',
                    'reference_id' => $orderId,
                    'notes' => "Order Cancellation - Restored stock for product: {$product->name}",
                    'movement_date' => now(),
                    'created_by' => $userId,
                ]);
                
                \Log::info("Restored direct product stock", [
                    'product_id' => $product->id_product,
                    'product_name' => $product->name,
                    'quantity_restored' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter
                ]);
            }
            return;
        }

        // Restore recipe items
        foreach ($product->productItems as $productItem) {
            $item = $productItem->item;
            $inventory = $item->inventory;
            
            if ($inventory) {
                $restoreQuantity = $productItem->quantity_needed * $quantity;
                $stockBefore = $inventory->current_stock;
                $stockAfter = $stockBefore + $restoreQuantity;
                
                \Log::info("Restoring item stock", [
                    'item_id' => $item->id_item,
                    'item_name' => $item->name,
                    'restore_quantity' => $restoreQuantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter
                ]);
                
                // Only update if there's actual restoration
                if ($restoreQuantity > 0) {
                    $inventory->update([
                        'current_stock' => $stockAfter,
                    ]);
                    
                    // Verify update
                    $inventory->refresh();
                    \Log::info("Stock after restoration: {$inventory->current_stock}");

                    // Create inventory movement for restoration
                    InventoryMovement::create([
                        'id_inventory' => $inventory->id_inventory,
                        'movement_type' => 'in',
                        'quantity' => $restoreQuantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_cost' => $inventory->average_cost ?? 0,
                        'total_cost' => ($inventory->average_cost ?? 0) * $restoreQuantity,
                        'reference_type' => 'order_cancellation',
                        'reference_id' => $orderId,
                        'notes' => "Order Cancellation - Restored recipe item for product: {$product->name} (Item: {$item->name})",
                        'movement_date' => now(),
                        'created_by' => $userId,
                    ]);
                    
                    \Log::info("Inventory movement for restoration created successfully");
                } else {
                    \Log::warning("Skipping inventory restoration - restore quantity is 0");
                }
            } else {
                \Log::warning("No inventory found for item restoration: {$item->name} (ID: {$item->id_item})");
            }
        }
    }

    /**
     * Create cash transaction for sales integration with cash drawer
     */
    private function createCashTransaction($order, $payment, $request)
    {
        try {
            // Get or create default cash register (same logic as CashDrawerController)
            $cashRegister = CashRegister::where('active', true)->first();
            
            if (!$cashRegister) {
                $cashRegister = CashRegister::create([
                    'register_name' => 'Main Register',
                    'register_code' => 'MAIN-001',
                    'location' => 'Main Counter',
                    'current_cash_balance' => 0,
                    'active' => true,
                    'supported_payment_methods' => ['cash', 'card', 'qris'],
                    'description' => 'Main cash register for POS operations',
                    'created_by' => $request->cashier_id
                ]);
            }

            // Create cash transaction record
            CashTransaction::create([
                'id_cash_register' => $cashRegister->id_cash_register,
                'id_user' => $request->cashier_id,
                'type' => 'in',
                'source' => 'sale', // Mark as sales transaction
                'amount' => $order->total_amount,
                'balance_before' => $cashRegister->current_cash_balance,
                'balance_after' => $cashRegister->current_cash_balance + $order->total_amount,
                'description' => "Penjualan - {$order->order_number}",
                'notes' => "Pembayaran {$request->payment_method}" . 
                          ($request->reference_number ? " - Ref: {$request->reference_number}" : ""),
                'reference_number' => $payment->payment_number,
                'reference_id' => $order->id_order,
                'reference_type' => 'order',
                'transaction_date' => now(),
            ]);

            // Update cash register balance
            $cashRegister->update([
                'current_cash_balance' => $cashRegister->current_cash_balance + $order->total_amount
            ]);

            \Log::info("Cash transaction created for sale", [
                'order_id' => $order->id_order,
                'amount' => $order->total_amount,
                'cash_register_id' => $cashRegister->id_cash_register
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to create cash transaction for sale", [
                'order_id' => $order->id_order,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception to avoid breaking the main payment flow
        }
    }

    /**
     * Reserve inventory for pending orders (like consumeRecipeItems but only reserves)
     */
    private function reserveRecipeItems(Product $product, int $quantity, int $orderId): void
    {
        // If product has no recipe items, try to reserve directly
        if ($product->productItems->isEmpty()) {
            $directInventory = Inventory::where('id_item', $product->id_product)->first();
            if ($directInventory) {
                // For pending orders, we just log the reservation - no actual stock movement
                \Log::info("Reserved direct product for pending order", [
                    'product_id' => $product->id_product,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'order_id' => $orderId
                ]);
            }
            return;
        }

        // Reserve recipe items
        foreach ($product->productItems as $productItem) {
            $item = $productItem->item;
            $inventory = $item->inventory;
            
            if ($inventory) {
                $reserveQuantity = $productItem->quantity_needed * $quantity;
                
                // For pending orders, we don't actually move stock but log the reservation
                \Log::info("Reserved recipe item for pending order", [
                    'item_id' => $item->id_item,
                    'item_name' => $item->name,
                    'product_name' => $product->name,
                    'reserve_quantity' => $reserveQuantity,
                    'order_id' => $orderId,
                    'current_stock' => $inventory->current_stock
                ]);
            }
        }
    }

    /**
     * Create pending order (open bill)
     */
    private function createPendingOrder(Request $request): JsonResponse
    {
        // Check if this is an empty open bill (no real items)
        $isEmptyOpenBill = $request->has('cart_items') && 
                          count($request->cart_items) == 1 && 
                          ($request->cart_items[0]['quantity'] ?? 0) == 0;

        $validationRules = [
            'order_type' => 'required|string|in:dine_in,takeaway,delivery',
            'customer_id' => 'nullable|integer|exists:customers,id_customer',
            'notes' => 'nullable|string|max:500',
            'cashier_id' => 'required|integer',
            'table_number' => 'nullable|string|max:50',
            'guest_count' => 'nullable|integer|min:1',
            // Flutter compatibility fields
            'discount_code' => 'nullable|string',
            'discount_code_id' => 'nullable|integer',
            'applied_promotions' => 'nullable|array',
            'transaction_date' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ];

        // Add cart validation only if not empty open bill
        if (!$isEmptyOpenBill) {
            $validationRules = array_merge($validationRules, [
                'cart_items' => 'required|array|min:1',
                'cart_items.*.item_type' => 'required|string|in:product,package',
                'cart_items.*.product_id' => 'nullable|integer',
                'cart_items.*.package_id' => 'nullable|integer',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.unit_price' => 'required|numeric|min:0',
                'cart_items.*.subtotal' => 'nullable|numeric|min:0',
                'cart_items.*.total_price' => 'nullable|numeric|min:0',
                'cart_items.*.notes' => 'nullable|string|max:255',
                'cart_items.*.discount_amount' => 'nullable|numeric|min:0',
                'cart_items.*.discount_type' => 'nullable|string|in:fixed,percentage',
                'cart_items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'subtotal_amount' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
            ]);
        } else {
            // For empty open bill, make amounts optional
            $validationRules = array_merge($validationRules, [
                'cart_items' => 'nullable|array',
                'subtotal_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
            ]);
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create pending order
            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $request->order_type,
                'status' => 'pending',
                'id_user' => $request->cashier_id,
                'id_customer' => $request->customer_id,
                'subtotal' => $request->subtotal_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total_amount' => $request->total_amount ?? 0,
                'table_number' => $request->table_number,
                'guest_count' => $request->guest_count ?? 1,
                'notes' => $request->notes,
                'order_date' => now(),
            ]);

            // Create order items and check inventory (skip for empty open bill)
            if (!$isEmptyOpenBill && !empty($request->cart_items)) {
                foreach ($request->cart_items as $item) {
                    // Skip empty items
                    if (($item['quantity'] ?? 0) == 0) {
                        continue;
                    }

                    $itemType = $item['item_type'] ?? 'product';
                    
                    // Determine subtotal - check both field names
                    $subtotal = $item['subtotal'] ?? $item['total_price'] ?? ($item['unit_price'] * $item['quantity']);

                    if ($itemType === 'package') {
                        // Handle package item
                        $package = \App\Models\Package::with(['items.product.productItems.item.inventory'])
                            ->find($item['package_id']);
                        
                        if (!$package) {
                            throw new \Exception("Package not found for package ID: {$item['package_id']}");
                        }

                        // Check if package is active
                        if (!$package->is_active) {
                            throw new \Exception("Paket '{$package->name}' tidak aktif");
                        }

                        // Check stock availability for all products in package
                        if ($package->items && $package->items->count() > 0) {
                            foreach ($package->items as $packageItem) {
                                if (!$packageItem->id_product || !$packageItem->product) {
                                    continue;
                                }
                                
                                $product = $packageItem->product;
                                $requiredQuantity = $item['quantity'] * $packageItem->quantity;
                                
                                $canProduce = $this->calculateProductAvailability($product, $requiredQuantity);
                                
                                if (!$canProduce['available']) {
                                    throw new \Exception("Stock tidak mencukupi untuk produk dalam paket: {$product->name}. {$canProduce['message']}");
                                }
                            }
                        }

                        // Create order item for package
                        OrderItem::create([
                            'id_order' => $order->id_order,
                            'id_package' => $item['package_id'],
                            'package_name' => $package->name,
                            'item_type' => 'package',
                            'item_name' => $package->name,
                            'item_sku' => $package->sku ?? 'PKG-' . $package->id_package,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $subtotal,
                            'notes' => $item['notes'] ?? null,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                            'discount_type' => $item['discount_type'] ?? null,
                            'discount_percentage' => $item['discount_percentage'] ?? null,
                            'subtotal_before_discount' => $item['quantity'] * $item['unit_price'],
                        ]);

                        // Reserve stock for all products in package
                        if ($package->items && $package->items->count() > 0) {
                            foreach ($package->items as $packageItem) {
                                if (!$packageItem->id_product || !$packageItem->product) {
                                    continue;
                                }
                                
                                $product = $packageItem->product;
                                $requiredQuantity = $item['quantity'] * $packageItem->quantity;
                                $this->reserveRecipeItems($product, $requiredQuantity, $order->id_order);
                            }
                        }
                    } else {
                        // Handle regular product item
                        $product = Product::with(['productItems.item.inventory'])->find($item['product_id']);
                        if (!$product) {
                            throw new \Exception("Product not found for product ID: {$item['product_id']}");
                        }

                        // Check stock availability based on recipe/items
                        $canProduce = $this->calculateProductAvailability($product, $item['quantity']);
                        
                        if (!$canProduce['available']) {
                            throw new \Exception("Stock tidak mencukupi untuk produk: {$product->name}. {$canProduce['message']}");
                        }

                        // Create order item
                        OrderItem::create([
                            'id_order' => $order->id_order,
                            'id_product' => $item['product_id'],
                            'item_type' => 'product',
                            'item_name' => $product->name,
                            'item_sku' => $product->sku ?? 'NO-SKU',
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $subtotal,
                            'notes' => $item['notes'] ?? null,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                            'discount_type' => $item['discount_type'] ?? null,
                            'discount_percentage' => $item['discount_percentage'] ?? null,
                            'subtotal_before_discount' => $item['quantity'] * $item['unit_price'],
                        ]);

                        // Reserve stock (don't consume yet since it's pending payment)
                        $this->reserveRecipeItems($product, $item['quantity'], $order->id_order);
                    }
                }
            }

            DB::commit();

            // Load order with relationships for response
            $order->load(['user', 'customer', 'orderItems.product']);

            // Log successful pending order creation
            \Log::info("Pending order created successfully", [
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'cashier_id' => $request->cashier_id
            ]);

            return $this->successResponse([
                'id' => $order->id_order,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'subtotal' => $order->subtotal,
                'total_amount' => $order->total_amount,
                'table_number' => $order->table_number,
                'guest_count' => $order->guest_count,
                'notes' => $order->notes,
                'created_at' => $order->created_at,
                'order_items' => $order->orderItems->map(function($item) {
                    $response = [
                        'id' => $item->id_order_item,
                        'item_type' => $item->item_type ?? 'product',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'notes' => $item->notes,
                    ];
                    
                    if ($item->item_type === 'package') {
                        $response['package_id'] = $item->id_package;
                        $response['package_name'] = $item->package_name;
                    } else {
                        $response['product_id'] = $item->id_product;
                        $response['product_name'] = $item->item_name;
                        $response['available_kitchen'] = $item->product ? $item->product->available_in_kitchen : null;
                        $response['available_bar'] = $item->product ? $item->product->available_in_bar : null;
                    }
                    
                    return $response;
                }),
                'cashier' => $order->user ? [
                    'name' => $order->user->name,
                    'email' => $order->user->email
                ] : null,
                'customer' => $order->customer ? [
                    'id' => $order->customer->id_customer,
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone
                ] : null
            ], 'Open bill created successfully', 201);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating pending order: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create open bill: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper method to get payment method label
     */
    private function getPaymentMethodLabel(string $method): string
    {
        $labels = [
            'cash' => 'Tunai',
            'card' => 'Kartu',
            'credit_card' => 'Kartu Kredit',
            'debit_card' => 'Kartu Debit',
            'qris' => 'QRIS',
            'digital_wallet' => 'Dompet Digital',
            'gopay' => 'GoPay',
            'grabpay' => 'GrabPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'shopeepay' => 'ShopeePay',
            'bank_transfer' => 'Transfer Bank',
            'other' => 'Lainnya'
        ];

        return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Helper method to get payment status label
     */
    private function getPaymentStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'paid' => 'Lunas',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Helper method to get order type label
     */
    private function getOrderTypeLabel(string $type): string
    {
        $labels = [
            'dine_in' => 'Makan di Tempat',
            'takeaway' => 'Bawa Pulang',
            'delivery' => 'Antar'
        ];

        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Helper method to get order status label
     */
    private function getOrderStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Draft',
            'pending' => 'Menunggu',
            'confirmed' => 'Dikonfirmasi',
            'preparing' => 'Diproses',
            'ready' => 'Siap',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get all categories for filter dropdown
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = \App\Models\Category::active()->get(['id_category', 'name']);
            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve categories: ' . $e->getMessage());
        }
    }

    /**
     * Export orders to Excel
     */
    public function exportOrders(Request $request)
    {
        try {
            $query = Order::with(['customer', 'user', 'orderItems.product', 'payments'])
                ->orderBy('created_at', 'desc');

            // Apply same filters as getOrders method
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('table_number')) {
                $query->where('table_number', 'like', "%{$request->table_number}%");
            }

            if ($request->filled('category_id')) {
                $query->whereHas('orderItems.product', function($q) use ($request) {
                    $q->where('id_category', $request->category_id);
                });
            }

            if ($request->filled('payment_method')) {
                $query->whereHas('payments', function($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                });
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if (!$request->has('date_from') && !$request->has('date_to')) {
                $query->whereDate('created_at', '>=', now()->subDays(30));
            }

            $orders = $query->get();

            // Generate filename with timestamp
            $filename = 'riwayat-transaksi-' . now()->format('Y-m-d-H-i-s') . '.xls';

            // Export to Excel using XML format
            $export = new OrdersExport($orders);
            $excelContent = $export->generateExcelXML();

            return response($excelContent)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'max-age=0');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to export orders: ' . $e->getMessage());
        }
    }

    /**
     * Get packages for POS (dedicated endpoint)
     */
    public function getPackages(Request $request): JsonResponse
    {
        try {
            // Get packages with items and products
            $query = \App\Models\Package::with(['category', 'items.product.productItems.item.inventory'])
                ->where('is_active', true);

            // Search by name or SKU
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $packages = $query->orderBy('name')->get();

            // Calculate stock for each package - use highest stock from products
            $packages->each(function($package) {
                $maxStock = 0;
                
                // Get highest stock from products in the package
                if ($package->items && $package->items->count() > 0) {
                    foreach ($package->items as $packageItem) {
                        if ($packageItem->product) {
                            $product = $packageItem->product;
                            
                            // Calculate product stock based on recipe (same logic as getProducts)
                            $productStock = 0;
                            $productItems = \App\Models\ProductItem::where('product_id', $product->id_product)->get();
                            
                            if ($productItems->count() > 0) {
                                // Product has recipe - calculate stock based on available ingredients
                                $minProducible = PHP_INT_MAX;
                                $canProduce = true;
                                
                                foreach ($productItems as $productItem) {
                                    $itemInventory = \App\Models\Inventory::where('id_item', $productItem->item_id)->first();
                                    
                                    if ($itemInventory) {
                                        // Calculate how many products can be made from this specific item
                                        $quantityNeeded = $productItem->quantity_needed > 0 ? $productItem->quantity_needed : 1;
                                        $possibleFromThisItem = floor($itemInventory->available_stock / $quantityNeeded);
                                        $minProducible = min($minProducible, $possibleFromThisItem);
                                    } else {
                                        $canProduce = false;
                                        $minProducible = 0;
                                        break;
                                    }
                                }
                                
                                $productStock = $canProduce ? ($minProducible == PHP_INT_MAX ? 0 : $minProducible) : 0;
                            } else {
                                // No recipe - use default stock 0
                                $productStock = 0;
                            }
                            
                            // Use the highest stock among all products in package
                            $maxStock = max($maxStock, $productStock);
                        }
                    }
                }
                
                // Determine stock status and disabled state
                $isDisabled = false;
                $stockStatus = 'available';
                
                if ($maxStock <= 0) {
                    $isDisabled = true;
                    $stockStatus = 'stok habis';
                }
                
                // Set additional fields for POS
                $package->item_type = 'package';
                $package->unit_price = $package->package_price;
                $package->total_price = $package->package_price;
                $package->stock = $maxStock;
                $package->is_disabled = $isDisabled;
                $package->stock_status = $stockStatus;
                
                // Explicitly set id_package to ensure it's in response
                if (!isset($package->id_package) && isset($package->id)) {
                    $package->id_package = $package->id;
                }
            });

            return $this->successResponse($packages, 'Packages retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve packages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to retrieve packages: ' . $e->getMessage());
        }
    }
}
