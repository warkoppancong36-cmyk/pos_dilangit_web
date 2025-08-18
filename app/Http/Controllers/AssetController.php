<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Asset::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by condition
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Filter by assigned_to
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', 'like', "%{$request->assigned_to}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'asset_code');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $assets = $query->paginate($perPage);

        return response()->json($assets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'asset_code' => 'required|string|unique:assets,asset_code|max:255',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'location' => 'required|string|max:255',
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
            'warranty_until' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('assets/images', 'public');
            $validatedData['image_url'] = Storage::url($imagePath);
        }

        $asset = Asset::create($validatedData);

        return response()->json([
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset): JsonResponse
    {
        return response()->json([
            'data' => $asset
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'location' => 'sometimes|required|string|max:255',
            'condition' => ['sometimes', 'required', Rule::in(Asset::CONDITIONS)],
            'status' => ['sometimes', 'required', Rule::in(Asset::STATUSES)],
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
            'warranty_until' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($asset->image_url) {
                $oldImagePath = str_replace('/storage/', '', $asset->image_url);
                Storage::disk('public')->delete($oldImagePath);
            }

            $imagePath = $request->file('image')->store('assets/images', 'public');
            $validatedData['image_url'] = Storage::url($imagePath);
        }

        $asset->update($validatedData);

        return response()->json([
            'message' => 'Asset updated successfully',
            'data' => $asset->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset): JsonResponse
    {
        // Delete image if exists
        if ($asset->image_url) {
            $imagePath = str_replace('/storage/', '', $asset->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $asset->delete();

        return response()->json([
            'message' => 'Asset deleted successfully'
        ]);
    }

    /**
     * Bulk delete assets
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'integer|exists:assets,id'
        ]);

        $assets = Asset::whereIn('id', $request->asset_ids)->get();

        // Delete images
        foreach ($assets as $asset) {
            if ($asset->image_url) {
                $imagePath = str_replace('/storage/', '', $asset->image_url);
                Storage::disk('public')->delete($imagePath);
            }
        }

        Asset::whereIn('id', $request->asset_ids)->delete();

        return response()->json([
            'message' => 'Assets deleted successfully',
            'deleted_count' => count($request->asset_ids)
        ]);
    }

    /**
     * Get asset statistics
     */
    public function stats(): JsonResponse
    {
        $stats = Asset::getStatistics();
        
        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get categories
     */
    public function categories(): JsonResponse
    {
        $categories = Asset::getCategories();
        
        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get locations
     */
    public function locations(): JsonResponse
    {
        $locations = Asset::getLocations();
        
        return response()->json([
            'data' => $locations
        ]);
    }

    /**
     * Get departments
     */
    public function departments(): JsonResponse
    {
        $departments = Asset::getDepartments();
        
        return response()->json([
            'data' => $departments
        ]);
    }
}
