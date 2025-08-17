<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductRecipeController extends Controller
{
    /**
     * Get all recipes for a specific product
     */
    public function index(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            
            $recipes = ProductRecipe::where('product_id', $productId)
                ->with(['recipeItems.item'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            // Update total costs
            foreach ($recipes->items() as $recipe) {
                $recipe->updateTotalCost();
            }

            return response()->json([
                'success' => true,
                'data' => $recipes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recipes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new recipe
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id_product',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'portion_size' => 'nullable|integer|min:1',
            'portion_unit' => 'nullable|string|max:20',
            'preparation_time' => 'nullable|integer|min:1',
            'difficulty_level' => 'nullable|in:easy,medium,hard',
            'instructions' => 'nullable|array',
            'instructions.*' => 'string',
            'active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id_item',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create recipe
            $recipe = ProductRecipe::create([
                'product_id' => $request->product_id,
                'name' => $request->name,
                'description' => $request->description,
                'portion_size' => $request->portion_size,
                'portion_unit' => $request->portion_unit,
                'preparation_time' => $request->preparation_time,
                'difficulty_level' => $request->difficulty_level ?? 'medium',
                'instructions' => $request->instructions,
                'active' => $request->active ?? true,
            ]);

            // Create recipe items
            foreach ($request->items as $item) {
                $itemData = Item::findOrFail($item['item_id']);
                
                ProductRecipeItem::create([
                    'product_recipe_id' => $recipe->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $itemData->unit,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update total cost
            $recipe->updateTotalCost();
            $recipe->load(['recipeItems.item']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe created successfully',
                'data' => $recipe
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific recipe
     */
    public function show($id): JsonResponse
    {
        try {
            $recipe = ProductRecipe::with(['recipeItems.item', 'product'])
                ->findOrFail($id);

            $recipe->updateTotalCost();

            return response()->json([
                'success' => true,
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }
    }

    /**
     * Update a recipe
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'portion_size' => 'nullable|integer|min:1',
            'portion_unit' => 'nullable|string|max:20',
            'preparation_time' => 'nullable|integer|min:1',
            'difficulty_level' => 'nullable|in:easy,medium,hard',
            'instructions' => 'nullable|array',
            'instructions.*' => 'string',
            'active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id_item',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $recipe = ProductRecipe::findOrFail($id);

            // Update recipe
            $recipe->update([
                'name' => $request->name,
                'description' => $request->description,
                'portion_size' => $request->portion_size,
                'portion_unit' => $request->portion_unit,
                'preparation_time' => $request->preparation_time,
                'difficulty_level' => $request->difficulty_level ?? 'medium',
                'instructions' => $request->instructions,
                'active' => $request->active ?? true,
            ]);

            // Delete existing recipe items
            $recipe->recipeItems()->delete();

            // Create new recipe items
            foreach ($request->items as $item) {
                $itemData = Item::findOrFail($item['item_id']);
                
                ProductRecipeItem::create([
                    'product_recipe_id' => $recipe->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $itemData->unit,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update total cost
            $recipe->updateTotalCost();
            $recipe->load(['recipeItems.item']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe updated successfully',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a recipe
     */
    public function destroy($id): JsonResponse
    {
        try {
            $recipe = ProductRecipe::findOrFail($id);
            $recipeName = $recipe->name;
            
            $recipe->delete(); // Cascade will delete recipe items

            return response()->json([
                'success' => true,
                'message' => "Recipe '{$recipeName}' deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recipe statistics for a product
     */
    public function stats($productId): JsonResponse
    {
        try {
            $totalRecipes = ProductRecipe::where('product_id', $productId)->count();
            $activeRecipes = ProductRecipe::where('product_id', $productId)->where('active', true)->count();
            
            $totalItemsUsed = ProductRecipeItem::whereHas('productRecipe', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })->distinct('item_id')->count();

            $avgCostPerRecipe = ProductRecipe::where('product_id', $productId)
                ->where('active', true)
                ->avg('total_cost') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_recipes' => $totalRecipes,
                    'active_recipes' => $activeRecipes,
                    'total_items_used' => $totalItemsUsed,
                    'avg_cost_per_recipe' => round($avgCostPerRecipe, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available items for recipes
     */
    public function availableItems(): JsonResponse
    {
        try {
            $items = Item::where('active', true)
                ->orderBy('name')
                ->get(['id_item', 'name', 'unit', 'cost_per_unit', 'current_stock']);

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available items: ' . $e->getMessage()
            ], 500);
        }
    }
}
