<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of packages
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Package::with(['items.product', 'category']);

            // Search
            if ($request->filled('search')) {
                $s = strtolower($request->search);
                $query->where(function($q) use ($s) {
                    $q->whereRaw('LOWER(name) like ?', ["%{$s}%"])
                      ->orWhereRaw('LOWER(description) like ?', ["%{$s}%"])
                      ->orWhereRaw('LOWER(sku) like ?', ["%{$s}%"])
                      ->orWhereRaw('LOWER(barcode) like ?', ["%{$s}%"]);
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by active
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by package type
            if ($request->filled('package_type')) {
                $query->where('package_type', $request->package_type);
            }

            // Filter featured
            if ($request->has('is_featured')) {
                $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
            }

            // Only active packages (for POS)
            if ($request->has('active_only') && filter_var($request->active_only, FILTER_VALIDATE_BOOLEAN)) {
                $query->active();
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            
            if ($request->has('paginate') && !filter_var($request->paginate, FILTER_VALIDATE_BOOLEAN)) {
                $packages = $query->get();
                return $this->successResponse($packages, 'Packages retrieved successfully');
            }

            $packages = $query->paginate($perPage);
            return $this->successResponse($packages, 'Packages retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve packages: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created package
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:packages,sku',
            'barcode' => 'nullable|string|unique:packages,barcode',
            'image' => 'nullable|string',
            'package_type' => 'required|in:fixed,customizable',
            'package_price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id_category',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'status' => 'in:draft,published,archived',
            'stock' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.id_product' => 'required|exists:products,id_product',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.is_optional' => 'boolean',
            'items.*.sort_order' => 'nullable|integer',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            // Calculate regular price first from items
            $regularPrice = 0;
            $itemsData = [];
            
            foreach ($request->items as $index => $itemData) {
                $product = Product::find($itemData['id_product']);
                
                if (!$product) {
                    throw new \Exception("Product with ID {$itemData['id_product']} not found");
                }
                
                $quantity = $itemData['quantity'];
                $unitPrice = $product->price;
                $subtotal = $unitPrice * $quantity;
                
                $itemsData[] = [
                    'id_product' => $itemData['id_product'],
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'] ?? $product->unit ?? 'pcs',
                    'is_optional' => $itemData['is_optional'] ?? false,
                    'sort_order' => $itemData['sort_order'] ?? $index,
                    'notes' => $itemData['notes'] ?? null,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
                
                $regularPrice += $subtotal;
            }

            // Create package with calculated regular_price
            $packageData = $request->only([
                'name', 'description', 'sku', 'barcode', 'image', 
                'package_type', 'package_price', 'category_id',
                'is_active', 'is_featured', 'status', 'stock', 
                'track_stock', 'tags', 'sort_order'
            ]);

            $packageData['slug'] = Str::slug($request->name);
            $packageData['created_by'] = auth()->id();
            $packageData['regular_price'] = $regularPrice;
            $packageData['savings_amount'] = abs($regularPrice - $request->package_price);
            $packageData['savings_percentage'] = $regularPrice > 0 
                ? (abs($regularPrice - $request->package_price) / $regularPrice) * 100 
                : 0;

            $package = Package::create($packageData);

            // Create package items
            foreach ($itemsData as $itemData) {
                $package->items()->create($itemData);
            }

            DB::commit();

            $package->load(['items.product', 'category']);
            return $this->successResponse($package, 'Package created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create package: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified package
     */
    public function show($id): JsonResponse
    {
        try {
            $package = Package::with(['items.product', 'category', 'creator', 'updater'])
                ->findOrFail($id);

            return $this->successResponse($package, 'Package retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Package not found', 404);
        }
    }

    /**
     * Update the specified package
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:packages,sku,' . $id . ',id_package',
            'barcode' => 'nullable|string|unique:packages,barcode,' . $id . ',id_package',
            'image' => 'nullable|string',
            'package_type' => 'in:fixed,customizable',
            'package_price' => 'numeric|min:0',
            'category_id' => 'required|exists:categories,id_category',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'status' => 'in:draft,published,archived',
            'stock' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            
            // Items
            'items' => 'array',
            'items.*.id_product' => 'required|exists:products,id_product',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.is_optional' => 'boolean',
            'items.*.sort_order' => 'nullable|integer',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            $package = Package::findOrFail($id);

            // Update package data
            $packageData = $request->only([
                'name', 'description', 'sku', 'barcode', 'image', 
                'package_type', 'package_price', 'category_id',
                'is_active', 'is_featured', 'status', 'stock', 
                'track_stock', 'tags', 'sort_order'
            ]);

            if (isset($packageData['name'])) {
                $packageData['slug'] = Str::slug($packageData['name']);
            }
            
            $packageData['updated_by'] = auth()->id();
            $package->update($packageData);

            // Update items if provided
            if ($request->has('items')) {
                // Delete old items
                $package->items()->delete();

                // Calculate new items and regular price
                $regularPrice = 0;
                $itemsData = [];
                
                foreach ($request->items as $index => $itemData) {
                    $product = Product::find($itemData['id_product']);
                    
                    if (!$product) {
                        throw new \Exception("Product with ID {$itemData['id_product']} not found");
                    }
                    
                    $quantity = $itemData['quantity'];
                    $unitPrice = $product->price;
                    $subtotal = $unitPrice * $quantity;
                    
                    $itemsData[] = [
                        'id_product' => $itemData['id_product'],
                        'quantity' => $quantity,
                        'unit' => $itemData['unit'] ?? $product->unit ?? 'pcs',
                        'is_optional' => $itemData['is_optional'] ?? false,
                        'sort_order' => $itemData['sort_order'] ?? $index,
                        'notes' => $itemData['notes'] ?? null,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                    
                    $regularPrice += $subtotal;
                }

                // Create new items
                foreach ($itemsData as $itemData) {
                    $package->items()->create($itemData);
                }

                // Recalculate pricing
                $package->regular_price = $regularPrice;
                $package->savings_amount = abs($regularPrice - $package->package_price);
                $package->savings_percentage = $regularPrice > 0 
                    ? (abs($regularPrice - $package->package_price) / $regularPrice) * 100 
                    : 0;
                $package->save();
            }

            DB::commit();

            $package->load(['items.product', 'category']);
            return $this->successResponse($package, 'Package updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update package: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified package
     */
    public function destroy($id): JsonResponse
    {
        try {
            $package = Package::findOrFail($id);
            $package->deleted_by = auth()->id();
            $package->save();
            $package->delete();

            return $this->successResponse(null, 'Package deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete package: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk delete packages
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:packages,id_package',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            Package::whereIn('id_package', $request->ids)
                ->update(['deleted_by' => auth()->id()]);
            
            Package::whereIn('id_package', $request->ids)->delete();

            return $this->successResponse(null, 'Packages deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete packages: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check package availability
     */
    public function checkAvailability($id): JsonResponse
    {
        try {
            $package = Package::with('items.product')->findOrFail($id);
            
            $isAvailable = $package->isAvailable();
            $unavailableItems = [];

            if (!$isAvailable) {
                foreach ($package->items as $item) {
                    if (!$item->product || !$item->product->active || $item->product->stock < $item->quantity) {
                        $unavailableItems[] = [
                            'product_name' => $item->product->name ?? 'Unknown',
                            'required' => $item->quantity,
                            'available' => $item->product->stock ?? 0,
                        ];
                    }
                }
            }

            return $this->successResponse([
                'is_available' => $isAvailable,
                'status' => $package->availability_status,
                'unavailable_items' => $unavailableItems,
            ], 'Availability checked successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Package not found', 404);
        }
    }
}
