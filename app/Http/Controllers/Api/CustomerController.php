<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::query()
                ->with(['creator', 'updater']);

            // Search functionality
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            }

            // Filter by gender
            if ($request->filled('gender')) {
                $query->byGender($request->gender);
            }

            // Filter by city
            if ($request->filled('city')) {
                $query->byCity($request->city);
            }

            // Filter by loyalty level
            if ($request->filled('loyalty_level')) {
                $query->byLoyaltyLevel($request->loyalty_level);
            }

            // Filter recent customers
            if ($request->filled('recent_days')) {
                $query->recentCustomers($request->recent_days);
            }

            // Filter frequent customers
            if ($request->filled('min_visits')) {
                $query->frequentCustomers($request->min_visits);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'email', 'phone', 'total_visits', 'total_spent', 'last_visit', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $customers = $query->paginate($perPage);

            return $this->paginatedResponse($customers, 'Customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customers: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:20|unique:customers,phone',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'preferences' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'preferences' => $request->preferences,
                'notes' => $request->notes,
                'active' => $request->get('active', true),
                'created_by' => Auth::id(),
            ]);

            $customer->load(['creator']);

            return $this->createdResponse($customer, 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create customer: ' . $e->getMessage());
        }
    }

    public function show(Customer $customer): JsonResponse
    {
        try {
            $customer->load(['creator', 'updater', 'deleter', 'orders' => function($query) {
                $query->latest()->take(5);
            }]);

            return $this->successResponse($customer, 'Customer retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id_customer . ',id_customer',
            'phone' => 'nullable|string|max:20|unique:customers,phone,' . $customer->id_customer . ',id_customer',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'preferences' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $customer->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'preferences' => $request->preferences,
                'notes' => $request->notes,
                'active' => $request->get('active', $customer->active),
                'updated_by' => Auth::id(),
            ]);

            $customer->load(['creator', 'updater']);

            return $this->successResponse($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update customer: ' . $e->getMessage());
        }
    }

    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->update(['deleted_by' => Auth::id()]);
            $customer->delete();

            return $this->deletedResponse('Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete customer: ' . $e->getMessage());
        }
    }

    public function toggleActive(Customer $customer): JsonResponse
    {
        try {
            $customer->update([
                'active' => !$customer->active,
                'updated_by' => Auth::id(),
            ]);

            $status = $customer->active ? 'activated' : 'deactivated';
            return $this->successResponse($customer, "Customer {$status} successfully");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to toggle customer status: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id_customer'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $customers = Customer::whereIn('id_customer', $request->ids);
            $count = $customers->count();
            
            $customers->update(['deleted_by' => Auth::id()]);
            $customers->delete();

            return $this->deletedResponse("{$count} customers deleted successfully");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete customers: ' . $e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::active()->count(),
                'inactive_customers' => Customer::inactive()->count(),
                'recent_customers' => Customer::recentCustomers(30)->count(),
                'frequent_customers' => Customer::frequentCustomers(5)->count(),
                'total_spent_all' => (float) Customer::sum('total_spent'),
                'average_spent_per_customer' => (float) Customer::avg('total_spent'),
                'total_visits_all' => Customer::sum('total_visits'),
                'average_visits_per_customer' => (float) Customer::avg('total_visits'),
                'customers_by_loyalty' => [
                    'platinum' => Customer::byLoyaltyLevel('platinum')->count(),
                    'gold' => Customer::byLoyaltyLevel('gold')->count(),
                    'silver' => Customer::byLoyaltyLevel('silver')->count(),
                    'bronze' => Customer::byLoyaltyLevel('bronze')->count(),
                    'basic' => Customer::byLoyaltyLevel('basic')->count(),
                ],
                'customers_by_gender' => [
                    'male' => Customer::byGender('male')->count(),
                    'female' => Customer::byGender('female')->count(),
                    'other' => Customer::byGender('other')->count(),
                    'unspecified' => Customer::whereNull('gender')->count(),
                ],
                'top_cities' => Customer::select('city')
                    ->whereNotNull('city')
                    ->groupBy('city')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(5)
                    ->pluck('city')
                    ->toArray(),
            ];

            return $this->successResponse($stats, 'Customer statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer statistics: ' . $e->getMessage());
        }
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        try {
            $search = $request->get('q', '');
            
            if (strlen($search) < 2) {
                return $this->successResponse([], 'Search query too short');
            }

            $customers = Customer::active()
                ->search($search)
                ->select('id_customer', 'name', 'email', 'phone', 'customer_code')
                ->limit(10)
                ->get();

            return $this->successResponse($customers, 'Customer suggestions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer suggestions: ' . $e->getMessage());
        }
    }
}
