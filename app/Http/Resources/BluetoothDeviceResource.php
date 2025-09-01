<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BluetoothDeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_bluetooth_device' => $this->id_bluetooth_device,
            'device_name' => $this->device_name,
            'device_address' => $this->device_address,
            'device_type' => $this->device_type,
            'device_type_label' => $this->device_type_label,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'device_capabilities' => $this->device_capabilities,
            'connection_settings' => $this->connection_settings,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'connection_status' => $this->connection_status,
            'last_connected_at' => $this->last_connected_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
