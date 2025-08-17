<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionLogController extends Controller
{
    /**
     * Display a listing of transaction logs.
     */
    public function index(Request $request)
    {
        // Check if user has permission to view logs
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            return $this->errorResponse('Unauthorized access to transaction logs', 403);
        }
        
        $query = TransactionLog::with('user:id,username,role')
            ->orderBy('created_at', 'desc');
        
        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        
        if ($request->filled('status')) {
            $successful = $request->status === 'success';
            $query->where('is_successful', $successful);
        }
        
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }
        
        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->method));
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('request_ip', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }
        
        // Pagination
        $perPage = $request->get('per_page', 50);
        $perPage = min($perPage, 100); // Limit max per page
        
        $logs = $query->paginate($perPage);
        
        // Transform data to hide sensitive information for non-admin users
        if ($user->role !== 'admin') {
            $logs->getCollection()->transform(function ($log) {
                $log->makeHidden(['request_payload', 'response_payload', 'request_headers', 'response_headers']);
                return $log;
            });
        }
        
        return $this->successResponse($logs, 'Transaction logs retrieved successfully');
    }
    
    /**
     * Display the specified transaction log.
     */
    public function show(Request $request, $id)
    {
        // Check if user has permission to view logs
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            return $this->errorResponse('Unauthorized access to transaction logs', 403);
        }
        
        $log = TransactionLog::with('user:id,username,role')->find($id);
        
        if (!$log) {
            return $this->errorResponse('Transaction log not found', 404);
        }
        
        // Hide sensitive information for non-admin users
        if ($user->role !== 'admin') {
            $log->makeHidden(['request_payload', 'response_payload', 'request_headers', 'response_headers']);
        }
        
        return $this->successResponse($log, 'Transaction log retrieved successfully');
    }
    
    /**
     * Get transaction log statistics.
     */
    public function statistics(Request $request)
    {
        // Check if user has permission to view logs
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            return $this->errorResponse('Unauthorized access to transaction logs', 403);
        }
        
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->startOfDay());
        $dateTo = $request->get('date_to', Carbon::now()->endOfDay());
        
        $query = TransactionLog::whereBetween('created_at', [$dateFrom, $dateTo]);
        
        $statistics = [
            'total_requests' => $query->count(),
            'successful_requests' => $query->where('is_successful', true)->count(),
            'failed_requests' => $query->where('is_successful', false)->count(),
            'unique_users' => $query->whereNotNull('user_id')->distinct('user_id')->count(),
            'unique_ips' => $query->distinct('request_ip')->count(),
            
            // By transaction type
            'by_transaction_type' => $query->selectRaw('transaction_type, COUNT(*) as count')
                ->groupBy('transaction_type')
                ->get()
                ->pluck('count', 'transaction_type'),
            
            // By module
            'by_module' => $query->selectRaw('module, COUNT(*) as count')
                ->whereNotNull('module')
                ->groupBy('module')
                ->get()
                ->pluck('count', 'module'),
            
            // By HTTP method
            'by_method' => $query->selectRaw('method, COUNT(*) as count')
                ->groupBy('method')
                ->get()
                ->pluck('count', 'method'),
            
            // Response status codes
            'by_status_code' => $query->selectRaw('response_status, COUNT(*) as count')
                ->groupBy('response_status')
                ->get()
                ->pluck('count', 'response_status'),
            
            // Performance metrics
            'avg_execution_time' => $query->whereNotNull('execution_time')->avg('execution_time'),
            'max_execution_time' => $query->whereNotNull('execution_time')->max('execution_time'),
            'avg_memory_usage' => $query->whereNotNull('memory_usage')->avg('memory_usage'),
            
            // Top endpoints
            'top_endpoints' => $query->selectRaw('endpoint, COUNT(*) as count')
                ->groupBy('endpoint')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'endpoint' => $item->endpoint,
                        'count' => $item->count
                    ];
                }),
            
            // Recent activity (last 24 hours by hour)
            'hourly_activity' => $query->where('created_at', '>=', Carbon::now()->subDay())
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour'),
        ];
        
        return $this->successResponse($statistics, 'Transaction log statistics retrieved successfully');
    }
    
    /**
     * Get user activity logs.
     */
    public function userActivity(Request $request, $userId)
    {
        // Check if user has permission or is requesting own logs
        $user = Auth::user();
        if (!$user || (!in_array($user->role, ['admin', 'manager']) && $user->id != $userId)) {
            return $this->errorResponse('Unauthorized access to user activity logs', 403);
        }
        
        $query = TransactionLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc');
        
        // Date filters
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }
        
        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);
        
        // Hide sensitive information for non-admin users
        if ($user->role !== 'admin') {
            $logs->getCollection()->transform(function ($log) {
                $log->makeHidden(['request_payload', 'response_payload', 'request_headers', 'response_headers']);
                return $log;
            });
        }
        
        return $this->successResponse($logs, 'User activity logs retrieved successfully');
    }
    
    /**
     * Export transaction logs.
     */
    public function export(Request $request)
    {
        // Check if user has permission
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return $this->errorResponse('Only administrators can export transaction logs', 403);
        }
        
        $query = TransactionLog::orderBy('created_at', 'desc');
        
        // Apply same filters as index
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }
        
        // Limit export size to prevent memory issues
        $logs = $query->limit(10000)->get();
        
        $exportData = $logs->map(function ($log) {
            return [
                'transaction_id' => $log->transaction_id,
                'timestamp' => $log->created_at->toISOString(),
                'method' => $log->method,
                'endpoint' => $log->endpoint,
                'user' => $log->username,
                'role' => $log->user_role,
                'ip' => $log->request_ip,
                'status' => $log->response_status,
                'success' => $log->is_successful ? 'Yes' : 'No',
                'execution_time' => $log->execution_time,
                'module' => $log->module,
                'action' => $log->action,
                'transaction_type' => $log->transaction_type,
            ];
        });
        
        return $this->successResponse($exportData, 'Transaction logs exported successfully');
    }
}
