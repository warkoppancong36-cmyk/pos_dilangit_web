<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Parse permission string (format: module.action or permission_name)
        if (strpos($permission, '.') !== false) {
            list($module, $action) = explode('.', $permission, 2);
            $hasPermission = $user->hasPermission($module, $action);
        } else {
            $hasPermission = $user->hasPermissionByName($permission);
        }

        if (!$hasPermission) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this resource',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
