<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AssetController extends Controller
{
    /**
     * Get all assets
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // For testing, return some sample data if no assets in database
            $assets = Asset::all();
            
            if ($assets->isEmpty()) {
                // Return restaurant-specific sample data for testing
                $sampleAssets = [
                    [
                        'id' => 1,
                        'asset_code' => 'KIT-001',
                        'name' => 'Commercial Oven',
                        'category' => 'Kitchen Equipment',
                        'brand' => 'Rational',
                        'model' => 'SelfCookingCenter',
                        'serial_number' => 'RAT123456',
                        'purchase_date' => '2024-01-15',
                        'purchase_price' => 15999.99,
                        'location' => 'Main Kitchen',
                        'condition' => 'excellent',
                        'status' => 'active',
                        'description' => 'Commercial combi oven for restaurant kitchen',
                        'supplier' => 'Restaurant Supply Co',
                        'warranty_until' => '2027-01-15',
                        'assigned_to' => 'Head Chef',
                        'department' => 'Kitchen',
                        'image_url' => null,
                        'created_at' => '2024-01-15T10:00:00Z',
                        'updated_at' => '2024-01-15T10:00:00Z'
                    ],
                    [
                        'id' => 2,
                        'asset_code' => 'POS-001',
                        'name' => 'Point of Sale Terminal',
                        'category' => 'Electronics',
                        'brand' => 'Square',
                        'model' => 'Terminal',
                        'serial_number' => 'SQ789012',
                        'purchase_date' => '2024-02-01',
                        'purchase_price' => 299.99,
                        'location' => 'Front Counter',
                        'condition' => 'excellent',
                        'status' => 'active',
                        'description' => 'POS system for order processing',
                        'supplier' => 'Square Inc',
                        'warranty_until' => '2026-02-01',
                        'assigned_to' => 'Cashier Team',
                        'department' => 'Front of House',
                        'image_url' => null,
                        'created_at' => '2024-02-01T14:30:00Z',
                        'updated_at' => '2024-02-01T14:30:00Z'
                    ],
                    [
                        'id' => 3,
                        'asset_code' => 'FRIDGE-001',
                        'name' => 'Commercial Refrigerator',
                        'category' => 'Kitchen Equipment',
                        'brand' => 'True Manufacturing',
                        'model' => 'T-49-HC',
                        'serial_number' => 'TRUE345678',
                        'purchase_date' => '2024-01-20',
                        'purchase_price' => 2899.99,
                        'location' => 'Cold Storage',
                        'condition' => 'good',
                        'status' => 'active',
                        'description' => 'Two-door reach-in refrigerator',
                        'supplier' => 'Restaurant Supply Co',
                        'warranty_until' => '2026-01-20',
                        'assigned_to' => 'Kitchen Staff',
                        'department' => 'Kitchen',
                        'image_url' => null,
                        'created_at' => '2024-01-20T11:00:00Z',
                        'updated_at' => '2024-01-20T11:00:00Z'
                    ],
                    [
                        'id' => 4,
                        'asset_code' => 'TABLE-001',
                        'name' => 'Dining Table Set',
                        'category' => 'Furniture',
                        'brand' => 'Restaurant Furniture Plus',
                        'model' => 'Classic Wood',
                        'serial_number' => 'RF901234',
                        'purchase_date' => '2024-01-10',
                        'purchase_price' => 459.99,
                        'location' => 'Dining Area',
                        'condition' => 'good',
                        'status' => 'active',
                        'description' => '4-seat dining table with chairs',
                        'supplier' => 'Restaurant Furniture Plus',
                        'warranty_until' => '2025-01-10',
                        'assigned_to' => 'Front of House',
                        'department' => 'Dining',
                        'image_url' => null,
                        'created_at' => '2024-01-10T09:00:00Z',
                        'updated_at' => '2024-01-10T09:00:00Z'
                    ],
                    [
                        'id' => 5,
                        'asset_code' => 'COFFEE-001',
                        'name' => 'Espresso Machine',
                        'category' => 'Kitchen Equipment',
                        'brand' => 'La Marzocco',
                        'model' => 'Linea PB',
                        'serial_number' => 'LM567890',
                        'purchase_date' => '2024-02-15',
                        'purchase_price' => 4999.99,
                        'location' => 'Beverage Station',
                        'condition' => 'excellent',
                        'status' => 'active',
                        'description' => 'Professional espresso machine',
                        'supplier' => 'Coffee Equipment Co',
                        'warranty_until' => '2027-02-15',
                        'assigned_to' => 'Barista',
                        'department' => 'Beverage',
                        'image_url' => null,
                        'created_at' => '2024-02-15T16:00:00Z',
                        'updated_at' => '2024-02-15T16:00:00Z'
                    ],
                    [
                        'id' => 6,
                        'asset_code' => 'WASH-001',
                        'name' => 'Commercial Dishwasher',
                        'category' => 'Kitchen Equipment',
                        'brand' => 'Hobart',
                        'model' => 'AM15',
                        'serial_number' => 'HOB123789',
                        'purchase_date' => '2024-01-25',
                        'purchase_price' => 3499.99,
                        'location' => 'Dish Pit',
                        'condition' => 'good',
                        'status' => 'active',
                        'description' => 'High-temperature dishwasher',
                        'supplier' => 'Restaurant Supply Co',
                        'warranty_until' => '2026-01-25',
                        'assigned_to' => 'Dish Crew',
                        'department' => 'Kitchen',
                        'image_url' => null,
                        'created_at' => '2024-01-25T12:00:00Z',
                        'updated_at' => '2024-01-25T12:00:00Z'
                    ]
                ];
                
                return response()->json([
                    'success' => true,
                    'data' => $sampleAssets,
                    'message' => 'Restaurant asset samples returned (no database assets found)',
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 20,
                        'total' => 6,
                        'from' => 1,
                        'to' => 6
                    ]
                ]);
            }

            $query = Asset::query();

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('original_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Only active assets
            $query->where('status', 'active');

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $assets = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $assets,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading assets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new asset
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'asset_code' => 'required|string|max:255|unique:assets,asset_code',
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'brand' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'purchase_date' => 'nullable|date',
                'purchase_price' => 'nullable|numeric|min:0',
                'location' => 'required|string|max:255',
                'condition' => 'required|in:excellent,good,fair,poor,damaged',
                'status' => 'required|in:active,inactive,maintenance,disposed',
                'description' => 'nullable|string',
                'supplier' => 'nullable|string|max:255',
                'warranty_until' => 'nullable|date',
                'assigned_to' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create asset record
            $asset = Asset::create($request->only([
                'asset_code', 'name', 'category', 'brand', 'model', 'serial_number',
                'purchase_date', 'purchase_price', 'location', 'condition',
                'status', 'description', 'supplier', 'warranty_until',
                'assigned_to', 'department'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Asset created successfully',
                'data' => $asset
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific asset
     */
    public function show(int $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $asset
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }
    }

    /**
     * Update asset
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:255',
                'brand' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'purchase_date' => 'nullable|date',
                'purchase_price' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'condition' => 'nullable|in:excellent,good,fair,poor,damaged',
                'status' => 'nullable|in:active,inactive,maintenance,disposed',
                'description' => 'nullable|string',
                'supplier' => 'nullable|string|max:255',
                'warranty_until' => 'nullable|date',
                'assigned_to' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asset->update($request->only([
                'name', 'category', 'brand', 'model', 'serial_number', 
                'purchase_date', 'purchase_price', 'location', 'condition',
                'status', 'description', 'supplier', 'warranty_until',
                'assigned_to', 'department'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully',
                'data' => $asset
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete asset
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);
            
            // Soft delete
            $asset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download asset (Not applicable for restaurant assets)
     */
    public function download(int $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Download not available for restaurant assets'
        ], 404);
    }

    /**
     * Get asset categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Asset::where('status', 'active')
                              ->distinct()
                              ->pluck('category')
                              ->filter()
                              ->sort()
                              ->values();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset types/categories
     */
    public function getTypes(): JsonResponse
    {
        try {
            $categories = Asset::getCategories();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = Asset::getStatistics();
            
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

    /**
     * Determine asset type from MIME type
     */
    private function determineAssetType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        if (in_array($mimeType, $documentMimes)) {
            return 'document';
        }

        return 'file';
    }
}
