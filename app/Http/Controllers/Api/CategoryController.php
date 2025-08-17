<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use App\Traits\TransactionLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponseTrait, TransactionLogTrait;

    public function index(Request $request)
    {
        try {
            $query = Category::with(['createdBy:id,name', 'updatedBy:id,name']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 10);
            $categories = $query->paginate($perPage);

            $response = $this->paginatedResponse($categories);
            $this->logSuccess($request, 'index', 'category', null, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'index', 'category', $e->getMessage());
            return $this->serverErrorResponse('Gagal mengambil data kategori: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name,NULL,id_category,deleted_at,NULL',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->only(['name', 'description', 'active']);
            $data['created_by'] = auth()->id();
            $data['active'] = $request->get('active', true);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('categories', $imageName, 'public');
                $data['image'] = $imageName;
            }

            $category = Category::create($data);
            $category->load(['createdBy:id,name', 'updatedBy:id,name']);

            $response = $this->createdResponse($category, 'Kategori berhasil ditambahkan');
            $this->logSuccess($request, 'store', 'category', $category->id_category, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'store', 'category', $e->getMessage());
            return $this->serverErrorResponse('Gagal menambahkan kategori: ' . $e->getMessage());
        }
    }

    public function show(Request $request, Category $category)
    {
        try {
            $category->load(['createdBy:id,name', 'updatedBy:id,name']);
            $response = $this->successResponse($category, 'Data kategori berhasil diambil');
            $this->logSuccess($request, 'show', 'category', $category->id_category, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'show', 'category', $e->getMessage(), $category->id_category);
            return $this->serverErrorResponse('Gagal mengambil data kategori: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Category $category)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->only(['name', 'description', 'active']);
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('image')) {
                if ($category->image && Storage::exists('public/categories/' . $category->image)) {
                    Storage::delete('public/categories/' . $category->image);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/categories', $imageName);
                $data['image'] = $imageName;
            }

            $category->update($data);
            $category->load(['createdBy:id,name', 'updatedBy:id,name']);

            $response = $this->updatedResponse($category, 'Kategori berhasil diperbarui');
            $this->logSuccess($request, 'update', 'category', $category->id_category, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'update', 'category', $e->getMessage(), $category->id_category);
            return $this->serverErrorResponse('Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Category $category)
    {
        try {
            if ($category->image && Storage::exists('public/categories/' . $category->image)) {
                Storage::delete('public/categories/' . $category->image);
            }

            $category->delete();

            $response = $this->deletedResponse('Kategori berhasil dihapus');
            $this->logSuccess($request, 'destroy', 'category', $category->id_category, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'destroy', 'category', $e->getMessage(), $category->id_category);
            return $this->serverErrorResponse('Gagal menghapus kategori: ' . $e->getMessage());
        }
    }

    public function toggleActive(Request $request, Category $category)
    {
        try {
            $category->update([
                'active' => !$category->active,
                'updated_by' => auth()->id()
            ]);

            $status = $category->active ? 'diaktifkan' : 'dinonaktifkan';
            $response = $this->successResponse($category, "Kategori berhasil {$status}");
            $this->logSuccess($request, 'toggleActive', 'category', $category->id_category, $response);

            return $response;

        } catch (\Exception $e) {
            $this->logError($request, 'toggleActive', 'category', $e->getMessage(), $category->id_category);
            return $this->serverErrorResponse('Gagal mengubah status kategori: ' . $e->getMessage());
        }
    }
}
