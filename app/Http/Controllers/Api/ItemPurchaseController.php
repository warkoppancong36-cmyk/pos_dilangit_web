<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemPurchase;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemPurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ItemPurchase::with(['item', 'purchase', 'creator']);

            // Filter by item
            if ($request->has('item_id') && !empty($request->item_id)) {
                $query->where('item_id', $request->item_id);
            }

            // Filter by purchase
            if ($request->has('purchase_id') && !empty($request->purchase_id)) {
                $query->where('purchase_id', $request->purchase_id);
            }

            // Filter by date range
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Sort by date
            $query->orderBy('created_at', 'desc');

            $itemPurchases = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $itemPurchases,
                'message' => 'Item purchases retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving item purchases: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_id' => 'required|exists:purchases,id',
                'item_id' => 'required|exists:items,id',
                'quantity' => 'required|numeric|min:0.01',
                'unit_cost' => 'required|numeric|min:0',
                'expiry_date' => 'nullable|date|after_or_equal:today',
                'batch_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['created_by'] = Auth::id();
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

            DB::beginTransaction();
            
            // Create item purchase record
            $itemPurchase = ItemPurchase::create($validated);

            // Update item stock
            $item = Item::findOrFail($validated['item_id']);
            $item->current_stock += $validated['quantity'];
            
            // Update item cost if provided
            if (isset($validated['unit_cost'])) {
                $item->cost_per_unit = $validated['unit_cost'];
            }
            
            // Update expiry date if provided
            if (isset($validated['expiry_date'])) {
                $item->expiry_date = $validated['expiry_date'];
            }
            
            $item->updated_by = Auth::id();
            $item->save();

            DB::commit();

            $itemPurchase->load(['item', 'purchase', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $itemPurchase,
                'message' => 'Item purchase created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating item purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $itemPurchase = ItemPurchase::with(['item', 'purchase', 'creator'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $itemPurchase,
                'message' => 'Item purchase retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item purchase not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $itemPurchase = ItemPurchase::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|numeric|min:0.01',
                'unit_cost' => 'required|numeric|min:0',
                'expiry_date' => 'nullable|date|after_or_equal:today',
                'batch_number' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

            DB::beginTransaction();

            // Calculate stock difference
            $oldQuantity = $itemPurchase->quantity;
            $newQuantity = $validated['quantity'];
            $stockDifference = $newQuantity - $oldQuantity;

            // Update item purchase record
            $itemPurchase->update($validated);

            // Adjust item stock
            if ($stockDifference != 0) {
                $item = Item::findOrFail($itemPurchase->item_id);
                $item->current_stock += $stockDifference;
                $item->updated_by = Auth::id();
                $item->save();
            }

            DB::commit();

            $itemPurchase->load(['item', 'purchase', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $itemPurchase,
                'message' => 'Item purchase updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating item purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $itemPurchase = ItemPurchase::findOrFail($id);

            DB::beginTransaction();

            // Adjust item stock (subtract the purchase quantity)
            $item = Item::findOrFail($itemPurchase->item_id);
            $item->current_stock -= $itemPurchase->quantity;
            $item->updated_by = Auth::id();
            $item->save();

            // Delete the purchase record
            $itemPurchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item purchase deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting item purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_id' => 'required|exists:purchases,id',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:items,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'items.*.expiry_date' => 'nullable|date|after_or_equal:today',
                'items.*.batch_number' => 'nullable|string|max:100',
                'items.*.notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $createdItems = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($validated['items'] as $index => $itemData) {
                try {
                    $itemData['purchase_id'] = $validated['purchase_id'];
                    $itemData['created_by'] = Auth::id();
                    $itemData['total_cost'] = $itemData['quantity'] * $itemData['unit_cost'];

                    // Create item purchase record
                    $itemPurchase = ItemPurchase::create($itemData);

                    // Update item stock
                    $item = Item::findOrFail($itemData['item_id']);
                    $item->current_stock += $itemData['quantity'];
                    
                    // Update item cost if provided
                    if (isset($itemData['unit_cost'])) {
                        $item->cost_per_unit = $itemData['unit_cost'];
                    }
                    
                    // Update expiry date if provided
                    if (isset($itemData['expiry_date'])) {
                        $item->expiry_date = $itemData['expiry_date'];
                    }
                    
                    $item->updated_by = Auth::id();
                    $item->save();

                    $itemPurchase->load(['item', 'purchase', 'creator']);
                    $createdItems[] = $itemPurchase;

                } catch (\Exception $e) {
                    $errors[] = "Item #{$index}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Bulk creation failed',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $createdItems,
                'message' => 'Item purchases created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating item purchases: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPurchaseHistory($itemId): JsonResponse
    {
        try {
            $item = Item::findOrFail($itemId);
            
            $purchases = ItemPurchase::with(['purchase', 'creator'])
                ->where('item_id', $itemId)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'item' => $item,
                    'purchases' => $purchases
                ],
                'message' => 'Purchase history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving purchase history: ' . $e->getMessage()
            ], 500);
        }
    }
}
