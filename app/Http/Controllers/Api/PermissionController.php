<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Get all permissions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Permission::query();

            // Filter by module
            if ($request->has('module')) {
                $query->where('module', $request->module);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Group by module if requested
            if ($request->boolean('group_by_module')) {
                $permissions = $query->where('is_active', true)
                                   ->orderBy('module')
                                   ->orderBy('sort_order')
                                   ->get()
                                   ->groupBy('module');

                return response()->json([
                    'success' => true,
                    'data' => $permissions
                ]);
            }

            // Regular listing
            $permissions = $query->orderBy('module')->orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new permission
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:permissions,name|max:100',
                'display_name' => 'required|string|max:255',
                'module' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'description' => 'nullable|string',
                'is_system' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = Permission::create([
                'name' => strtolower($request->name),
                'display_name' => $request->display_name,
                'module' => strtolower($request->module),
                'action' => strtolower($request->action),
                'description' => $request->description,
                'is_system' => $request->boolean('is_system', false),
                'is_active' => true,
                'sort_order' => $request->get('sort_order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific permission
     */
    public function show(int $id): JsonResponse
    {
        try {
            $permission = Permission::with(['roles', 'users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }
    }

    /**
     * Update permission
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            // Prevent editing system permissions
            if ($permission->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit system permission'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:permissions,name,' . $id . ',id_permission',
                'display_name' => 'required|string|max:255',
                'module' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission->update([
                'name' => strtolower($request->name),
                'display_name' => $request->display_name,
                'module' => strtolower($request->module),
                'action' => strtolower($request->action),
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', $permission->is_active),
                'sort_order' => $request->get('sort_order', $permission->sort_order),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete permission
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            // Prevent deleting system permissions
            if ($permission->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system permission'
                ], 403);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all modules
     */
    public function modules(): JsonResponse
    {
        try {
            $modules = Permission::getModules();

            return response()->json([
                'success' => true,
                'data' => $modules
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading modules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permission to role
     */
    public function assignToRole(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'permission_id' => 'required|exists:permissions,id_permission',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = Role::findOrFail($request->role_id);
            $role->assignPermission($request->permission_id);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove permission from role
     */
    public function removeFromRole(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'permission_id' => 'required|exists:permissions,id_permission',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = Role::findOrFail($request->role_id);
            $role->removePermission($request->permission_id);

            return response()->json([
                'success' => true,
                'message' => 'Permission removed from role successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Grant permission to user
     */
    public function grantToUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'permission_id' => 'required|exists:permissions,id_permission',
                'reason' => 'nullable|string|max:500',
                'expires_at' => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = Permission::findOrFail($request->permission_id);
            $user = User::findOrFail($request->user_id);

            $user->grantPermission(
                $permission->name,
                auth()->id(),
                $request->reason,
                $request->expires_at
            );

            return response()->json([
                'success' => true,
                'message' => 'Permission granted to user successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error granting permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deny permission to user
     */
    public function denyToUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'permission_id' => 'required|exists:permissions,id_permission',
                'reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = Permission::findOrFail($request->permission_id);
            $user = User::findOrFail($request->user_id);

            $user->denyPermission(
                $permission->name,
                auth()->id(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Permission denied to user successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error denying permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user permissions
     */
    public function userPermissions(int $userId): JsonResponse
    {
        try {
            $user = User::with(['role.permissions', 'userPermissions.permission'])->findOrFail($userId);

            $rolePermissions = $user->role ? $user->role->permissions->toArray() : [];
            $directPermissions = $user->userPermissions->map(function ($up) {
                return [
                    'permission' => $up->permission->toArray(),
                    'type' => $up->type,
                    'reason' => $up->reason,
                    'expires_at' => $up->expires_at,
                    'is_active' => $up->is_active,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'role' => $user->role,
                    'role_permissions' => $rolePermissions,
                    'direct_permissions' => $directPermissions,
                    'all_permissions' => $user->getAllPermissions(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
