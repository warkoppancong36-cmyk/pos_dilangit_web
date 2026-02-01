# Kitchen Notification System - Documentation

## 📋 Daftar Isi

- [Overview](#overview)
- [Fitur Utama](#fitur-utama)
- [Arsitektur Sistem](#arsitektur-sistem)
- [Instalasi & Setup](#instalasi--setup)
- [Konfigurasi Backend](#konfigurasi-backend)
- [Cara Penggunaan](#cara-penggunaan)
- [API Reference](#api-reference)
- [Testing Guide](#testing-guide)
- [Troubleshooting](#troubleshooting)

---

## Overview

**Kitchen Notification System** adalah fitur notifikasi real-time untuk sistem POS restoran yang memungkinkan kitchen staff menerima notifikasi otomatis ketika ada order baru dari bar atau kasir.

### Use Case
- **Bar** membuat order yang berisi item makanan → **Kitchen** langsung dapat notifikasi
- **Kasir** membuat order → Item yang perlu diproses kitchen otomatis muncul di Kitchen Display
- Kitchen staff dapat melacak status order: **Pending** → **In Progress** → **Completed**

### Technology Stack
- **Frontend**: Flutter + GetX
- **Notification**: Sound (audioplayers) + Vibration + Visual (Snackbar)
- **Real-time**: Polling mechanism (5 detik interval)
- **Backend**: Laravel API (asumsi)

---

## Fitur Utama

### 1. Multi-Type Notifications 🔔
- ✅ **Sound Alert** - Play MP3 notification saat order baru
- ✅ **Vibration** - Getar device untuk alert
- ✅ **Visual Pop-up** - Orange snackbar di top screen
- ✅ **Badge Counter** - Red badge dengan jumlah order baru

### 2. Kitchen Display System (KDS) 📺
- ✅ 3-Column Layout: **Pending** | **In Progress** | **Completed**
- ✅ Real-time order updates setiap 5 detik
- ✅ Urgency color coding (hijau < 5min, orange 5-10min, merah > 10min)
- ✅ Order source indicator (dari BAR atau KASIR)
- ✅ Item details dengan quantity, variant, dan notes

### 3. Order Management ⚡
- ✅ Accept order (Pending → In Progress)
- ✅ Complete order (In Progress → Completed)
- ✅ Pull-to-refresh untuk manual update
- ✅ Auto-limit completed orders (max 50) untuk optimasi memory

### 4. Settings & Controls ⚙️
- ✅ Sound on/off toggle
- ✅ Vibration on/off toggle
- ✅ Settings disimpan di SharedPreferences

---

## Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                     KITCHEN NOTIFICATION SYSTEM              │
└─────────────────────────────────────────────────────────────┘

┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│  Bar Device │────────▶│   Backend    │◀────────│   Kitchen   │
│   (Create)  │         │   Laravel    │         │   Display   │
└─────────────┘         └──────────────┘         └─────────────┘
                              │                         │
                              │                         │
                              ▼                         ▼
                        ┌──────────┐            ┌──────────────┐
                        │ Database │            │   Polling    │
                        │ orders + │            │   Service    │
                        │ kitchen_ │            │ (every 5sec) │
                        │  status  │            └──────────────┘
                        └──────────┘                    │
                                                        ▼
                                               ┌────────────────┐
                                               │  Notification  │
                                               │    Service     │
                                               │ • Sound        │
                                               │ • Vibration    │
                                               │ • Visual       │
                                               └────────────────┘
```

### Data Flow

```
1. Bar/Kasir create order
   └─▶ POST /api/pos/process-direct-payment
       (with created_by_station: "bar")

2. Backend save order
   └─▶ Database: transactions table
       • created_by_station = 'bar'
       • kitchen_status = 'pending'

3. Kitchen device polling
   └─▶ GET /api/kitchen/orders?since_timestamp=...
       (every 5 seconds)

4. New orders detected
   └─▶ KitchenPollingService.newOrders updated

5. Trigger notifications
   └─▶ Sound + Vibration + Snackbar + Badge

6. Kitchen staff action
   ├─▶ Accept → PUT /api/kitchen/orders/{id}/status (status: in_progress)
   └─▶ Complete → PUT /api/kitchen/orders/{id}/status (status: completed)
```

---

## Instalasi & Setup

### 1. Dependencies

Dependencies sudah ditambahkan ke `pubspec.yaml`:

```yaml
dependencies:
  audioplayers: ^5.2.1    # Sound notification
  vibration: ^1.8.4       # Vibration alert
```

Install dependencies:
```bash
flutter pub get
```

### 2. Sound Asset

Download notification sound (format MP3) dan simpan di:
```
assets/sounds/kitchen_alert.mp3
```

**Rekomendasi Download:**
- [Mixkit Free Sound Effects](https://mixkit.co/free-sound-effects/notification/)
- [FreeSound.org](https://freesound.org/search/?q=notification+bell)

Contoh sound yang cocok:
- Bell notification
- Kitchen bell
- Ding sound
- Alert tone

### 3. Struktur File

```
lib/app/
├── models/
│   ├── station_type.dart              # Enum: kasir, bar, kitchen
│   ├── kitchen_order.dart             # Model untuk kitchen orders
│   └── transaction.dart               # Extended dengan kitchen fields
├── services/
│   ├── notification_service.dart      # Sound & vibration handler
│   ├── kitchen_polling_service.dart   # Polling logic
│   └── api_service.dart               # HTTP requests
├── modules/
│   └── kitchen_display/
│       ├── controllers/
│       │   └── kitchen_display_controller.dart
│       ├── bindings/
│       │   └── kitchen_display_binding.dart
│       ├── views/
│       │   └── kitchen_display_view.dart
│       └── widgets/
│           └── order_card_widget.dart
└── routes/
    ├── app_routes.dart                # Route: /kitchen-display
    └── app_pages.dart                 # Route registration
```

---

## Konfigurasi Backend

### 1. Database Migration

```sql
-- Tambahkan kolom ke tabel transactions
ALTER TABLE transactions
ADD COLUMN created_by_station ENUM('kasir', 'bar', 'kitchen') DEFAULT 'kasir',
ADD COLUMN kitchen_status ENUM('pending', 'in_progress', 'completed') NULL,
ADD COLUMN kitchen_acknowledged_at TIMESTAMP NULL,
ADD COLUMN kitchen_completed_at TIMESTAMP NULL;

-- Index untuk optimasi query polling
CREATE INDEX idx_kitchen_orders
ON transactions(created_at, kitchen_status)
WHERE kitchen_status IS NOT NULL;
```

### 2. API Endpoints

#### A. GET /api/kitchen/orders
**Purpose:** Polling endpoint untuk fetch kitchen orders

**Request:**
```http
GET /api/kitchen/orders?since_timestamp=2024-01-15T10:30:00Z&status=pending&limit=50
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `since_timestamp` | string (ISO 8601) | No | Fetch orders after this timestamp |
| `status` | string | No | Filter by status: `pending`, `in_progress`, `completed` |
| `limit` | integer | No | Max orders to return (default: 50) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "123",
      "order_number": "ORD-2024-001",
      "transaction_id": "456",
      "customer_name": "John Doe",
      "table_number": "A1",
      "created_at": "2024-01-15T10:30:00Z",
      "created_by_station": "bar",
      "kitchen_status": "pending",
      "items": [
        {
          "product_name": "Nasi Goreng",
          "quantity": 2,
          "variant_name": "Pedas",
          "notes": "Extra cabai"
        }
      ]
    }
  ],
  "meta": {
    "timestamp": "2024-01-15T10:35:00Z"
  }
}
```

**Backend Logic:**
```php
// Laravel Controller Example
public function getKitchenOrders(Request $request) {
    $query = Transaction::with('items')
        ->whereHas('items', function($q) {
            $q->where('available_kitchen', true);
        });

    // Filter by timestamp
    if ($request->has('since_timestamp')) {
        $query->where('created_at', '>', $request->since_timestamp);
    }

    // Filter by status
    if ($request->has('status')) {
        $query->where('kitchen_status', $request->status);
    }

    $orders = $query
        ->orderBy('created_at', 'desc')
        ->limit($request->input('limit', 50))
        ->get();

    // Transform data - hanya kirim kitchen items
    $kitchenOrders = $orders->map(function($order) {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'table_number' => $order->table_number,
            'created_at' => $order->created_at->toIso8601String(),
            'created_by_station' => $order->created_by_station,
            'kitchen_status' => $order->kitchen_status,
            'items' => $order->items
                ->filter(fn($item) => $item->available_kitchen)
                ->map(fn($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'variant_name' => $item->variant_name,
                    'notes' => $item->notes,
                ])
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $kitchenOrders,
        'meta' => [
            'timestamp' => now()->toIso8601String()
        ]
    ]);
}
```

#### B. PUT /api/kitchen/orders/{id}/status
**Purpose:** Update kitchen order status

**Request:**
```http
PUT /api/kitchen/orders/123/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "in_progress",
  "staff_name": "Chef Ali"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order status updated to in_progress"
}
```

**Backend Logic:**
```php
public function updateKitchenOrderStatus(Request $request, $id) {
    $order = Transaction::findOrFail($id);

    $order->kitchen_status = $request->status;

    if ($request->status === 'in_progress') {
        $order->kitchen_acknowledged_at = now();
    } elseif ($request->status === 'completed') {
        $order->kitchen_completed_at = now();
    }

    $order->save();

    return response()->json([
        'success' => true,
        'message' => "Order status updated to {$request->status}"
    ]);
}
```

#### C. Modify POST /api/pos/process-direct-payment
**Purpose:** Accept station source when creating order

**Request Modification:**
```json
{
  // ... existing fields ...
  "created_by_station": "bar"  // ← Tambahkan field ini
}
```

**Backend Logic:**
```php
public function processDirectPayment(Request $request) {
    // ... existing order creation logic ...

    $transaction = new Transaction();
    // ... set other fields ...
    $transaction->created_by_station = $request->input('created_by_station', 'kasir');

    // Set kitchen status if order has kitchen items
    $hasKitchenItems = collect($request->cart_items)
        ->some(fn($item) => $item['available_kitchen'] ?? false);

    if ($hasKitchenItems) {
        $transaction->kitchen_status = 'pending';
    }

    $transaction->save();

    // ... rest of the logic ...
}
```

### 3. Routes (Laravel)

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Kitchen endpoints
    Route::get('/kitchen/orders', [KitchenController::class, 'getKitchenOrders']);
    Route::put('/kitchen/orders/{id}/status', [KitchenController::class, 'updateKitchenOrderStatus']);
});
```

---

## Cara Penggunaan

### 1. Setup Device Stations

Setiap device perlu di-set sebagai station tertentu (kasir, bar, atau kitchen).

**Di Checkout Controller:**
```dart
// Set station type (simpan di SharedPreferences)
final checkoutController = Get.find<CheckoutController>();
await checkoutController.setStationType(StationType.bar); // atau kasir/kitchen
```

**Pilihan:**
- `StationType.kasir` - Device kasir (default)
- `StationType.bar` - Device bar
- `StationType.kitchen` - Device kitchen display

### 2. Akses Kitchen Display

**Via Navigation:**
```dart
// Dari mana saja di app
Get.toNamed(Routes.KITCHEN_DISPLAY);
```

**Via Route:**
```
/kitchen-display
```

**Tambah Menu Button:**
```dart
// Di drawer atau bottom navigation
ListTile(
  leading: Icon(Icons.restaurant_menu),
  title: Text('Kitchen Display'),
  onTap: () => Get.toNamed(Routes.KITCHEN_DISPLAY),
)
```

### 3. Workflow Kitchen Staff

#### a. Saat Order Baru Masuk
1. **Sound notification** otomatis berbunyi
2. **Snackbar** muncul: "🔔 Order Baru! 2 order baru dari BAR"
3. Order muncul di **Pending column**
4. **Red badge** muncul di AppBar dengan angka

#### b. Accept Order
1. Tap button **"Accept Order"** pada order card
2. Order pindah ke **In Progress column**
3. Status di backend update ke `in_progress`

#### c. Complete Order
1. Tap button **"Mark Complete"** pada order card
2. Order pindah ke **Completed column**
3. Status di backend update ke `completed`

### 4. Settings

**Toggle Sound:**
- Tap icon 🔊 di AppBar
- Icon berubah menjadi 🔇 saat muted

**Clear Badge:**
- Tap red badge di AppBar untuk reset counter

**Manual Refresh:**
- Tap icon ↻ di AppBar
- Atau pull-to-refresh pada screen

---

## API Reference

### Models

#### StationType Enum
```dart
enum StationType {
  kasir,    // Kasir/cashier station
  bar,      // Bar station
  kitchen,  // Kitchen station
}
```

#### KitchenOrderStatus Enum
```dart
enum KitchenOrderStatus {
  pending,      // Order baru, belum diproses
  inProgress,   // Sedang diproses kitchen
  completed,    // Sudah selesai
}
```

#### KitchenOrder Model
```dart
class KitchenOrder {
  final String id;
  final String orderNumber;
  final String customerName;
  final String? tableNumber;
  final DateTime orderTime;
  final List<KitchenOrderItem> items;
  final KitchenOrderStatus status;
  final StationType createdByStation;
  final String? notes;
}
```

### Services

#### NotificationService
```dart
class NotificationService extends GetxService {
  final RxBool soundEnabled = true.obs;
  final RxBool vibrationEnabled = false.obs;

  Future<void> playOrderNotification();
  Future<void> enableSound(bool enable);
  Future<void> enableVibration(bool enable);
}
```

**Usage:**
```dart
final notificationService = Get.find<NotificationService>();

// Play notification
await notificationService.playOrderNotification();

// Toggle sound
await notificationService.enableSound(true);
```

#### KitchenPollingService
```dart
class KitchenPollingService extends GetxService {
  final RxBool isPolling = false.obs;
  final RxList<KitchenOrder> newOrders = <KitchenOrder>[].obs;
  int pollingInterval = 5; // seconds

  void startPolling();
  void stopPolling();
  Future<void> refresh();
  void adjustPollingInterval({required bool hasNewOrders});
}
```

**Usage:**
```dart
final pollingService = Get.find<KitchenPollingService>();

// Start polling
pollingService.startPolling();

// Stop polling
pollingService.stopPolling();

// Listen to new orders
ever(pollingService.newOrders, (orders) {
  print('New orders received: ${orders.length}');
});
```

### Controllers

#### KitchenDisplayController
```dart
class KitchenDisplayController extends GetxController {
  final RxList<KitchenOrder> pendingOrders;
  final RxList<KitchenOrder> inProgressOrders;
  final RxList<KitchenOrder> completedOrders;
  final RxInt newOrderBadge;

  Future<void> fetchAllOrders();
  Future<void> acceptOrder(String orderId);
  Future<void> completeOrder(String orderId);
  void clearBadge();
}
```

---

## Testing Guide

### 1. Unit Testing Setup

**Test Polling Service:**
```dart
// test/services/kitchen_polling_service_test.dart
void main() {
  group('KitchenPollingService', () {
    test('should start polling when startPolling is called', () {
      final service = KitchenPollingService();
      service.startPolling();
      expect(service.isPolling.value, true);
    });

    test('should stop polling when stopPolling is called', () {
      final service = KitchenPollingService();
      service.startPolling();
      service.stopPolling();
      expect(service.isPolling.value, false);
    });
  });
}
```

### 2. Integration Testing

**Test Full Flow:**

**Setup:**
1. Device 1 (Bar): Login → Set station type = bar
2. Device 2 (Kitchen): Login → Navigate to Kitchen Display

**Test Steps:**

| Step | Action | Expected Result |
|------|--------|----------------|
| 1 | Bar create order dengan 2 Nasi Goreng | Order ter-save di backend |
| 2 | Wait 5-10 detik | Kitchen device dapat notif: sound + snackbar |
| 3 | Check Kitchen Display | Order muncul di Pending column |
| 4 | Check badge | Red badge menunjukkan "1" |
| 5 | Tap "Accept Order" | Order pindah ke In Progress |
| 6 | Tap "Mark Complete" | Order pindah ke Completed |
| 7 | Bar create 3 orders rapid | Kitchen dapat 3 notifikasi berturut-turut |
| 8 | Check badge | Badge menunjukkan "3" |
| 9 | Tap badge | Badge reset ke 0 |
| 10 | Toggle sound off | Icon berubah menjadi 🔇 |
| 11 | Create new order | No sound, tapi tetap ada snackbar |

### 3. Performance Testing

**Metric yang perlu diukur:**

| Metric | Target | Cara Test |
|--------|--------|-----------|
| Polling delay | < 10 detik | Create order → waktu sampai notif |
| Sound latency | < 500ms | Dari detect order → sound play |
| UI responsiveness | < 100ms | Tap button → order pindah column |
| Memory usage | < 200MB | Create 50+ orders, check memory |
| Battery drain | < 5%/hour | Run KDS 1 jam, check battery |

**Load Testing:**
```bash
# Create 20 orders simultaneously
for i in {1..20}; do
  curl -X POST http://localhost:8000/api/pos/process-direct-payment \
    -H "Authorization: Bearer $TOKEN" \
    -d '{...}' &
done
```

### 4. Edge Cases Testing

**Test Scenarios:**

1. **Network Loss:**
   - Matikan WiFi saat polling
   - Expected: Polling error di log, tapi app tidak crash

2. **Backend Down:**
   - Stop backend server
   - Expected: Error handling, show offline indicator

3. **Rapid Order Creation:**
   - Create 10 orders dalam 10 detik
   - Expected: Semua order masuk, no duplicate notification

4. **App Background:**
   - Navigate ke Home screen saat di KDS
   - Expected: Polling tetap jalan (tapi bisa add lifecycle aware nanti)

5. **Token Expired:**
   - Wait sampai token expire
   - Expected: Auto redirect ke login screen

---

## Troubleshooting

### Problem 1: No Sound Notification

**Symptoms:**
- Visual notification muncul, tapi tidak ada sound
- Icon volume di AppBar sudah ON

**Possible Causes:**
1. Sound file tidak ada
2. File path salah
3. Device muted
4. Permission issue

**Solutions:**

1. **Check file exists:**
```bash
ls -la assets/sounds/kitchen_alert.mp3
```

2. **Check pubspec.yaml:**
```yaml
flutter:
  assets:
    - assets/sounds/
```

3. **Re-run flutter:**
```bash
flutter clean
flutter pub get
flutter run
```

4. **Test sound manually:**
```dart
// Test di main.dart
final audioPlayer = AudioPlayer();
await audioPlayer.play(AssetSource('sounds/kitchen_alert.mp3'));
```

5. **Check device volume:**
   - Pastikan device volume tidak muted
   - Test dengan play music di device

---

### Problem 2: Polling Not Working

**Symptoms:**
- Kitchen Display terbuka, tapi tidak dapat order baru
- Backend sudah ada order, tapi tidak muncul

**Debug Steps:**

1. **Check polling status:**
```dart
// Di Kitchen Display Controller
print('Is polling: ${_pollingService.isPolling.value}');
```

2. **Check backend endpoint:**
```bash
curl -X GET "http://localhost:8000/api/kitchen/orders" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

3. **Check console logs:**
```
# Should see these logs every 5 seconds:
[log] Starting kitchen order polling...
[log] Received 2 new kitchen orders
```

4. **Check token:**
```dart
final token = await AuthService.getToken();
print('Token: $token');
```

**Common Issues:**

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Token expired, re-login |
| 404 Not Found | Backend endpoint belum dibuat |
| 500 Server Error | Check backend logs |
| Empty response | No kitchen orders di database |

---

### Problem 3: Orders Not Moving Between Columns

**Symptoms:**
- Tap "Accept Order" tapi order tetap di Pending
- No error message

**Debug:**

1. **Check network request:**
```dart
// Di acceptOrder method
print('Updating order ${orderId} to in_progress');
final response = await _apiService.updateKitchenOrderStatus(...);
print('Response: $response');
```

2. **Check backend:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log
```

3. **Check order ID:**
```dart
// Pastikan order ID valid
print('Order ID: ${order.id}');
```

---

### Problem 4: Multiple Duplicate Notifications

**Symptoms:**
- 1 order baru, tapi sound berbunyi 3x
- Snackbar muncul berkali-kali

**Causes:**
- Polling service di-init multiple times
- `ever()` listener registered multiple times

**Solutions:**

1. **Check service initialization:**
```dart
// main.dart - pastikan hanya 1x
Get.put<KitchenPollingService>(KitchenPollingService(), permanent: true);
```

2. **Check listener:**
```dart
// KitchenDisplayController.onInit()
// Pastikan ever() hanya dipanggil 1x
@override
void onInit() {
  super.onInit();
  _startPolling();
  _listenToNewOrders(); // ← Hanya 1x
}
```

---

### Problem 5: High Battery Drain

**Symptoms:**
- Battery drop 20% dalam 1 jam
- Device panas

**Optimization:**

1. **Adjust polling interval:**
```dart
// Dari 5 detik → 10 detik
pollingInterval = 10;
```

2. **Add lifecycle awareness:**
```dart
// Stop polling saat app di background
@override
void didChangeAppLifecycleState(AppLifecycleState state) {
  if (state == AppLifecycleState.paused) {
    _pollingService.stopPolling();
  } else if (state == AppLifecycleState.resumed) {
    _pollingService.startPolling();
  }
}
```

3. **Limit completed orders:**
```dart
// Sudah implemented - max 50 completed orders
if (completedOrders.length > 50) {
  completedOrders.removeRange(50, completedOrders.length);
}
```

---

## Performance Optimization Tips

### 1. Smart Polling
```dart
// Adjust interval based on activity
void adjustPollingInterval({required bool hasNewOrders}) {
  if (hasNewOrders) {
    pollingInterval = 3; // Active: poll faster
  } else {
    pollingInterval = 10; // Idle: poll slower
  }
}
```

### 2. Pagination
```dart
// Fetch only recent orders
final response = await _apiService.getKitchenOrders(
  status: 'pending',
  limit: 20, // Don't fetch all
);
```

### 3. Cache Completed Orders
```dart
// Store completed orders di local storage
final prefs = await SharedPreferences.getInstance();
await prefs.setString('completed_orders', jsonEncode(orders));
```

---

## Future Enhancements

### Short-term (1-2 bulan)
- [ ] WebSocket support untuk real-time push
- [ ] Order preparation timer dengan countdown
- [ ] Kitchen staff assignment per order
- [ ] Print integration (auto-print ke kitchen printer)

### Mid-term (3-6 bulan)
- [ ] Firebase Cloud Messaging untuk background notification
- [ ] Order priority/rush flag
- [ ] Multi-language support (EN/ID)
- [ ] Historical analytics (avg preparation time, etc)

### Long-term (6-12 bulan)
- [ ] AI-based order time prediction
- [ ] Voice command support ("Mark order 123 complete")
- [ ] Integration dengan inventory system
- [ ] Mobile app untuk kitchen staff (Android/iOS)

---

## Support & Contact

Untuk pertanyaan atau issue terkait Kitchen Notification System:

- **Documentation**: `/docs/KITCHEN_NOTIFICATION_SYSTEM.md`
- **Plan File**: `~/.claude/plans/wondrous-skipping-peach.md`
- **GitHub Issues**: (jika ada repository)

---

**Version:** 1.0.0
**Last Updated:** 2026-02-01
**Author:** Claude Code Assistant
**License:** MIT
