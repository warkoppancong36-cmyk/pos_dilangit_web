<?php

namespace App\Http\Controllers\Api;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Promotion::with(['createdBy:id,name', 'updatedBy:id,name']);

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
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
            $sortBy = $request->get('sort_by', 'priority');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'priority') {
                $query->byPriority();
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $promotions = $query->paginate($perPage);

            // Add computed attributes
            $promotions->getCollection()->transform(function ($promotion) {
                $promotion->status = $promotion->status;
                $promotion->formatted_discount = $promotion->formatted_discount;
                $promotion->valid_days_text = $promotion->valid_days_text;
                $promotion->valid_time_text = $promotion->valid_time_text;
                return $promotion;
            });

            return $this->successResponse($promotions, 'Daftar promosi berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar promosi: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:happy_hour,buy_one_get_one,combo_deal,member_discount,seasonal',
                'promotion_rules' => 'required|array',
                'discount_value' => 'nullable|numeric|min:0',
                'discount_type' => 'nullable|in:percentage,fixed_amount',
                'valid_from' => 'required|date',
                'valid_until' => 'required|date|after:valid_from',
                'valid_days' => 'nullable|array',
                'valid_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'valid_time_from' => 'nullable|date_format:H:i',
                'valid_time_until' => 'nullable|date_format:H:i|after:valid_time_from',
                'active' => 'boolean',
                'priority' => 'integer|min:0|max:100',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'integer|exists:products,id_product',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'integer|exists:categories,id_category',
                'conditions' => 'nullable|array',
                'banner_image' => 'nullable|string'
            ]);

            // Validation for discount value based on type
            if (isset($validated['discount_type']) && $validated['discount_type'] === 'percentage' 
                && isset($validated['discount_value']) && $validated['discount_value'] > 100) {
                throw ValidationException::withMessages([
                    'discount_value' => 'Percentage discount cannot be more than 100%'
                ]);
            }

            $validated['created_by'] = Auth::id();

            DB::beginTransaction();

            $promotion = Promotion::create($validated);

            DB::commit();

            $promotion->load(['createdBy:id,name']);
            $promotion->status = $promotion->status;
            $promotion->formatted_discount = $promotion->formatted_discount;

            return $this->successResponse($promotion, 'Promosi berhasil dibuat', 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membuat promosi: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $promotion = Promotion::with(['createdBy:id,name', 'updatedBy:id,name'])
                                 ->findOrFail($id);

            $promotion->status = $promotion->status;
            $promotion->formatted_discount = $promotion->formatted_discount;
            $promotion->valid_days_text = $promotion->valid_days_text;
            $promotion->valid_time_text = $promotion->valid_time_text;

            return $this->successResponse($promotion, 'Detail promosi berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Promosi tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $promotion = Promotion::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:happy_hour,buy_one_get_one,combo_deal,member_discount,seasonal',
                'promotion_rules' => 'required|array',
                'discount_value' => 'nullable|numeric|min:0',
                'discount_type' => 'nullable|in:percentage,fixed_amount',
                'valid_from' => 'required|date',
                'valid_until' => 'required|date|after:valid_from',
                'valid_days' => 'nullable|array',
                'valid_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'valid_time_from' => 'nullable|date_format:H:i',
                'valid_time_until' => 'nullable|date_format:H:i|after:valid_time_from',
                'active' => 'boolean',
                'priority' => 'integer|min:0|max:100',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'integer|exists:products,id_product',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'integer|exists:categories,id_category',
                'conditions' => 'nullable|array',
                'banner_image' => 'nullable|string'
            ]);

            // Validation for discount value based on type
            if (isset($validated['discount_type']) && $validated['discount_type'] === 'percentage' 
                && isset($validated['discount_value']) && $validated['discount_value'] > 100) {
                throw ValidationException::withMessages([
                    'discount_value' => 'Percentage discount cannot be more than 100%'
                ]);
            }

            $validated['updated_by'] = Auth::id();

            DB::beginTransaction();

            $promotion->update($validated);

            DB::commit();

            $promotion->load(['createdBy:id,name', 'updatedBy:id,name']);
            $promotion->status = $promotion->status;
            $promotion->formatted_discount = $promotion->formatted_discount;

            return $this->successResponse($promotion, 'Promosi berhasil diperbarui');

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memperbarui promosi: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $promotion = Promotion::findOrFail($id);

            DB::beginTransaction();

            $promotion->delete();

            DB::commit();

            return $this->successResponse(null, 'Promosi berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menghapus promosi: ' . $e->getMessage(), 500);
        }
    }

    public function getActivePromotions(): JsonResponse
    {
        try {
            $promotions = Promotion::active()
                                  ->valid()
                                  ->byPriority()
                                  ->get();

            $promotions->transform(function ($promotion) {
                $promotion->status = $promotion->status;
                $promotion->formatted_discount = $promotion->formatted_discount;
                return $promotion;
            });

            return $this->successResponse($promotions, 'Active promotions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get active promotions: ' . $e->getMessage(), 500);
        }
    }

    public function calculatePromotions(Request $request): JsonResponse
    {
        try {
            $cartItems = $request->input('cart_items', []);

            if (empty($cartItems)) {
                return $this->errorResponse('Cart items are required', 400);
            }

            $activePromotions = Promotion::active()
                                        ->valid()
                                        ->byPriority()
                                        ->get();

            $applicablePromotions = [];
            
            foreach ($activePromotions as $promotion) {
                $result = $promotion->calculatePromotion($cartItems);
                
                if ($result['discount'] > 0) {
                    $applicablePromotions[] = [
                        'promotion' => $promotion,
                        'discount_amount' => $result['discount'],
                        'description' => $result['description'],
                        'applied_items' => $result['applied_items'],
                        'formatted_discount' => 'Rp ' . number_format($result['discount'], 0, ',', '.')
                    ];
                }
            }

            // Sort by discount amount (highest first)
            usort($applicablePromotions, function ($a, $b) {
                return $b['discount_amount'] <=> $a['discount_amount'];
            });

            return $this->successResponse($applicablePromotions, 'Promotion calculations completed');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate promotions: ' . $e->getMessage(), 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_promotions' => Promotion::count(),
                'active_promotions' => Promotion::active()->valid()->count(),
                'expired_promotions' => Promotion::where('valid_until', '<', now())->count(),
                'scheduled_promotions' => Promotion::where('valid_from', '>', now())->count(),
                'promotion_types' => Promotion::select('type', DB::raw('count(*) as count'))
                                           ->groupBy('type')
                                           ->get(),
                'highest_priority' => Promotion::max('priority'),
                'current_active' => Promotion::where('active', true)
                                            ->where('valid_from', '<=', now())
                                            ->where('valid_until', '>=', now())
                                            ->count()
            ];

            return $this->successResponse($stats, 'Promotion statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get promotion statistics: ' . $e->getMessage(), 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        try {
            $promotion = Promotion::findOrFail($id);

            DB::beginTransaction();

            $promotion->update([
                'active' => !$promotion->active,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return $this->successResponse([
                'active' => $promotion->active
            ], 'Promotion status updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update promotion status: ' . $e->getMessage(), 500);
        }
    }

    public function duplicate($id): JsonResponse
    {
        try {
            $originalPromotion = Promotion::findOrFail($id);

            DB::beginTransaction();

            $newPromotion = $originalPromotion->replicate();
            $newPromotion->name = $originalPromotion->name . ' (Copy)';
            $newPromotion->created_by = Auth::id();
            $newPromotion->updated_by = null;
            $newPromotion->save();

            DB::commit();

            $newPromotion->load(['createdBy:id,name']);
            $newPromotion->status = $newPromotion->status;

            return $this->successResponse($newPromotion, 'Promotion duplicated successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to duplicate promotion: ' . $e->getMessage(), 500);
        }
    }
}
