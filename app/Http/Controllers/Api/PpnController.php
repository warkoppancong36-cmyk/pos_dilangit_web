<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Ppn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PpnController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $query = Ppn::with(['creator:id,username', 'updater:id,username']);

            if ($request->has('active')) {
                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('active', $active);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('nominal', 'like', "%{$search}%");
                });
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSortFields = ['id_ppn', 'name', 'nominal', 'active', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);

            if ($request->get('paginate', true)) {
                $ppns = $query->paginate($perPage);
            } else {
                $ppns = $query->get();
            }

            $response = $this->successResponse($ppns, 'PPN data retrieved successfully');
            
            // Log successful transaction
            $this->logSuccess($request, 'index', 'ppn', null, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'index', 'ppn', $e->getMessage());
            
            return $this->errorResponse('Failed to retrieve PPN data: ' . $e->getMessage(), null, 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role->name, ['admin', 'manager'])) {
                return $this->errorResponse('Unauthorized. Only admin and manager can create PPN', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'nominal' => 'required|integer|min:0|max:100',
                'description' => 'nullable|string',
                'active' => 'boolean',
                'status' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            $ppn = Ppn::create([
                'name' => $request->name,
                'nominal' => $request->nominal,
                'description' => $request->description,
                'active' => $request->get('active', true),
                'status' => $request->status,
                'created_by' => $user->id,
            ]);

            $ppn->load(['creator:id,username']);

            return $this->successResponse($ppn, 'PPN created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create PPN: ' . $e->getMessage(), null, 500);
        }
    }

    public function show($id)
    {
        try {
            $ppn = Ppn::with(['creator:id,username', 'updater:id,username', 'deleter:id,username'])->find($id);

            if (!$ppn) {
                return $this->errorResponse('PPN not found', null, 404);
            }

            return $this->successResponse($ppn, 'PPN retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PPN: ' . $e->getMessage(), null, 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role->name, ['admin', 'manager'])) {
                return $this->errorResponse('Unauthorized. Only admin and manager can update PPN', null, 403);
            }

            $ppn = Ppn::find($id);
            if (!$ppn) {
                return $this->errorResponse('PPN not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'nominal' => 'sometimes|required|integer|min:0|max:100',
                'description' => 'nullable|string',
                'active' => 'boolean',
                'status' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            $updateData = $request->only(['name', 'nominal', 'description', 'active', 'status']);
            $updateData['updated_by'] = $user->id;

            $ppn->update($updateData);
            $ppn->load(['creator:id,username', 'updater:id,username']);

            return $this->successResponse($ppn, 'PPN updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update PPN: ' . $e->getMessage(), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role->name !== 'admin') {
                return $this->errorResponse('Unauthorized. Only admin can delete PPN', null, 403);
            }

            $ppn = Ppn::find($id);
            if (!$ppn) {
                return $this->errorResponse('PPN not found', null, 404);
            }

            $ppn->update(['deleted_by' => $user->id]);
            $ppn->delete();

            return $this->successResponse(null, 'PPN deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete PPN: ' . $e->getMessage(), null, 500);
        }
    }

    public function restore($id)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role->name !== 'admin') {
                return $this->errorResponse('Unauthorized. Only admin can restore PPN', null, 403);
            }

            $ppn = Ppn::withTrashed()->find($id);
            if (!$ppn) {
                return $this->errorResponse('PPN not found', null, 404);
            }

            if (!$ppn->trashed()) {
                return $this->errorResponse('PPN is not deleted', null, 400);
            }

            $ppn->restore();
            $ppn->update(['updated_by' => $user->id]);

            return $this->successResponse($ppn, 'PPN restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore PPN: ' . $e->getMessage(), null, 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role->name, ['admin', 'manager'])) {
                return $this->errorResponse('Unauthorized. Only admin and manager can toggle PPN status', null, 403);
            }

            $ppn = Ppn::find($id);
            if (!$ppn) {
                return $this->errorResponse('PPN not found', null, 404);
            }

            $ppn->update([
                'active' => !$ppn->active,
                'updated_by' => $user->id,
            ]);

            $status = $ppn->active ? 'activated' : 'deactivated';
            return $this->successResponse($ppn, "PPN {$status} successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle PPN status: ' . $e->getMessage(), null, 500);
        }
    }

    public function getActive()
    {
        try {
            $ppns = Ppn::active()
                ->select('id_ppn', 'name', 'nominal', 'description')
                ->orderBy('name')
                ->get();

            return $this->successResponse($ppns, 'Active PPN data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve active PPN data: ' . $e->getMessage(), null, 500);
        }
    }
}
