# Testing API dengan JWT Token

## 📋 **Setup Authentication**

### 1. Login untuk mendapatkan JWT Token

**Request:**
```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}
```

**Response Success:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "admin@example.com"
        },
        "token": "1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

### 2. Gunakan Token untuk Request Selanjutnya

Semua request ke API yang dilindungi harus menggunakan header:
```
Authorization: Bearer 1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Accept: application/json
Content-Type: application/json
```

---

## 🔧 **Testing dengan cURL (Command Line)**

### Login dan Simpan Token:
```bash
# Login dan simpan token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.data.token' > token.txt

# Baca token dari file
TOKEN=$(cat token.txt)
```

### Test Asset API:
```bash
# Get all assets
curl -X GET http://localhost:8000/api/assets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Get asset statistics
curl -X GET http://localhost:8000/api/assets/statistics \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Create new asset
curl -X POST http://localhost:8000/api/assets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Printer",
    "category": "Office Equipment",
    "brand": "HP",
    "model": "LaserJet Pro",
    "condition": "excellent",
    "status": "active",
    "location": "Office",
    "purchase_price": 250.00,
    "department": "IT"
  }'
```

### Test Bluetooth Device API:
```bash
# Get all bluetooth devices
curl -X GET http://localhost:8000/api/bluetooth-devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Get default devices
curl -X GET http://localhost:8000/api/bluetooth-devices/defaults \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Create new bluetooth device
curl -X POST http://localhost:8000/api/bluetooth-devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "device_name": "Test Printer BT",
    "device_address": "00:AA:BB:CC:DD:EE",
    "device_type": "printer",
    "manufacturer": "Epson",
    "model": "TM-T88VI",
    "is_default": false
  }'
```

---

## 🔧 **Testing dengan PowerShell**

### 1. Login dan Simpan Token:
```powershell
# Login request
$loginData = @{
    email = "admin@example.com"
    password = "password"
} | ConvertTo-Json

$loginResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/login" `
    -Method POST `
    -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} `
    -Body $loginData

$loginResult = $loginResponse.Content | ConvertFrom-Json
$token = $loginResult.data.token

Write-Host "Token: $token"
```

### 2. Test Asset API:
```powershell
# Get all assets
$assetResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/assets" `
    -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"}

$assets = $assetResponse.Content | ConvertFrom-Json
$assets.data | Format-Table

# Create new asset
$assetData = @{
    name = "Test Equipment"
    category = "Kitchen Equipment"
    brand = "Samsung"
    model = "Commercial Oven"
    condition = "excellent"
    status = "active"
    location = "Main Kitchen"
    purchase_price = 1500.00
    department = "Kitchen"
} | ConvertTo-Json

$createResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/assets" `
    -Method POST `
    -Headers @{"Authorization"="Bearer $token"; "Content-Type"="application/json"; "Accept"="application/json"} `
    -Body $assetData

$createResult = $createResponse.Content | ConvertFrom-Json
$createResult.data
```

### 3. Test Bluetooth Device API:
```powershell
# Get bluetooth devices
$btResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/bluetooth-devices" `
    -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"}

$btDevices = $btResponse.Content | ConvertFrom-Json
$btDevices.data | Format-Table

# Get default devices
$defaultsResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/bluetooth-devices/defaults" `
    -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"}

$defaults = $defaultsResponse.Content | ConvertFrom-Json
$defaults.data
```

---

## 🔧 **Testing dengan Postman**

### 1. Setup Environment:
- Buat environment baru: `POS_API`
- Tambahkan variable:
  - `base_url`: `http://localhost:8000/api`
  - `token`: (kosong dulu)

### 2. Login Request:
```
POST {{base_url}}/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}
```

**Postman Script (Tests tab):**
```javascript
if (responseCode.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.data.token);
    pm.test("Token saved", function () {
        pm.expect(jsonData.data.token).to.not.be.undefined;
    });
}
```

### 3. Asset API Requests:
```
GET {{base_url}}/assets
Authorization: Bearer {{token}}
Accept: application/json
```

```
POST {{base_url}}/assets
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "name": "Commercial Microwave",
    "category": "Kitchen Equipment",
    "brand": "Panasonic",
    "model": "NE-1054F",
    "condition": "excellent",
    "status": "active",
    "location": "Kitchen Station 2",
    "purchase_price": 450.00,
    "department": "Kitchen"
}
```

---

## 📊 **Asset API Endpoints**

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/assets` | List all assets (with filtering) |
| POST | `/api/assets` | Create new asset |
| GET | `/api/assets/{id}` | Get specific asset |
| PUT | `/api/assets/{id}` | Update asset |
| DELETE | `/api/assets/{id}` | Delete asset |
| GET | `/api/assets/statistics` | Get asset statistics |
| GET | `/api/assets/category/{category}` | Get assets by category |
| POST | `/api/assets/{id}/change-status` | Change asset status |
| POST | `/api/assets/{id}/assign` | Assign asset to someone |
| GET | `/api/assets/maintenance-schedule` | Get maintenance schedule |

### Filtering Parameters untuk GET `/api/assets`:
- `category`: Filter by category
- `location`: Filter by location  
- `status`: Filter by status (active, inactive, maintenance, disposed)
- `condition`: Filter by condition (excellent, good, fair, poor, damaged)
- `department`: Filter by department
- `assigned_only`: true/false - only assigned assets
- `unassigned_only`: true/false - only unassigned assets
- `search`: Search in name, asset_code, brand, model, serial_number, description
- `sort_by`: Sort field (default: created_at)
- `sort_order`: asc/desc (default: desc)
- `per_page`: Items per page (default: 15)

---

## 📱 **Bluetooth Device API Endpoints**

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bluetooth-devices` | List all devices |
| POST | `/api/bluetooth-devices` | Create new device |
| GET | `/api/bluetooth-devices/defaults` | Get default devices by type |
| GET | `/api/bluetooth-devices/{id}` | Get specific device |
| PUT | `/api/bluetooth-devices/{id}` | Update device |
| DELETE | `/api/bluetooth-devices/{id}` | Delete device |
| POST | `/api/bluetooth-devices/{id}/set-default` | Set as default device |
| POST | `/api/bluetooth-devices/{id}/update-connection` | Update connection status |
| POST | `/api/bluetooth-devices/{id}/test-connection` | Test device connection |

---

## 🚀 **Quick Test Script**

Simpan script ini sebagai `test_api.ps1`:

```powershell
# Test API Script
$baseUrl = "http://localhost:8000/api"

# Login
$loginData = @{
    email = "admin@example.com"
    password = "password"
} | ConvertTo-Json

Write-Host "🔐 Testing Login..." -ForegroundColor Yellow
$loginResponse = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -Headers @{"Content-Type"="application/json"} -Body $loginData
$token = ($loginResponse.Content | ConvertFrom-Json).data.token
Write-Host "✅ Login Success! Token: $($token.Substring(0,20))..." -ForegroundColor Green

# Test Assets
Write-Host "📦 Testing Assets API..." -ForegroundColor Yellow
$headers = @{"Authorization"="Bearer $token"; "Accept"="application/json"}
$assetsResponse = Invoke-WebRequest -Uri "$baseUrl/assets" -Headers $headers
$assets = ($assetsResponse.Content | ConvertFrom-Json).data
Write-Host "✅ Found $($assets.Count) assets" -ForegroundColor Green

# Test Bluetooth Devices
Write-Host "📱 Testing Bluetooth Devices API..." -ForegroundColor Yellow
$btResponse = Invoke-WebRequest -Uri "$baseUrl/bluetooth-devices" -Headers $headers
$btDevices = ($btResponse.Content | ConvertFrom-Json).data
Write-Host "✅ Found $($btDevices.Count) bluetooth devices" -ForegroundColor Green

Write-Host "🎉 All tests completed successfully!" -ForegroundColor Green
```

Jalankan dengan: `.\test_api.ps1`
