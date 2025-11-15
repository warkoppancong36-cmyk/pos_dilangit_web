<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Bank::query();

            if ($request->filled('search')) {
                $s = strtolower($request->search);
                $query->where(function($q) use ($s) {
                    $q->whereRaw('LOWER(code) like ?', ["%{$s}%"])
                      ->orWhereRaw('LOWER(name) like ?', ["%{$s}%"]);
                });
            }

            if ($request->filled('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            $perPage = $request->get('per_page', 15);
            $rows = $query->orderBy('name')->paginate($perPage);

            return $this->paginatedResponse($rows, 'Banks retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve banks: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:banks,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $request->only(['code', 'name', 'description', 'icon']);
            $data['code'] = strtolower(trim($data['code']));
            $data['is_active'] = $request->boolean('is_active', true);
            $data['created_by'] = auth()->id();

            $bank = Bank::create($data);
            return $this->createdResponse($bank, 'Bank created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create bank: ' . $e->getMessage());
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $bank = Bank::findOrFail($id);
            return $this->successResponse($bank, 'Bank retrieved');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Bank not found');
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $bank = Bank::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|max:50|unique:banks,code,' . $bank->id_bank . ',id_bank',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->only(['code', 'name', 'description', 'icon']);
            if (isset($data['code'])) $data['code'] = strtolower(trim($data['code']));
            if ($request->filled('is_active')) $data['is_active'] = $request->boolean('is_active');
            $data['updated_by'] = auth()->id();

            $bank->update($data);
            return $this->successResponse($bank, 'Bank updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update bank: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->deleted_by = auth()->id();
            $bank->save();
            $bank->delete();
            return $this->deletedResponse('Bank deleted');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete bank: ' . $e->getMessage());
        }
    }

    public function toggleActive(Request $request, $id): JsonResponse
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->is_active = !$bank->is_active;
            $bank->save();
            $status = $bank->is_active ? 'activated' : 'deactivated';
            return $this->successResponse($bank, "Bank {$status}");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to toggle bank: ' . $e->getMessage());
        }
    }
}
