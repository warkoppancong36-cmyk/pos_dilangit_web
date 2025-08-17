<?php

namespace App\Http\Controllers\Api;

use App\Models\Discount;
use App\Http\Requests\DiscountRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiscountController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Discount::with(['createdBy:id,name', 'updatedBy:id,name']);

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                switch ($request->status) {
                    case 'active':
                        $query->active()->valid();
                        break;
                    case 'inactive':
                        $query->where('active', false);
                        break;
                    case 'expired':
                        $query->where('valid_until', '<', now());
                        break;
                    case 'scheduled':
                        $query->where('valid_from', '>', now());
                        break;
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $discounts = $query->paginate($perPage);

            // Add computed attributes
            $discounts->getCollection()->transform(function ($discount) {
                $discount->status = $discount->status;
                $discount->formatted_value = $discount->formatted_value;
                $discount->remaining_usage = $discount->remaining_usage;
                return $discount;
            });

            return $this->successResponse($discounts, 'Daftar discount berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar discount: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:discounts,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:percentage,fixed_amount,buy_x_get_y',
                'value' => 'required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'maximum_discount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'usage_limit_per_customer' => 'nullable|integer|min:1',
                'valid_from' => 'required|date',
                'valid_until' => 'required|date|after:valid_from',
                'active' => 'boolean',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'integer|exists:products,id_product',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'integer|exists:categories,id_category',
                'customer_groups' => 'nullable|array',
                'conditions' => 'nullable|array'
            ]);

            // Validation rules based on type
            if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
                throw ValidationException::withMessages([
                    'value' => 'Percentage discount cannot be more than 100%'
                ]);
            }

            $validated['created_by'] = Auth::id();

            DB::beginTransaction();

            $discount = Discount::create($validated);

            DB::commit();

            $discount->load(['createdBy:id,name']);
            $discount->status = $discount->status;
            $discount->formatted_value = $discount->formatted_value;

            return $this->successResponse($discount, 'Discount berhasil dibuat', 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membuat discount: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $discount = Discount::with(['createdBy:id,name', 'updatedBy:id,name'])
                              ->findOrFail($id);

            $discount->status = $discount->status;
            $discount->formatted_value = $discount->formatted_value;
            $discount->remaining_usage = $discount->remaining_usage;

            return $this->successResponse($discount, 'Detail discount berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Discount tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:discounts,code,' . $id . ',id_discount',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:percentage,fixed_amount,buy_x_get_y',
                'value' => 'required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'maximum_discount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'usage_limit_per_customer' => 'nullable|integer|min:1',
                'valid_from' => 'required|date',
                'valid_until' => 'required|date|after:valid_from',
                'active' => 'boolean',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'integer|exists:products,id_product',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'integer|exists:categories,id_category',
                'customer_groups' => 'nullable|array',
                'conditions' => 'nullable|array'
            ]);

            // Validation rules based on type
            if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
                throw ValidationException::withMessages([
                    'value' => 'Percentage discount cannot be more than 100%'
                ]);
            }

            $validated['updated_by'] = Auth::id();

            DB::beginTransaction();

            $discount->update($validated);

            DB::commit();

            $discount->load(['createdBy:id,name', 'updatedBy:id,name']);
            $discount->status = $discount->status;
            $discount->formatted_value = $discount->formatted_value;

            return $this->successResponse($discount, 'Discount berhasil diperbarui');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memperbarui discount: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            // Check if discount is being used
            if ($discount->used_count > 0) {
                return $this->errorResponse('Cannot delete discount that has been used', 400);
            }

            DB::beginTransaction();

            $discount->delete();

            DB::commit();

            return $this->successResponse(null, 'Discount berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menghapus discount: ' . $e->getMessage(), 500);
        }
    }

    public function validateCode(Request $request): JsonResponse
    {
        try {
            $code = $request->input('code');
            $orderTotal = $request->input('order_total', 0);
            $customerId = $request->input('customer_id');

            if (!$code) {
                return $this->errorResponse('Discount code is required', 400);
            }

            $discount = Discount::where('code', $code)->first();

            if (!$discount) {
                return $this->errorResponse('Invalid discount code', 404);
            }

            if (!$discount->canBeUsedBy($customerId, $orderTotal)) {
                $message = 'Discount code cannot be used';
                
                if (!$discount->isValid()) {
                    $message = 'Discount code is expired or inactive';
                } elseif ($discount->minimum_amount && $orderTotal < $discount->minimum_amount) {
                    $message = "Minimum order amount is Rp " . number_format($discount->minimum_amount, 0, ',', '.');
                } elseif ($discount->isUsageLimitReached()) {
                    $message = 'Discount usage limit has been reached';
                }

                return $this->errorResponse($message, 400);
            }

            $discountAmount = $discount->calculateDiscount($orderTotal);

            return $this->successResponse([
                'discount' => $discount,
                'discount_amount' => $discountAmount,
                'formatted_discount' => 'Rp ' . number_format($discountAmount, 0, ',', '.')
            ], 'Discount code is valid');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to validate discount code: ' . $e->getMessage(), 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_discounts' => Discount::count(),
                'active_discounts' => Discount::active()->valid()->count(),
                'expired_discounts' => Discount::where('valid_until', '<', now())->count(),
                'scheduled_discounts' => Discount::where('valid_from', '>', now())->count(),
                'total_usage' => Discount::sum('used_count'),
                'most_used_discount' => Discount::orderBy('used_count', 'desc')->first(),
                'discount_types' => Discount::select('type', DB::raw('count(*) as count'))
                                         ->groupBy('type')
                                         ->get()
            ];

            return $this->successResponse($stats, 'Discount statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get discount statistics: ' . $e->getMessage(), 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            DB::beginTransaction();

            $discount->update([
                'active' => !$discount->active,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return $this->successResponse([
                'active' => $discount->active
            ], 'Discount status updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update discount status: ' . $e->getMessage(), 500);
        }
    }

    public function duplicate($id): JsonResponse
    {
        try {
            $originalDiscount = Discount::findOrFail($id);

            DB::beginTransaction();

            $newDiscount = $originalDiscount->replicate();
            $newDiscount->code = $originalDiscount->code . '_COPY';
            $newDiscount->name = $originalDiscount->name . ' (Copy)';
            $newDiscount->used_count = 0;
            $newDiscount->created_by = Auth::id();
            $newDiscount->updated_by = null;
            $newDiscount->save();

            DB::commit();

            $newDiscount->load(['createdBy:id,name']);
            $newDiscount->status = $newDiscount->status;

            return $this->successResponse($newDiscount, 'Discount duplicated successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to duplicate discount: ' . $e->getMessage(), 500);
        }
    }
}
