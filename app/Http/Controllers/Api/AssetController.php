<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * Get all assets with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Asset::query();

            // Apply filters
            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }

            if ($request->has('location') && $request->location) {
                $query->byLocation($request->location);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('condition') && $request->condition) {
                $query->byCondition($request->condition);
            }

            if ($request->has('department') && $request->department) {
                $query->where('department', $request->department);
            }

            if ($request->has('assigned_only') && $request->boolean('assigned_only')) {
                $query->assigned();
            }

            if ($request->has('unassigned_only') && $request->boolean('unassigned_only')) {
                $query->unassigned();
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('asset_code', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $assets = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $assets->items(),
                'pagination' => [
                    'current_page' => $assets->currentPage(),
                    'last_page' => $assets->lastPage(),
                    'per_page' => $assets->perPage(),
                    'total' => $assets->total(),
                    'from' => $assets->firstItem(),
                    'to' => $assets->lastItem(),
                ],
                'filters' => [
                    'categories' => Asset::getCategories(),
                    'locations' => Asset::getLocations(),
                    'departments' => Asset::getDepartments(),
                    'conditions' => Asset::CONDITIONS,
                    'statuses' => Asset::STATUSES,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new asset
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'asset_code' => 'nullable|string|max:20|unique:assets,asset_code',
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100|unique:assets,serial_number',
                'purchase_date' => 'nullable|date',
                'purchase_price' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'condition' => 'required|in:' . implode(',', Asset::CONDITIONS),
                'status' => 'required|in:' . implode(',', Asset::STATUSES),
                'description' => 'nullable|string|max:1000',
                'supplier' => 'nullable|string|max:255',
                'warranty_until' => 'nullable|date|after:today',
                'assigned_to' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Generate asset code if not provided
            if (!isset($data['asset_code']) || empty($data['asset_code'])) {
                $data['asset_code'] = $this->generateUniqueAssetCode($data['category']);
            }

            $asset = Asset::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil dibuat',
                'data' => $asset
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific asset
     */
    public function show($id): JsonResponse
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
                'message' => 'Asset not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update asset
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'asset_code' => 'nullable|string|max:20|unique:assets,asset_code,' . $id,
                'name' => 'sometimes|required|string|max:255',
                'category' => 'sometimes|required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100|unique:assets,serial_number,' . $id,
                'purchase_date' => 'nullable|date',
                'purchase_price' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'condition' => 'sometimes|required|in:' . implode(',', Asset::CONDITIONS),
                'status' => 'sometimes|required|in:' . implode(',', Asset::STATUSES),
                'description' => 'nullable|string|max:1000',
                'supplier' => 'nullable|string|max:255',
                'warranty_until' => 'nullable|date',
                'assigned_to' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asset->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil diupdate',
                'data' => $asset->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete asset
     */
    public function destroy($id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);
            $asset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete assets
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'asset_ids' => 'required|array',
                'asset_ids.*' => 'integer|exists:assets,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = Asset::whereIn('id', $request->asset_ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} assets",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset statistics
     */
    public function statistics(): JsonResponse
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
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assets by category
     */
    public function byCategory($category): JsonResponse
    {
        try {
            $assets = Asset::byCategory($category)->get();

            return response()->json([
                'success' => true,
                'data' => $assets
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get assets by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change asset status
     */
    public function changeStatus(Request $request, $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . implode(',', Asset::STATUSES),
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $old_status = $asset->status;
            $asset->status = $request->status;
            
            if ($request->notes) {
                $asset->description = $asset->description . "\n[" . now()->format('Y-m-d H:i:s') . "] Status changed from {$old_status} to {$request->status}: " . $request->notes;
            }
            
            $asset->save();

            return response()->json([
                'success' => true,
                'message' => 'Status asset berhasil diubah',
                'data' => $asset
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change asset status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign asset to someone
     */
    public function assign(Request $request, $id): JsonResponse
    {
        try {
            $asset = Asset::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'assigned_to' => 'required|string|max:255',
                'department' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $old_assigned = $asset->assigned_to;
            $asset->assigned_to = $request->assigned_to;
            
            if ($request->department) {
                $asset->department = $request->department;
            }

            if ($request->notes) {
                $asset->description = $asset->description . "\n[" . now()->format('Y-m-d H:i:s') . "] Assigned from '{$old_assigned}' to '{$request->assigned_to}': " . $request->notes;
            }
            
            $asset->save();

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil di-assign',
                'data' => $asset
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance schedule (assets with warranty expiring soon)
     */
    public function maintenanceSchedule(): JsonResponse
    {
        try {
            $next_month = now()->addMonth();
            
            $expiring_warranties = Asset::where('warranty_until', '<=', $next_month)
                ->where('warranty_until', '>=', now())
                ->where('status', 'active')
                ->orderBy('warranty_until')
                ->get();

            $maintenance_needed = Asset::where('status', 'maintenance')
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'expiring_warranties' => $expiring_warranties,
                    'maintenance_needed' => $maintenance_needed,
                    'summary' => [
                        'expiring_count' => $expiring_warranties->count(),
                        'maintenance_count' => $maintenance_needed->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get maintenance schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available asset categories
     */
    public function getCategories(): JsonResponse
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
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available asset locations
     */
    public function getLocations(): JsonResponse
    {
        try {
            $locations = Asset::getLocations();
            
            return response()->json([
                'success' => true,
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique asset code based on category
     */
    private function generateUniqueAssetCode(string $category): string
    {
        // Category code mapping for better codes
        $categoryMappings = [
            'Kitchen Equipment' => 'KIT',
            'Electronics' => 'ELC',
            'Furniture' => 'FUR',
            'Beverage Equipment' => 'BEV',
            'Office Equipment' => 'OFF',
            'Cleaning Equipment' => 'CLN',
            'Security Equipment' => 'SEC',
            'Maintenance Equipment' => 'MNT',
            'Computer Equipment' => 'CMP',
            'Vehicle' => 'VEH',
            'Tools' => 'TOL',
            'Other' => 'OTH',
        ];
        
        // Get category code from mapping or generate from category name
        $categoryCode = $categoryMappings[$category] ?? strtoupper(substr(str_replace(' ', '', $category), 0, 3));
        
        // If category is shorter than 3 chars, pad with 'X'
        if (strlen($categoryCode) < 3) {
            $categoryCode = str_pad($categoryCode, 3, 'X', STR_PAD_RIGHT);
        }
        
        // Find the highest existing number for this category (including soft deleted)
        $lastAsset = Asset::withTrashed()
            ->where('asset_code', 'like', $categoryCode . '-%')
            ->orderByRaw('CAST(SUBSTRING(asset_code, ' . (strlen($categoryCode) + 2) . ') AS UNSIGNED) DESC')
            ->first();
        
        $nextNumber = 1;
        if ($lastAsset) {
            $lastNumber = (int) substr($lastAsset->asset_code, strlen($categoryCode) + 1);
            $nextNumber = $lastNumber + 1;
        }
        
        // Generate code and check for uniqueness with retry mechanism (including soft deleted)
        $maxRetries = 1000; // Prevent infinite loop
        $attempts = 0;
        
        do {
            $assetCode = $categoryCode . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = Asset::withTrashed()->where('asset_code', $assetCode)->exists();
            
            if ($exists) {
                $nextNumber++;
                $attempts++;
            }
            
            if ($attempts > $maxRetries) {
                // Fallback to timestamp-based code
                $assetCode = $categoryCode . '-' . time();
                break;
            }
        } while ($exists);
        
        return $assetCode;
    }
}
