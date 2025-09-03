<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Supplier::query()->with(['creator', 'updater']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('city')) {
                $query->where('city', 'like', "%{$request->city}%");
            }

            if ($request->filled('province')) {
                $query->where('province', 'like', "%{$request->province}%");
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            
            // Handle 'all' parameter to return all suppliers without pagination
            if ($perPage === 'all') {
                $allSuppliers = $query->get();
                return $this->successResponse($allSuppliers, 'All suppliers retrieved successfully');
            }
            
            $suppliers = $query->paginate($perPage);

            return $this->paginatedResponse($suppliers, 'Suppliers retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve suppliers: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:suppliers,code',
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $supplier = Supplier::create([
                ...$request->only([
                    'code', 'name', 'email', 'phone', 'address', 'city', 'province',
                    'postal_code', 'contact_person', 'tax_number', 'bank_name',
                    'bank_account', 'bank_account_name', 'notes', 'status'
                ]),
                'created_by' => Auth::id(),
            ]);

            $supplier->load(['creator', 'updater']);

            return $this->createdResponse($supplier, 'Supplier created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create supplier: ' . $e->getMessage());
        }
    }

    public function show(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->load(['creator', 'updater', 'deleter']);

            return $this->successResponse($supplier, 'Supplier retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve supplier: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $supplier->update([
                ...$request->only([
                    'name', 'email', 'phone', 'address', 'city', 'province',
                    'postal_code', 'contact_person', 'tax_number', 'bank_name',
                    'bank_account', 'bank_account_name', 'notes', 'status'
                ]),
                'updated_by' => Auth::id(),
            ]);

            $supplier->load(['creator', 'updater']);

            return $this->successResponse($supplier, 'Supplier updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update supplier: ' . $e->getMessage());
        }
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->update(['deleted_by' => Auth::id()]);
            $supplier->delete();

            return $this->deletedResponse('Supplier deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete supplier: ' . $e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_suppliers' => Supplier::count(),
                'active_suppliers' => Supplier::where('status', 'active')->count(),
                'inactive_suppliers' => Supplier::where('status', 'inactive')->count(),
                'suppliers_with_purchases' => 0,
                'total_cities' => Supplier::whereNotNull('city')->distinct('city')->count(),
                'total_provinces' => Supplier::whereNotNull('province')->distinct('province')->count(),
            ];

            return $this->successResponse($stats, 'Supplier statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve supplier statistics: ' . $e->getMessage());
        }
    }

    public function cities(): JsonResponse
    {
        try {
            $cities = Supplier::whereNotNull('city')
                             ->distinct()
                             ->pluck('city')
                             ->sort()
                             ->values();

            return $this->successResponse($cities, 'Cities retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cities: ' . $e->getMessage());
        }
    }

    public function provinces(): JsonResponse
    {
        try {
            $provinces = Supplier::whereNotNull('province')
                                ->distinct()
                                ->pluck('province')
                                ->sort()
                                ->values();

            return $this->successResponse($provinces, 'Provinces retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve provinces: ' . $e->getMessage());
        }
    }
}
