<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use App\Traits\TransactionLogTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponseTrait, TransactionLogTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['category', 'creator:id,name,email', 'updater:id,name,email', 'deleter:id,name,email']);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('active')) {
                if ($request->active === 'active') {
                    $query->active();
                } elseif ($request->active === 'inactive') {
                    $query->inactive();
                }
            }

            if ($request->filled('category_id')) {
                $query->byCategory($request->category_id);
            }

            if ($request->filled('stock_status')) {
                switch ($request->stock_status) {
                    case 'in_stock':
                        $query->inStock();
                        break;
                    case 'low_stock':
                        $query->lowStock();
                        break;
                    case 'out_of_stock':
                        $query->outOfStock();
                        break;
                }
            }

            if ($request->filled('featured')) {
                if ($request->boolean('featured')) {
                    $query->featured();
                }
            }

            // Filter by kitchen availability
            if ($request->filled('available_in_kitchen')) {
                $query->where('available_in_kitchen', $request->boolean('available_in_kitchen'));
            }

            // Filter by bar availability
            if ($request->filled('available_in_bar')) {
                $query->where('available_in_bar', $request->boolean('available_in_bar'));
            }

            // Filter by station (kitchen OR bar)
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

            // Filter by status (draft|published|archived) - no validation as requested
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSorts = ['name', 'price', 'cost', 'stock', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            $response = $this->paginatedResponse($products, 'Data produk berhasil diambil');
            $this->logSuccess($request, 'index', 'product', null, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'index', 'product', $e->getMessage());
            return $this->serverErrorResponse('Gagal mengambil data produk');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                // 'price' => 'required|numeric|min:0',
                'cost' => 'nullable|numeric|min:0',
                'min_stock' => 'nullable|integer|min:0',
                'category_id' => 'required|exists:categories,id_category',
                'available_in_kitchen' => 'nullable|in:true,false,1,0',
                'available_in_bar' => 'nullable|in:true,false,1,0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $productData = $request->except(['image']);
            $productData['created_by'] = auth()->id();
            $productData['status'] = $productData['status'] ?? 'published';
            $productData['active'] = $request->boolean('active', true);
            $productData['featured'] = $request->boolean('featured', false);
            $productData['available_in_kitchen'] = $request->boolean('available_in_kitchen', true);
            $productData['available_in_bar'] = $request->boolean('available_in_bar', true);
            $productData['price'] = $request->input('price', 0);

            if (!$request->filled('sku')) {
                $productData['sku'] = $this->generateSKU($request->name);
            }

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('products', $imageName, 'public');
                $productData['image'] = $imageName;
            }

            if ($request->filled('dimensions')) {
                $productData['dimensions'] = $request->dimensions;
            }

            if ($request->filled('tags')) {
                $productData['tags'] = $request->tags;
            }

            $product = Product::create($productData);
            $product->load(['category', 'creator']);

            $response = $this->createdResponse($product, 'Produk berhasil dibuat');
            $this->logSuccess($request, 'store', 'product', $product->id_product, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'store', 'product', $e->getMessage());
            return $this->serverErrorResponse('Gagal membuat produk: ' . $e->getMessage());
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::with(['category', 'creator', 'updater'])->findOrFail($id);
            $response = $this->successResponse($product, 'Data produk berhasil diambil');
            $this->logSuccess($request, 'show', 'product', $id, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'show', 'product', $e->getMessage(), $id);
            return $this->notFoundResponse('Produk tidak ditemukan');
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

             $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'nullable|numeric|min:0', // Changed from required to nullable
                'cost' => 'nullable|numeric|min:0',
                'min_stock' => 'nullable|integer|min:0',
                'category_id' => 'required|exists:categories,id_category',
                'available_in_kitchen' => 'nullable|in:true,false,1,0',
                'available_in_bar' => 'nullable|in:true,false,1,0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $productData = $request->except(['image']);
            $productData['updated_by'] = auth()->id();
            $productData['active'] = $request->boolean('active', $product->active);
            $productData['featured'] = $request->boolean('featured', $product->featured);
            $productData['available_in_kitchen'] = $request->boolean('available_in_kitchen', $product->available_in_kitchen);
            $productData['available_in_bar'] = $request->boolean('available_in_bar', $product->available_in_bar);

            // Preserve existing price if not provided in request
            if (!$request->has('price')) {
                $productData['price'] = $product->price;
            }

            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                    Storage::disk('public')->delete('products/' . $product->image);
                }
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('products', $imageName, 'public');
                $productData['image'] = $imageName;
            }

            if ($request->filled('dimensions')) {
                $productData['dimensions'] = $request->dimensions;
            }

            if ($request->filled('tags')) {
                $productData['tags'] = $request->tags;
            }

            $product->update($productData);
            $product->load(['category', 'creator', 'updater']);

            $response = $this->updatedResponse($product, 'Produk berhasil diperbarui');
            $this->logSuccess($request, 'update', 'product', $id, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'update', 'product', $e->getMessage(), $id);
            return $this->serverErrorResponse('Gagal memperbarui produk: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                Storage::disk('public')->delete('products/' . $product->image);
            }

            $product->deleted_by = auth()->id();
            $product->save();
            $product->delete();

            $response = $this->deletedResponse('Produk berhasil dihapus');
            $this->logSuccess($request, 'destroy', 'product', $id, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'destroy', 'product', $e->getMessage(), $id);
            return $this->serverErrorResponse('Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    public function toggleActive(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $query = Product::with(['category', 'creator:id,name,email', 'updater:id,name,email', 'deleter:id,name,email']);
            $product->active = !$product->active;
            $product->updated_by = auth()->id();
            $product->save();

            $status = $product->active ? 'diaktifkan' : 'dinonaktifkan';
            $response = $this->successResponse($query->find($id), "Produk berhasil {$status}");
            $this->logSuccess($request, 'toggleActive', 'product', $id, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'toggleActive', 'product', $e->getMessage(), $id);
            return $this->serverErrorResponse('Gagal mengubah status produk: ' . $e->getMessage());
        }
    }

    public function toggleFeatured(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->featured = !$product->featured;
            $product->updated_by = auth()->id();
            $product->save();

            $status = $product->featured ? 'ditampilkan' : 'disembunyikan';
            $response = $this->successResponse($product, "Produk berhasil {$status} dari produk unggulan");
            $this->logSuccess($request, 'toggleFeatured', 'product', $id, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'toggleFeatured', 'product', $e->getMessage(), $id);
            return $this->serverErrorResponse('Gagal mengubah status produk unggulan: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:products,id_product'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $products = Product::whereIn('id_product', $request->ids)->get();

            foreach ($products as $product) {
                if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                    Storage::disk('public')->delete('products/' . $product->image);
                }
                $product->delete();
            }

            $response = $this->deletedResponse(count($request->ids) . ' produk berhasil dihapus');
            $this->logSuccess($request, 'bulkDelete', 'product', $request->ids, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'bulkDelete', 'product', $e->getMessage());
            return $this->serverErrorResponse('Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'published_products' => Product::byStatus('published')->count(),
                'draft_products' => Product::byStatus('draft')->count(),
                'archived_products' => Product::byStatus('archived')->count(),
                'active_products' => Product::active()->count(),
                'inactive_products' => Product::inactive()->count(),
                'featured_products' => Product::featured()->count(),
                'low_stock_products' => Product::lowStock()->count(),
                'out_of_stock_products' => Product::outOfStock()->count(),
                'total_stock_value' => 0, // Temporarily disabled - stock moved to separate table
                // TODO: Calculate from product_items or inventory table
                'average_price' => (float) (Product::avg('price') ?? 0),
                'total_categories' => Product::distinct('category_id')->whereNotNull('category_id')->count(),
                'average_margin' => (float) ceil(Product::whereNotNull('cost')->where('cost', '>', 0)->selectRaw('AVG(((price - cost) / cost) * 100) as avg_margin')->first()->avg_margin ?? 0),
                'highest_margin' => (float) ceil(Product::whereNotNull('cost')->where('cost', '>', 0)->selectRaw('MAX(((price - cost) / cost) * 100) as max_margin')->first()->max_margin ?? 0),
                'lowest_margin' => (float) ceil(Product::whereNotNull('cost')->where('cost', '>', 0)->selectRaw('MIN(((price - cost) / cost) * 100) as min_margin')->first()->min_margin ?? 0),
                'profitable_products' => Product::whereNotNull('cost')->where('cost', '>', 0)->whereRaw('price > cost')->count(),
            ];

            $response = $this->successResponse($stats, 'Statistik produk berhasil diambil');
            $this->logSuccess($request, 'stats', 'product', null, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, 'stats', 'product', $e->getMessage());
            return $this->serverErrorResponse('Gagal mengambil statistik produk: ' . $e->getMessage());
        }
    }

    private function generateSKU($productName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productName), 0, 3));
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(3));
        return $prefix . $timestamp . $random;
    }
}
