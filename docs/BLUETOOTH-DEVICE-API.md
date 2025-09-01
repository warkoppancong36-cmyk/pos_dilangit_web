# Bluetooth Device Management API

This API allows mobile cashier applications to manage Bluetooth devices (printers, scanners, cash drawers, scales) for POS operations.

## Base URL
```
http://your-domain.com/api/bluetooth-devices
```

## Authentication
All endpoints require Bearer token authentication.

## Endpoints

### 1. Get All Bluetooth Devices
```http
GET /api/bluetooth-devices
```

**Query Parameters:**
- `device_type` (optional): Filter by device type (printer, scanner, cash_drawer, scale, other)
- `active_only` (optional): Boolean to filter only active devices

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id_bluetooth_device": 1,
            "device_name": "Thermal Printer POS",
            "device_address": "00:11:22:33:44:55",
            "device_type": "printer",
            "device_type_label": "Printer",
            "manufacturer": "Epson",
            "model": "TM-T82III",
            "device_capabilities": {
                "paper_width": "80mm",
                "print_speed": "200mm/s",
                "auto_cutter": true,
                "cash_drawer_kick": true
            },
            "connection_settings": {
                "baudrate": 9600,
                "data_bits": 8,
                "stop_bits": 1,
                "parity": "none"
            },
            "is_default": true,
            "is_active": true,
            "connection_status": "connected",
            "last_connected_at": "2025-01-31 15:30:00",
            "notes": "Main receipt printer for POS transactions",
            "created_at": "2025-01-31 10:00:00",
            "updated_at": "2025-01-31 15:30:00"
        }
    ]
}
```

### 2. Create New Bluetooth Device
```http
POST /api/bluetooth-devices
```

**Request Body:**
```json
{
    "device_name": "Kitchen Printer",
    "device_address": "00:11:22:33:44:66",
    "device_type": "printer",
    "manufacturer": "Star",
    "model": "TSP143III",
    "device_capabilities": {
        "paper_width": "80mm",
        "print_speed": "250mm/s",
        "auto_cutter": true,
        "waterproof": true
    },
    "connection_settings": {
        "baudrate": 9600,
        "data_bits": 8,
        "stop_bits": 1,
        "parity": "none"
    },
    "is_default": false,
    "notes": "Kitchen order printer for food preparation"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Bluetooth device added successfully",
    "data": { /* device object */ }
}
```

### 3. Get Specific Device
```http
GET /api/bluetooth-devices/{id}
```

### 4. Update Device
```http
PUT /api/bluetooth-devices/{id}
```

### 5. Delete Device
```http
DELETE /api/bluetooth-devices/{id}
```

### 6. Set Device as Default
```http
POST /api/bluetooth-devices/{id}/set-default
```

Sets the device as the default for its device type. Only one device per type can be default.

### 7. Update Connection Status
```http
POST /api/bluetooth-devices/{id}/update-connection
```

Updates the `last_connected_at` timestamp to indicate recent connection.

### 8. Test Device Connection
```http
POST /api/bluetooth-devices/{id}/test-connection
```

Simulates testing the device connection and updates the connection timestamp.

**Response:**
```json
{
    "success": true,
    "message": "Connection test completed",
    "data": {
        "device_name": "Thermal Printer POS",
        "device_type": "printer",
        "connection_status": "connected",
        "last_connected_at": "2025-01-31 15:45:00"
    }
}
```

### 9. Get Default Devices
```http
GET /api/bluetooth-devices/defaults
```

Returns the default device for each device type.

**Response:**
```json
{
    "success": true,
    "data": {
        "printer": { /* default printer device */ },
        "scanner": { /* default scanner device */ },
        "cash_drawer": { /* default cash drawer device */ },
        "scale": { /* default scale device */ },
        "other": null
    }
}
```

## Device Types
- `printer`: Thermal printers, receipt printers, kitchen printers
- `scanner`: Barcode scanners, QR code readers
- `cash_drawer`: Electronic cash drawers
- `scale`: Digital scales for weighing products
- `other`: Other Bluetooth devices

## Connection Status
- `connected`: Device was connected within the last hour
- `disconnected`: Device was connected more than an hour ago
- `never_connected`: Device has never been connected
- `disabled`: Device is marked as inactive

## Error Responses
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["validation error messages"]
    }
}
```

## Usage in Mobile App

### Example: Get Printer for Receipt
```javascript
// Get default printer
const response = await fetch('/api/bluetooth-devices/defaults', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
});

const defaults = await response.json();
const printer = defaults.data.printer;

if (printer && printer.is_active) {
    // Use printer for receipt printing
    console.log('Using printer:', printer.device_name);
    console.log('Connection settings:', printer.connection_settings);
}
```

### Example: Add New Device
```javascript
const newDevice = {
    device_name: 'Customer Display',
    device_address: '00:AA:BB:CC:DD:EE',
    device_type: 'other',
    manufacturer: 'Epson',
    model: 'DM-D110',
    device_capabilities: {
        'display_type': 'LCD',
        'characters': '20x2',
        'backlight': true
    },
    is_default: true
};

const response = await fetch('/api/bluetooth-devices', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify(newDevice)
});
```

### Example: Update Connection Status
```javascript
// Call this when successfully connecting to a device
const updateConnection = async (deviceId) => {
    await fetch(`/api/bluetooth-devices/${deviceId}/update-connection`, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    });
};
```

## Database Schema

The `bluetooth_devices` table includes:
- Device identification (name, address, type, manufacturer, model)
- JSON capabilities and connection settings for flexibility
- User ownership and default device management
- Activity status and connection tracking
- Timestamps for audit trail

This design allows for comprehensive device management while remaining flexible for different types of Bluetooth peripherals.
