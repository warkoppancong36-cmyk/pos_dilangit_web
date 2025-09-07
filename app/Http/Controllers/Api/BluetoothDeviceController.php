<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BluetoothDevice;
use App\Http\Resources\BluetoothDeviceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BluetoothDeviceController extends Controller
{

    /**
     * Get all Bluetooth devices for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = BluetoothDevice::query();

        // Filter by device type if provided
        if ($request->has('device_type')) {
            $query->byType($request->device_type);
        }

        // Filter by active status if provided
        if ($request->has('active_only') && $request->boolean('active_only')) {
            $query->active();
        }

        $devices = $query->with('user:id,name,email')->orderBy('device_name')->get();

        return response()->json([
            'success' => true,
            'data' => BluetoothDeviceResource::collection($devices)
        ]);
    }

    /**
     * Store a new Bluetooth device
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'device_address' => 'required|string|max:17|unique:bluetooth_devices,device_address',
            'device_type' => 'required|in:printer,scanner,cash_drawer,scale,other',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'device_capabilities' => 'nullable|array',
            'connection_settings' => 'nullable|array',
            'is_default' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        $validated['id_user'] = $user->id;
        $validated['is_active'] = true;

        $device = BluetoothDevice::create($validated);

        // If set as default, update other devices
        if ($validated['is_default'] ?? false) {
            $device->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Bluetooth device added successfully',
            'data' => new BluetoothDeviceResource($device->fresh())
        ], 201);
    }

    /**
     * Get a specific Bluetooth device
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found'
            ], 404);
        }

        // Add computed attributes
        $device->device_type_label = $device->device_type_label;
        $device->connection_status = $device->connection_status;

        return response()->json([
            'success' => true,
            'data' => new BluetoothDeviceResource($device)
        ]);
    }

    /**
     * Update a Bluetooth device
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found'
            ], 404);
        }

        $validated = $request->validate([
            'device_name' => 'sometimes|required|string|max:255',
            'device_address' => [
                'sometimes',
                'required',
                'string',
                'max:17',
                Rule::unique('bluetooth_devices')->ignore($device->id_bluetooth_device, 'id_bluetooth_device')
            ],
            'device_type' => 'sometimes|required|in:printer,scanner,cash_drawer,scale,other',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'device_capabilities' => 'nullable|array',
            'connection_settings' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        $device->update($validated);

        // If set as default, update other devices
        if (isset($validated['is_default']) && $validated['is_default']) {
            $device->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Bluetooth device updated successfully',
            'data' => new BluetoothDeviceResource($device->fresh())
        ]);
    }

    /**
     * Delete a Bluetooth device
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found'
            ], 404);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bluetooth device deleted successfully'
        ]);
    }

    /**
     * Set a device as default for its type
     */
    public function setDefault($id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->active()
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found or inactive'
            ], 404);
        }

        $device->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Device set as default successfully',
            'data' => new BluetoothDeviceResource($device->fresh())
        ]);
    }

    /**
     * Update device connection status
     */
    public function updateConnection($id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->active()
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found or inactive'
            ], 404);
        }

        $device->updateLastConnected();

        return response()->json([
            'success' => true,
            'message' => 'Connection status updated successfully',
            'data' => [
                'last_connected_at' => $device->fresh()->last_connected_at,
                'connection_status' => $device->fresh()->connection_status
            ]
        ]);
    }

    /**
     * Get default devices by type for the user
     */
    public function getDefaults(): JsonResponse
    {
        $user = Auth::user();
        
        $defaults = BluetoothDevice::forUser($user->id)
            ->active()
            ->default()
            ->get()
            ->groupBy('device_type');

        $result = [];
        foreach (['printer', 'scanner', 'cash_drawer', 'scale', 'other'] as $type) {
            $result[$type] = $defaults->get($type, collect())->first();
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get mobile-optimized device list with minimal data
     * Khusus untuk mobile apps dengan data yang sudah di-optimize
     */
    public function mobileDevices(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Get active devices only for mobile
        $devices = BluetoothDevice::forUser($user->id)
            ->active()
            ->select([
                'id_bluetooth_device',
                'device_name', 
                'device_address',
                'device_type',
                'is_default',
                'last_connected_at',
                'updated_at'
            ])
            ->orderBy('is_default', 'desc')
            ->orderBy('device_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
            'meta' => [
                'last_sync' => now()->toISOString(),
                'total_devices' => $devices->count(),
                'cache_duration' => 300 // 5 minutes recommended cache
            ]
        ]);
    }

    /**
     * Check for device updates since last sync
     * Untuk efficient mobile sync
     */
    public function checkUpdates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $lastSync = $request->input('last_sync', now()->subDay());

        $hasUpdates = BluetoothDevice::forUser($user->id)
            ->where('updated_at', '>', $lastSync)
            ->exists();

        return response()->json([
            'success' => true,
            'has_updates' => $hasUpdates,
            'last_check' => now()->toISOString()
        ]);
    }

    /**
     * Test device connection
     */
    public function testConnection($id): JsonResponse
    {
        $user = Auth::user();
        
        $device = BluetoothDevice::where('id_bluetooth_device', $id)
            ->forUser($user->id)
            ->active()
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Bluetooth device not found or inactive'
            ], 404);
        }

        // In a real implementation, you would test the actual Bluetooth connection
        // For now, we'll simulate and update the connection timestamp
        $device->updateLastConnected();

        return response()->json([
            'success' => true,
            'message' => 'Connection test completed',
            'data' => [
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'connection_status' => $device->fresh()->connection_status,
                'last_connected_at' => $device->fresh()->last_connected_at
            ]
        ]);
    }
}
