<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $query = User::with(['role:id,name']);

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Filter by role
            if ($request->filled('role_id')) {
                $query->where('role_id', $request->role_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSortFields = ['id', 'name', 'username', 'email', 'is_active', 'created_at', 'updated_at', 'last_login_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);

            if ($request->get('paginate', true)) {
                $users = $query->paginate($perPage);
            } else {
                $users = $query->get();
            }

            $response = $this->successResponse($users, 'Users data retrieved successfully');
            
            // Log successful transaction
            $this->logSuccess($request, 'index', 'user', null, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'index', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:6',
                'role_id' => 'required|exists:roles,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $userData = $request->all();
            $userData['password'] = Hash::make($request->password);
            $userData['is_active'] = $request->get('is_active', true);

            $user = User::create($userData);
            
            // Load the role relationship
            $user->load('role:id,name');

            $response = $this->successResponse($user, 'User created successfully', 201);
            
            // Log successful transaction
            $this->logSuccess($request, 'store', 'user', $user->id, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'store', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['role:id,name'])->findOrFail($id);
            
            $response = $this->successResponse($user, 'User retrieved successfully');
            
            // Log successful transaction
            $this->logSuccess(request(), 'show', 'user', $user->id, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError(request(), 'show', 'user', $e->getMessage());
            
            return $this->errorResponse('User not found', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'phone' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:6',
                'role_id' => 'required|exists:roles,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $updateData = $request->except(['password']);
            
            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            
            // Load the role relationship
            $user->load('role:id,name');

            $response = $this->successResponse($user, 'User updated successfully');
            
            // Log successful transaction
            $this->logSuccess($request, 'update', 'user', $user->id, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'update', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting yourself
            if ($user->id === Auth::id()) {
                return $this->errorResponse('You cannot delete your own account', 400);
            }

            $user->delete();

            $response = $this->successResponse(null, 'User deleted successfully');
            
            // Log successful transaction
            $this->logSuccess(request(), 'destroy', 'user', $user->id, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError(request(), 'destroy', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    public function toggleActive(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deactivating yourself
            if ($user->id === Auth::id()) {
                return $this->errorResponse('You cannot deactivate your own account', 400);
            }

            $user->update(['is_active' => !$user->is_active]);
            
            // Load the role relationship
            $user->load('role:id,name');

            $status = $user->is_active ? 'activated' : 'deactivated';
            $response = $this->successResponse($user, "User {$status} successfully");
            
            // Log successful transaction
            $this->logSuccess($request, 'toggle-active', 'user', $user->id, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'toggle-active', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to toggle user status: ' . $e->getMessage(), 500);
        }
    }

    public function getRoles()
    {
        try {
            $roles = Role::select('id', 'name', 'description')->get();
            
            $response = $this->successResponse($roles, 'Roles retrieved successfully');
            
            return $response;
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve roles: ' . $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $userIds = $request->user_ids;
            
            // Prevent deleting yourself
            if (in_array(Auth::id(), $userIds)) {
                return $this->errorResponse('You cannot delete your own account', 400);
            }

            $deletedCount = User::whereIn('id', $userIds)->delete();

            $response = $this->successResponse(
                ['deleted_count' => $deletedCount], 
                "{$deletedCount} users deleted successfully"
            );
            
            // Log successful transaction
            $this->logSuccess($request, 'bulk-delete', 'user', null, $response);
            
            return $response;
        } catch (\Exception $e) {
            // Log failed transaction
            $this->logError($request, 'bulk-delete', 'user', $e->getMessage());
            
            return $this->errorResponse('Failed to delete users: ' . $e->getMessage(), 500);
        }
    }
}
