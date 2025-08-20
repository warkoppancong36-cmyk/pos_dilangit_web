<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;  // ← Import base Controller
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
// use App\Models\Variant; // DISABLED - Variant system removed
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Purchase::with(['supplier', 'items.item', 'creator'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by supplier
            if ($request->has('supplier_id') && !empty($request->supplier_id)) {
                $query->where('supplier_id', $request->supplier_id);
            }

            // Filter by date range
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->whereDate('purchase_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->whereDate('purchase_date', '<=', $request->end_date);
            }

            // Search by purchase number
            if ($request->has('search') && !empty($request->search)) {
                $query->where('purchase_number', 'like', '%' . $request->search . '%');
            }

            $purchases = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $purchases
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading purchases: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id_supplier',
                'purchase_date' => 'required|date',
                'expected_delivery_date' => 'nullable|date|after_or_equal:purchase_date',
                'items' => 'required|array|min:1',
                'items.*.id_item' => 'required|exists:items,id_item',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Generate purchase number
            $lastPurchase = Purchase::orderBy('id_purchase', 'desc')->first();
            $nextNumber = $lastPurchase ? intval(substr($lastPurchase->purchase_number, -6)) + 1 : 1;
            $purchaseNumber = 'PO-' . date('Ymd') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }

            // $taxRate = 0.11; // 11% PPN
            // $taxAmount = $subtotal * $taxRate;
            $taxAmount = $subtotal;
            $discountAmount = $request->get('discount_amount', 0);
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create purchase
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            // Create purchase items
            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id_purchase,
                    'item_id' => $item['id_item'],
                    'quantity_ordered' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['quantity'] * $item['unit_cost'],
                    'unit' => $item['unit'] ?? 'pcs' // Default unit if not provided
                ]);
            }

            DB::commit();

            // Load with relationships
            $purchase->load(['supplier', 'items.item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $purchase = Purchase::with(['supplier', 'items.item', 'creator', 'updater'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            // Only allow updates for pending or ordered status
            if (!in_array($purchase->status, ['pending', 'ordered'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update purchase with status: ' . $purchase->status
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id_supplier',
                'purchase_date' => 'required|date',
                'expected_delivery_date' => 'nullable|date|after_or_equal:purchase_date',
                'items' => 'required|array|min:1',
                'items.*.id_item' => 'required|exists:items,id_item',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Calculate new totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }

            // $taxRate = 0.11; // 11% PPN
            // $taxAmount = $subtotal * $taxRate;
            $taxAmount = $subtotal;
            $discountAmount = $request->get('discount_amount', 0);
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Update purchase
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            // Delete existing items and create new ones
            PurchaseItem::where('purchase_id', $purchase->id_purchase)->delete();

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id_purchase,
                    'item_id' => $item['id_item'],
                    'quantity_ordered' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['quantity'] * $item['unit_cost'],
                    'unit' => $item['unit'] ?? 'pcs' // Default unit if not provided
                ]);
            }

            DB::commit();

            // Load with relationships
            $purchase->load(['supplier', 'items.item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase updated successfully',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,ordered,received,completed,cancelled',
                'actual_delivery_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'status' => $request->status,
                'updated_by' => Auth::id()
            ];

            if ($request->has('actual_delivery_date')) {
                $updateData['actual_delivery_date'] = $request->actual_delivery_date;
            }

            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            $purchase->update($updateData);

            // If status is received or completed, update inventory
            if (in_array($request->status, ['received', 'completed'])) {
                $this->updateInventory($purchase);
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase status updated successfully',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateInventory($purchase)
    {
        // This method will update inventory when goods are received
        // Implementation depends on your inventory management logic
        \Log::info('Starting inventory update for purchase: ' . $purchase->purchase_number);
        
        foreach ($purchase->items as $item) {
            $inventoryItem = $item->item;
            
            \Log::info('Processing item: ' . ($inventoryItem ? $inventoryItem->name : 'NO ITEM'), [
                'item_id' => $item->item_id,
                'quantity_ordered' => $item->quantity_ordered,
                'quantity_received' => $item->quantity_received,
                'unit_cost' => $item->unit_cost
            ]);
            
            // Update inventory stock and cost
            if ($inventoryItem) {
                $stockIncrease = $item->quantity_received ?? $item->quantity_ordered;
                
                // Find or create inventory record for this item
                $inventory = Inventory::where('id_item', $item->item_id)->first();
                
                if ($inventory) {
                    // Update existing inventory
                    $inventory->increment('current_stock', $stockIncrease);
                    $inventory->update([
                        'average_cost' => $item->unit_cost,
                        'last_restocked' => now()
                    ]);
                } else {
                    // Create new inventory record
                    Inventory::create([
                        'id_item' => $item->item_id,
                        'current_stock' => $stockIncrease,
                        'reserved_stock' => 0,
                        'reorder_level' => 10, // Default reorder level
                        'max_stock_level' => 100, // Default max stock
                        'average_cost' => $item->unit_cost,
                        'last_restocked' => now(),
                        'created_by' => auth()->id()
                    ]);
                }
                
                // Update item cost_per_unit
                $inventoryItem->update([
                    'cost_per_unit' => $item->unit_cost
                ]);
                
                \Log::info('Stock and cost updated for item: ' . $inventoryItem->name, [
                    'stock_increase' => $stockIncrease,
                    'new_cost_per_unit' => $item->unit_cost
                ]);
            } else {
                \Log::warning('No item found for purchase item ID: ' . $item->id_purchase_item);
            }
            
            // Create inventory movement record
            // You might want to create an InventoryMovement model and record here
        }
        
        \Log::info('Inventory update completed for purchase: ' . $purchase->purchase_number);
    }

    public function destroy($id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            // Only allow deletion for pending status
            if ($purchase->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete purchase with status: ' . $purchase->status . '. Only pending purchases can be deleted.'
                ], 422);
            }

            // Soft delete purchase items first (cascade will happen via model events)
            $purchase->items()->delete();
            
            // Soft delete purchase
            $purchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted successfully (soft delete - can be restored)'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductsForPurchase(Request $request)
    {
        try {
            $supplierId = $request->get('supplier_id');
            
            // Get products with inventory, optionally filter by supplier
            $query = Product::with(['inventory', 'category'])
                ->where('active', true)
                ->orderBy('name');

            if ($supplierId) {
                // If you have a supplier-product relationship, filter by it
                // For now, return all products
            }

            $products = $query->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading products: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics()
    {
        try {
            $stats = [
                'total_purchases' => Purchase::count(),
                'pending_purchases' => Purchase::where('status', 'pending')->count(),
                'completed_purchases' => Purchase::where('status', 'completed')->count(),
                'total_amount_this_month' => Purchase::whereMonth('purchase_date', now()->month)
                    ->whereYear('purchase_date', now()->year)
                    ->sum('total_amount'),
                'recent_purchases' => Purchase::with(['supplier'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receiveItems(Request $request, $id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.id_purchase_item' => 'required|exists:purchase_items,id_purchase_item',
                'items.*.quantity_received' => 'required|numeric|min:0',
                'items.*.quality_check' => 'nullable|array',
                'items.*.notes' => 'nullable|string|max:500',
                'delivery_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update purchase items with received quantities
            foreach ($request->items as $itemData) {
                $purchaseItem = PurchaseItem::find($itemData['id_purchase_item']);
                
                if ($purchaseItem && $purchaseItem->purchase_id == $purchase->id_purchase) {
                    $purchaseItem->update([
                        'quantity_received' => ($purchaseItem->quantity_received ?? 0) + $itemData['quantity_received'],
                        'quality_check' => $itemData['quality_check'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                        'status' => $itemData['quantity_received'] >= $purchaseItem->quantity_ordered ? 'received' : 'pending'
                    ]);

                    // Update inventory
                    $inventory = Inventory::where('id_item', $purchaseItem->item_id)->first();
                    $stockBefore = $inventory ? $inventory->current_stock : 0;
                    
                    if ($inventory) {
                        $inventory->increment('current_stock', $itemData['quantity_received']);
                        $inventory->update([
                            'last_restocked' => now(),
                            'average_cost' => $purchaseItem->unit_cost
                        ]);
                        
                        // Create inventory movement for existing inventory
                        \App\Models\InventoryMovement::create([
                            'id_inventory' => $inventory->id_inventory,
                            'movement_type' => 'in',
                            'quantity' => $itemData['quantity_received'],
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockBefore + $itemData['quantity_received'],
                            'unit_cost' => $purchaseItem->unit_cost,
                            'total_cost' => $purchaseItem->unit_cost * $itemData['quantity_received'],
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id_purchase,
                            'notes' => "Purchase receipt #{$purchase->purchase_number}",
                            'movement_date' => now(),
                            'created_by' => auth()->id(),
                        ]);
                    } else {
                        // Create new inventory record if doesn't exist
                        $newInventory = Inventory::create([
                            'id_item' => $purchaseItem->item_id,
                            'current_stock' => $itemData['quantity_received'],
                            'reserved_stock' => 0,
                            'reorder_level' => 10,
                            'max_stock_level' => 100,
                            'average_cost' => $purchaseItem->unit_cost,
                            'last_restocked' => now(),
                            'created_by' => auth()->id()
                        ]);
                        
                        // Create inventory movement for new inventory
                        \App\Models\InventoryMovement::create([
                            'id_inventory' => $newInventory->id_inventory,
                            'movement_type' => 'in',
                            'quantity' => $itemData['quantity_received'],
                            'stock_before' => 0,
                            'stock_after' => $itemData['quantity_received'],
                            'unit_cost' => $purchaseItem->unit_cost,
                            'total_cost' => $purchaseItem->unit_cost * $itemData['quantity_received'],
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id_purchase,
                            'notes' => "Initial stock from purchase #{$purchase->purchase_number}",
                            'movement_date' => now(),
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Update purchase delivery date and status if needed
            $purchase->update([
                'actual_delivery_date' => $request->delivery_date
            ]);

            // Check if all items are fully received to update purchase status
            $allItemsReceived = $purchase->items()->where(function($query) {
                $query->whereRaw('quantity_received >= quantity_ordered');
            })->count() === $purchase->items()->count();

            if ($allItemsReceived) {
                $purchase->update(['status' => 'received']);
            }
            // If not all items received, keep current status (probably 'ordered')
            // Don't update to 'partial' as it's not a valid status for purchases table

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items received successfully',
                'data' => $purchase->load(['items.item', 'supplier'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error receiving items: ' . $e->getMessage()
            ], 500);
        }
    }
}
