<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Debug logging
            \Log::info('RoleController@index called', [
                'user' => $request->user() ? $request->user()->id : 'no user',
                'headers' => $request->headers->all()
            ]);

            $query = Role::with(['permissions', 'users'])
                ->withCount(['permissions', 'users']);

            // Search functionality
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->has('status')) {
                $status = $request->get('status');
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $roles = $query->get();

            \Log::info('Roles found', ['count' => $roles->count()]);

            // If no roles found, return empty array instead of error
            $rolesData = $roles->isEmpty() ? [] : $roles->toArray();

            return response()->json([
                'success' => true,
                'data' => $rolesData,
                'message' => $roles->isEmpty() ? 'No roles found' : 'Roles fetched successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('RoleController@index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean',
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Sync permissions if provided
            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            DB::commit();

            $role->load(['permissions', 'users']);
            $role->loadCount(['permissions', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $role = Role::with(['permissions', 'users'])
                ->withCount(['permissions', 'users'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean',
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            DB::beginTransaction();

            $role->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? $role->is_active,
            ]);

            // Sync permissions if provided
            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            DB::commit();

            $role->load(['permissions', 'users']);
            $role->loadCount(['permissions', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            // Check if role has users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has assigned users',
                ], 422);
            }

            DB::beginTransaction();

            // Detach all permissions
            $role->permissions()->detach();
            
            // Delete the role
            $role->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle role active status.
     */
    public function toggleActive(string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            
            $role->update([
                'is_active' => !$role->is_active,
            ]);

            $role->load(['permissions', 'users']);
            $role->loadCount(['permissions', 'users']);

            return response()->json([
                'success' => true,
                'message' => $role->is_active ? 'Role activated successfully' : 'Role deactivated successfully',
                'data' => $role,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle role status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get role statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_roles' => Role::count(),
                'active_roles' => Role::where('is_active', true)->count(),
                'inactive_roles' => Role::where('is_active', false)->count(),
                'total_permissions' => Permission::count(),
                'roles_with_users' => Role::has('users')->count(),
                'empty_roles' => Role::doesntHave('users')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get permissions for a role.
     */
    public function getPermissions(string $id): JsonResponse
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $role->permissions,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            DB::beginTransaction();

            $role->permissions()->sync($validated['permissions']);

            DB::commit();

            $role->load('permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role permissions updated successfully',
                'data' => $role->permissions,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users for a role.
     */
    public function getUsers(string $id): JsonResponse
    {
        try {
            $role = Role::with(['users' => function ($query) {
                $query->select('users.id', 'users.name', 'users.username', 'users.email', 'users.is_active');
            }])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $role->users,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
