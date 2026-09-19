# Kitchen FCM Push Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kitchen Display menerima order baru / item tambahan secara realtime lewat FCM (termasuk saat app di background), dengan sync penuh dari server sebagai sumber kebenaran sehingga tidak ada order yang hilang.

**Architecture:** Laravel mengirim pesan FCM ke topic `kitchen-orders` setelah DB commit dan setelah response HTTP (`DB::afterCommit` + `defer`), lewat interface `KitchenPushNotifier`. Flutter memperlakukan FCM hanya sebagai sinyal: setiap sinyal (push, app resume, timer 60 detik, refresh manual) memicu `sync()` yang mengambil ulang seluruh order pending hari ini lalu di-diff terhadap list di layar.

**Tech Stack:** Laravel 12 / PHP 8.2+, `kreait/laravel-firebase`, PHPUnit (SQLite in-memory); Flutter 3.44, GetX, `firebase_core`, `firebase_messaging`, Kotlin (notification channel).

**Spec:** `docs/superpowers/specs/2026-09-18-kitchen-fcm-push-design.md`

## Catatan eksekusi (2026-09-19) — penyimpangan dari plan ini

Plan ini dieksekusi dengan tiga perubahan. Kode di repo adalah acuan; bagian plan yang bertentangan
dengan catatan ini sudah tidak berlaku.

1. **Task 2–3 tidak memakai `kreait/laravel-firebase`.** `composer require` gagal: library itu butuh
   `lcobucci/jwt` ≥ 4.3, sedangkan project terkunci di 4.0.4 oleh `tymon/jwt-auth`. Sebagai gantinya
   dibuat `app/Services/Push/FcmClient.php` (FCM HTTP v1 langsung: JWT RS256 → token OAuth →
   `messages:send`) dengan test `tests/Feature/FcmClientTest.php` dan fixture key sekali-pakai
   `tests/Fixtures/fcm-test-service-account.json`. `FcmKitchenPushNotifier` dan `kitchen:push-test`
   memakai `FcmClient::send(array $message)`. Tidak ada `config/firebase.php`; konfigurasi ada di
   `config/services.php` key `fcm` (`enabled`, `kitchen_topic`, `credentials`, `timeout`), env
   `FCM_TIMEOUT` menggantikan `FIREBASE_HTTP_CLIENT_TIMEOUT`.
2. **Task 5 dan 6 diverifikasi dengan satu kali build APK** di akhir Task 6 (bukan dua kali).
3. **Tidak ada commit.** Semua perubahan dibiarkan di working tree branch `feat/kitchen-fcm-push`
   di kedua repo, menunggu review user.

## Global Constraints

- Dua repo git terpisah: `D:\KAFE_DILANGIT\phpunit` (branch `main`) dan `D:\KAFE_DILANGIT\pos_dilangit` (branch `membuat-paket`, **punya perubahan uncommitted milik user — jangan di-stage, jangan di-revert**). Kerja di branch baru `feat/kitchen-fcm-push` di masing-masing repo. `git add` selalu per file, tidak pernah `git add -A` / `git add .`.
- Backend test **wajib** dijalankan dengan SQLite in-memory (MySQL lokal mati):
  `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter <Nama>`
  Jangan pernah menjalankan `php artisan config:cache` — dengan config ter-cache, `RefreshDatabase` akan menghapus DB dev.
- Primary key non-standar: `id_order`, `id_kitchen_order`, `id_kitchen_order_item`. User: `id`.
- Kontrak `GET /kitchen/orders` dan `POST /kitchen/orders` **tidak boleh berubah** (APK lama masih polling).
- Kegagalan FCM tidak boleh menggagalkan / memperlambat order. HTTP timeout ke FCM: 5 detik.
- Nilai tetap (harus sama persis di backend, Kotlin, dan Dart):
  - topic default: `kitchen-orders` (lokal: `kitchen-orders-dev`)
  - notification channel id: `kitchen_orders_v1`
  - nama sound resource: `kitchen_alert`
  - `data.type`: `kitchen_order_created`, `kitchen_items_added`, `kitchen_status_changed`
  - route Kitchen Display: `/kitchen-display`; route utama setelah login: `/main-navigation`
  - SharedPreferences key: `kitchen_push_enabled`
- `storage/app/firebase/firebase-credentials.json` adalah secret: jangan di-commit, jangan dicetak ke output.
- Android saja. Jangan menyentuh folder `windows/`, `web/`, `ios/`, `linux/`, `macos/`.
- Ikuti gaya kode sekitar (komentar Indonesia/Inggris campur seperti file yang ada, `print()` untuk debug di Flutter).

---

## File Structure

**Backend (`phpunit/`)**

| File | Tanggung jawab |
|---|---|
| `app/Contracts/KitchenPushNotifier.php` (baru) | Interface 3 event push |
| `app/Services/Push/NullKitchenPushNotifier.php` (baru) | No-op saat `FCM_ENABLED=false` |
| `app/Services/Push/KitchenPushDispatcher.php` (baru) | Satu-satunya tempat `afterCommit` + `defer` + try/catch |
| `app/Services/Push/FcmKitchenPushNotifier.php` (baru) | Bentuk payload FCM + peredam bunyi |
| `app/Console/Commands/KitchenPushTest.php` (baru) | `kitchen:push-test` untuk diagnosa |
| `app/Models/KitchenOrder.php` (ubah) | Panggil dispatcher di `createFromOrderItems`, `addItems` |
| `app/Http/Controllers/Api/KitchenController.php` (ubah) | Panggil dispatcher saat status berubah |
| `app/Providers/AppServiceProvider.php` (ubah) | Binding interface |
| `config/services.php`, `config/firebase.php`, `.env.example`, `phpunit.xml` (ubah/baru) | Konfigurasi |
| `tests/Support/FakeKitchenPushNotifier.php`, `tests/Feature/KitchenPushTriggerTest.php`, `tests/Feature/FcmKitchenPushNotifierTest.php` (baru) | Test |

**Flutter (`pos_dilangit/`)**

| File | Tanggung jawab |
|---|---|
| `lib/app/modules/kitchen_display/utils/kitchen_order_diff.dart` (baru) | Fungsi murni: diff list pending |
| `lib/app/services/sync_coalescer.dart` (baru) | Kelas murni: cegah sync paralel, jamin satu sync susulan |
| `lib/app/services/kitchen_sync_service.dart` (baru) | Pemicu sync: timer 60s + app resume |
| `lib/app/services/push_service.dart` (baru) | FCM: subscribe topic, handler foreground/tap |
| `lib/app/services/kitchen_polling_service.dart` (**hapus**) | Digantikan |
| `lib/app/services/notification_service.dart` (ubah) | Tambah `playOrderChime()` |
| `lib/app/modules/home/controllers/home_controller.dart`, `lib/app/modules/auth/controllers/auth_controller.dart` (ubah) | Unsubscribe saat logout eksplisit |
| `lib/app/services/api_service.dart` (ubah) | Hapus `createKitchenOrder` yang redundan |
| `lib/app/modules/transaction/dialogs/add_item_dialog.dart` (ubah) | Hapus 2 pemanggilan redundan |
| `lib/app/modules/kitchen_display/controllers/kitchen_display_controller.dart`, `views/kitchen_display_view.dart` (ubah) | Pakai sync + diff, toggle push |
| `lib/main.dart` (ubah) | Init Firebase, daftar service |
| `android/settings.gradle.kts`, `android/app/build.gradle.kts`, `AndroidManifest.xml`, `MainActivity.kt`, `res/raw/kitchen_alert.mp3` | Setup Android |
| `test/kitchen_order_diff_test.dart`, `test/sync_coalescer_test.dart` (baru) | Test |

---

### Task 1: Backend — kontrak push, dispatcher, dan titik trigger

**Files:**
- Create: `app/Contracts/KitchenPushNotifier.php`
- Create: `app/Services/Push/NullKitchenPushNotifier.php`
- Create: `app/Services/Push/KitchenPushDispatcher.php`
- Create: `tests/Support/FakeKitchenPushNotifier.php`
- Create: `tests/Feature/KitchenPushTriggerTest.php`
- Modify: `app/Models/KitchenOrder.php` (`addItems`, `createFromOrderItems`)
- Modify: `app/Http/Controllers/Api/KitchenController.php` (`updateKitchenOrderStatusNewTable`, setelah `$kitchenOrder->save();`)
- Modify: `app/Providers/AppServiceProvider.php` (`register()`)

**Interfaces:**
- Produces:
  - `App\Contracts\KitchenPushNotifier` dengan `orderCreated(KitchenOrder $kitchenOrder): void`, `itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void`, `statusChanged(KitchenOrder $kitchenOrder): void`
  - `App\Services\Push\KitchenPushDispatcher::afterCommit(\Closure $send): void` — `$send` menerima `KitchenPushNotifier`
  - `Tests\Support\FakeKitchenPushNotifier` dengan properti publik `array $calls` berisi `[method, id_kitchen_order, extra]`

- [ ] **Step 1: Buat branch**

```bash
cd /d/KAFE_DILANGIT/phpunit && git checkout -b feat/kitchen-fcm-push
```

- [ ] **Step 2: Tulis fake notifier untuk test**

`tests/Support/FakeKitchenPushNotifier.php`:

```php
<?php

namespace Tests\Support;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;

class FakeKitchenPushNotifier implements KitchenPushNotifier
{
    /** @var array<int, array{0: string, 1: int, 2: mixed}> */
    public array $calls = [];

    public function orderCreated(KitchenOrder $kitchenOrder): void
    {
        $this->calls[] = ['orderCreated', $kitchenOrder->id_kitchen_order, null];
    }

    public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void
    {
        $this->calls[] = ['itemsAdded', $kitchenOrder->id_kitchen_order, $newItemsCount];
    }

    public function statusChanged(KitchenOrder $kitchenOrder): void
    {
        $this->calls[] = ['statusChanged', $kitchenOrder->id_kitchen_order, $kitchenOrder->status];
    }
}
```

Pastikan `composer.json` punya `"autoload-dev": {"psr-4": {"Tests\\": "tests/"}}` (standar Laravel). Kalau belum ada, tambahkan lalu `composer dump-autoload`.

- [ ] **Step 3: Tulis test yang gagal**

`tests/Feature/KitchenPushTriggerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeKitchenPushNotifier;
use Tests\TestCase;

class KitchenPushTriggerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FakeKitchenPushNotifier $push;

    protected function setUp(): void
    {
        parent::setUp();

        // defer() dijalankan langsung supaya bisa di-assert tanpa siklus HTTP penuh
        $this->withoutDefer();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);

        $this->user = User::factory()->create(['username' => 'kasir-push-test']);
        Sanctum::actingAs($this->user);

        $this->push = new FakeKitchenPushNotifier();
        $this->app->instance(KitchenPushNotifier::class, $this->push);
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'order_number' => 'ORD-PUSH-' . Str::random(8),
            'id_user' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);
    }

    private function items(int $count): array
    {
        return array_map(fn ($i) => ['product_name' => "Nasi Goreng {$i}", 'quantity' => 1], range(1, $count));
    }

    public function test_creating_kitchen_order_sends_order_created_once(): void
    {
        $kitchenOrder = KitchenOrder::createFromOrderItems($this->makeOrder(), $this->items(2));

        $this->assertSame([['orderCreated', $kitchenOrder->id_kitchen_order, null]], $this->push->calls);
    }

    public function test_merging_items_into_pending_order_sends_items_added(): void
    {
        $order = $this->makeOrder();
        $first = KitchenOrder::findOrCreateForOrder($order, $this->items(1));
        $this->push->calls = [];

        $second = KitchenOrder::findOrCreateForOrder($order, $this->items(2));

        $this->assertSame($first->id_kitchen_order, $second->id_kitchen_order, 'Harus merge ke kitchen order pending yang sama');
        $this->assertSame([['itemsAdded', $first->id_kitchen_order, 2]], $this->push->calls);
    }

    public function test_push_waits_for_commit(): void
    {
        $order = $this->makeOrder();

        DB::transaction(function () use ($order) {
            KitchenOrder::createFromOrderItems($order, $this->items(1));
            $this->assertSame([], $this->push->calls, 'Push tidak boleh terkirim sebelum commit');
        });

        $this->assertCount(1, $this->push->calls);
    }

    public function test_rolled_back_transaction_sends_nothing(): void
    {
        $order = $this->makeOrder();

        DB::beginTransaction();
        KitchenOrder::createFromOrderItems($order, $this->items(1));
        DB::rollBack();

        $this->assertSame([], $this->push->calls);
    }

    public function test_status_update_endpoint_sends_status_changed(): void
    {
        $kitchenOrder = KitchenOrder::createFromOrderItems($this->makeOrder(), $this->items(1));
        $this->push->calls = [];

        $this->putJson("/api/kitchen/orders/{$kitchenOrder->id_kitchen_order}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertSame([['statusChanged', $kitchenOrder->id_kitchen_order, 'completed']], $this->push->calls);
    }

    public function test_create_endpoint_sends_exactly_one_push(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/api/kitchen/orders', [
            'order_id' => $order->id_order,
            'items' => $this->items(3),
            'station' => 'bar',
        ])->assertCreated();

        $this->assertCount(1, $this->push->calls);
        $this->assertSame('orderCreated', $this->push->calls[0][0]);
    }

    public function test_push_failure_never_breaks_the_order(): void
    {
        $this->app->bind(KitchenPushNotifier::class, fn () => new class implements KitchenPushNotifier {
            public function orderCreated(KitchenOrder $kitchenOrder): void { throw new \RuntimeException('FCM down'); }
            public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void { throw new \RuntimeException('FCM down'); }
            public function statusChanged(KitchenOrder $kitchenOrder): void { throw new \RuntimeException('FCM down'); }
        });
        $order = $this->makeOrder();

        $this->postJson('/api/kitchen/orders', [
            'order_id' => $order->id_order,
            'items' => $this->items(1),
        ])->assertCreated();

        $this->assertDatabaseCount('kitchen_orders', 1);
    }
}
```

- [ ] **Step 4: Jalankan — pastikan gagal**

Run: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter KitchenPushTriggerTest`
Expected: FAIL — `Interface "App\Contracts\KitchenPushNotifier" not found`.

- [ ] **Step 5: Buat interface**

`app/Contracts/KitchenPushNotifier.php`:

```php
<?php

namespace App\Contracts;

use App\Models\KitchenOrder;

interface KitchenPushNotifier
{
    /** Kitchen order baru dibuat (berbunyi di tablet dapur). */
    public function orderCreated(KitchenOrder $kitchenOrder): void;

    /** Item ditambahkan ke kitchen order yang masih pending (berbunyi). */
    public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void;

    /** Status berubah — sinyal senyap supaya tablet lain ikut sync. */
    public function statusChanged(KitchenOrder $kitchenOrder): void;
}
```

- [ ] **Step 6: Buat null notifier**

`app/Services/Push/NullKitchenPushNotifier.php`:

```php
<?php

namespace App\Services\Push;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;

/**
 * Dipakai saat FCM_ENABLED=false (lokal, testing, atau server tanpa kredensial).
 */
class NullKitchenPushNotifier implements KitchenPushNotifier
{
    public function orderCreated(KitchenOrder $kitchenOrder): void
    {
    }

    public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void
    {
    }

    public function statusChanged(KitchenOrder $kitchenOrder): void
    {
    }
}
```

- [ ] **Step 7: Buat dispatcher**

`app/Services/Push/KitchenPushDispatcher.php`:

```php
<?php

namespace App\Services\Push;

use App\Contracts\KitchenPushNotifier;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class KitchenPushDispatcher
{
    /**
     * Kirim push setelah transaksi DB commit DAN setelah response HTTP terkirim.
     *
     * - afterCommit: transaksi yang rollback tidak pernah mengirim push.
     * - defer: checkout kasir tidak menunggu FCM, dan tidak butuh queue worker.
     * - Notifier di-resolve di dalam try supaya kredensial yang salah pun tidak
     *   pernah menggagalkan order.
     */
    public static function afterCommit(Closure $send): void
    {
        DB::afterCommit(static function () use ($send) {
            defer(static function () use ($send) {
                try {
                    $send(app(KitchenPushNotifier::class));
                } catch (Throwable $e) {
                    Log::warning('Kitchen push failed', [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]);
                }
            })->always();
        });
    }
}
```

- [ ] **Step 8: Binding default di `AppServiceProvider::register()`**

Ganti isi `register()` (saat ini hanya `//`):

```php
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\KitchenPushNotifier::class,
            \App\Services\Push\NullKitchenPushNotifier::class
        );
    }
```

- [ ] **Step 9: Pasang trigger di model `KitchenOrder`**

Di `app/Models/KitchenOrder.php` tambahkan import di bawah `use` yang ada:

```php
use App\Contracts\KitchenPushNotifier;
use App\Services\Push\KitchenPushDispatcher;
```

Di `addItems()`, tepat setelah blok `\Illuminate\Support\Facades\DB::transaction(function () use ($items) { ... });` dan sebelum kurung tutup method:

```php
        $newItemsCount = count($items);
        KitchenPushDispatcher::afterCommit(
            fn (KitchenPushNotifier $push) => $push->itemsAdded($this, $newItemsCount)
        );
```

Di `createFromOrderItems()`, tepat sebelum `return $kitchenOrder;`:

```php
        KitchenPushDispatcher::afterCommit(
            fn (KitchenPushNotifier $push) => $push->orderCreated($kitchenOrder)
        );
```

Perbaiki juga komentar yang menyesatkan di `addItems()` — ganti
`// Touch updated_at so kitchen display polling detects the change` menjadi
`// Touch updated_at; perubahan item dikabarkan ke Kitchen Display lewat push (lihat bawah)`.

- [ ] **Step 10: Pasang trigger status di `KitchenController`**

Di `updateKitchenOrderStatusNewTable()`, tepat setelah `$kitchenOrder->save();`:

```php
        if ($oldStatus !== $kitchenOrder->status) {
            \App\Services\Push\KitchenPushDispatcher::afterCommit(
                fn (\App\Contracts\KitchenPushNotifier $push) => $push->statusChanged($kitchenOrder)
            );
        }
```

- [ ] **Step 11: Jalankan — pastikan lolos**

Run: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter KitchenPushTriggerTest`
Expected: PASS, 7 tests.

Run juga regresi: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter SalesReportTest`
Expected: PASS, 4 tests.

- [ ] **Step 12: Commit**

```bash
git add app/Contracts/KitchenPushNotifier.php app/Services/Push/NullKitchenPushNotifier.php app/Services/Push/KitchenPushDispatcher.php app/Providers/AppServiceProvider.php app/Models/KitchenOrder.php app/Http/Controllers/Api/KitchenController.php tests/Support/FakeKitchenPushNotifier.php tests/Feature/KitchenPushTriggerTest.php
git commit -m "feat(kitchen): dispatch push events after commit for kitchen orders"
```

---

### Task 2: Backend — notifier FCM, konfigurasi, dan peredam bunyi

**Files:**
- Create: `app/Services/Push/FcmKitchenPushNotifier.php`
- Create: `tests/Feature/FcmKitchenPushNotifierTest.php`
- Create: `config/firebase.php` (hasil `vendor:publish`)
- Modify: `composer.json`, `composer.lock` (via composer)
- Modify: `config/services.php` (tambah key `fcm`)
- Modify: `app/Providers/AppServiceProvider.php` (`register()`)
- Modify: `.env.example`, `.env` (lokal, tidak di-commit), `phpunit.xml`

**Interfaces:**
- Consumes: `App\Contracts\KitchenPushNotifier` (Task 1)
- Produces:
  - `App\Services\Push\FcmKitchenPushNotifier::__construct(\Kreait\Firebase\Contract\Messaging $messaging, string $topic)`; konstanta `CHANNEL_ID = 'kitchen_orders_v1'`, `SOUND = 'kitchen_alert'`. Method **melempar** exception kalau FCM gagal (yang menangkap adalah `KitchenPushDispatcher`).
  - `config('services.fcm.enabled')` (bool), `config('services.fcm.kitchen_topic')` (string)

- [ ] **Step 1: Install library**

```bash
composer require kreait/laravel-firebase
php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider" --tag=config
```

Expected: `config/firebase.php` terbentuk. Buka file itu dan pastikan ada `env('FIREBASE_CREDENTIALS')` dan `env('FIREBASE_HTTP_CLIENT_TIMEOUT')`; kalau nama env-nya berbeda di versi yang terpasang, pakai nama yang ada di file tersebut pada Step 3.

- [ ] **Step 2: Tambah config `fcm`**

Di `config/services.php`, tambahkan entry baru di dalam array return (sebelum `];` penutup):

```php
    'fcm' => [
        'enabled' => (bool) env('FCM_ENABLED', false),
        'kitchen_topic' => env('FCM_KITCHEN_TOPIC', 'kitchen-orders'),
    ],
```

- [ ] **Step 3: Env**

Tambahkan ke akhir `.env.example`:

```dotenv

# Firebase Cloud Messaging — push order ke Kitchen Display
# File kredensial service account TIDAK di-commit; upload manual ke server.
FIREBASE_CREDENTIALS=storage/app/firebase/firebase-credentials.json
FIREBASE_HTTP_CLIENT_TIMEOUT=5
FCM_ENABLED=false
# Pakai kitchen-orders-dev di lokal supaya testing tidak membunyikan tablet production
FCM_KITCHEN_TOPIC=kitchen-orders
```

Tambahkan blok yang sama ke `.env` lokal, tetapi dengan `FCM_ENABLED=true` dan `FCM_KITCHEN_TOPIC=kitchen-orders-dev`.

Di `phpunit.xml`, di dalam `<php>`, tambahkan supaya test tidak pernah mengirim push sungguhan:

```xml
        <env name="FCM_ENABLED" value="false"/>
```

- [ ] **Step 4: Tulis test yang gagal**

`tests/Feature/FcmKitchenPushNotifierTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Push\FcmKitchenPushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Messaging;
use Mockery;
use Tests\TestCase;

class FcmKitchenPushNotifierTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array> payload FCM yang "terkirim", sudah dinormalisasi ke array */
    private array $sent = [];

    private function notifier(): FcmKitchenPushNotifier
    {
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->andReturnUsing(function ($message) {
            $this->sent[] = json_decode(json_encode($message), true);

            return [];
        });

        return new FcmKitchenPushNotifier($messaging, 'kitchen-orders-test');
    }

    private function makeKitchenOrder(int $items = 2): KitchenOrder
    {
        DB::table('roles')->insert(['id' => 1, 'name' => 'kasir', 'display_name' => 'Kasir']);
        $user = User::factory()->create(['username' => 'kasir-fcm-test']);

        $order = Order::create([
            'order_number' => 'ORD-FCM-' . Str::random(6),
            'id_user' => $user->id,
            'order_type' => 'dine_in',
            'table_number' => '5',
            'status' => 'pending',
            'subtotal' => 0,
            'total_amount' => 0,
            'order_date' => now(),
        ]);

        return KitchenOrder::createFromOrderItems(
            $order,
            array_map(fn ($i) => ['product_name' => "Menu {$i}", 'quantity' => 1], range(1, $items)),
            'bar'
        );
    }

    public function test_order_created_sends_audible_notification_to_topic(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(2);

        $this->notifier()->orderCreated($kitchenOrder);

        $this->assertCount(1, $this->sent);
        $payload = $this->sent[0];
        $this->assertSame('kitchen-orders-test', $payload['topic']);
        $this->assertSame("Order Baru #{$kitchenOrder->order_number}", $payload['notification']['title']);
        $this->assertSame('Meja 5 · 2 item · dari Bar', $payload['notification']['body']);
        $this->assertSame('kitchen_order_created', $payload['data']['type']);
        $this->assertSame((string) $kitchenOrder->id_kitchen_order, $payload['data']['kitchen_order_id']);
        $this->assertSame($kitchenOrder->order_number, $payload['data']['order_number']);
        $this->assertSame('high', $payload['android']['priority']);
        $this->assertSame('kitchen_orders_v1', $payload['android']['notification']['channel_id']);
        $this->assertSame('kitchen_alert', $payload['android']['notification']['sound']);
        $this->assertSame("kitchen-order-{$kitchenOrder->id_kitchen_order}", $payload['android']['notification']['tag']);
    }

    public function test_second_alert_within_window_is_downgraded_to_silent_data_message(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);
        $notifier = $this->notifier();

        $notifier->itemsAdded($kitchenOrder, 1);
        $notifier->itemsAdded($kitchenOrder, 1);

        $this->assertCount(2, $this->sent);
        $this->assertSame("Tambahan Order #{$kitchenOrder->order_number}", $this->sent[0]['notification']['title']);
        $this->assertSame('+1 item · Meja 5', $this->sent[0]['notification']['body']);
        $this->assertArrayNotHasKey('notification', $this->sent[1], 'Push susulan harus senyap');
        $this->assertSame('kitchen_items_added', $this->sent[1]['data']['type']);
        $this->assertSame('high', $this->sent[1]['android']['priority'], 'Sinyal sync tetap harus cepat sampai');
    }

    public function test_status_changed_is_silent(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);

        $this->notifier()->statusChanged($kitchenOrder);

        $this->assertCount(1, $this->sent);
        $this->assertArrayNotHasKey('notification', $this->sent[0]);
        $this->assertSame('kitchen_status_changed', $this->sent[0]['data']['type']);
        $this->assertSame('pending', $this->sent[0]['data']['status']);
        $this->assertSame('normal', $this->sent[0]['android']['priority']);
    }

    public function test_order_without_table_omits_table_label(): void
    {
        $kitchenOrder = $this->makeKitchenOrder(1);
        $kitchenOrder->update(['table_number' => null]);

        $this->notifier()->orderCreated($kitchenOrder->fresh());

        $this->assertSame('1 item · dari Bar', $this->sent[0]['notification']['body']);
    }
}
```

- [ ] **Step 5: Jalankan — pastikan gagal**

Run: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter FcmKitchenPushNotifierTest`
Expected: FAIL — `Class "App\Services\Push\FcmKitchenPushNotifier" not found`.

- [ ] **Step 6: Implementasi notifier FCM**

`app/Services/Push/FcmKitchenPushNotifier.php`:

```php
<?php

namespace App\Services\Push;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmKitchenPushNotifier implements KitchenPushNotifier
{
    /** Harus sama dengan channel yang dibuat di MainActivity.kt (Flutter). */
    public const CHANNEL_ID = 'kitchen_orders_v1';

    /** Nama file di android/app/src/main/res/raw tanpa ekstensi. */
    public const SOUND = 'kitchen_alert';

    /** Cart berisi N item = N push beruntun; hanya satu yang boleh berbunyi per jendela ini. */
    private const ALERT_WINDOW_SECONDS = 5;

    public function __construct(
        private Messaging $messaging,
        private string $topic,
    ) {
    }

    public function orderCreated(KitchenOrder $kitchenOrder): void
    {
        $itemsCount = $kitchenOrder->items()->count();
        $station = ucfirst($kitchenOrder->created_by_station ?? 'kasir');

        $this->sendAlert(
            $kitchenOrder,
            'kitchen_order_created',
            "Order Baru #{$kitchenOrder->order_number}",
            $this->joinParts([$this->tableLabel($kitchenOrder), "{$itemsCount} item", "dari {$station}"])
        );
    }

    public function itemsAdded(KitchenOrder $kitchenOrder, int $newItemsCount): void
    {
        $this->sendAlert(
            $kitchenOrder,
            'kitchen_items_added',
            "Tambahan Order #{$kitchenOrder->order_number}",
            $this->joinParts(["+{$newItemsCount} item", $this->tableLabel($kitchenOrder)])
        );
    }

    public function statusChanged(KitchenOrder $kitchenOrder): void
    {
        $this->sendSilent($kitchenOrder, 'kitchen_status_changed', 'normal', '300s');
    }

    private function sendAlert(KitchenOrder $kitchenOrder, string $type, string $title, string $body): void
    {
        $isFirstInWindow = Cache::add(
            "kitchen-push-alert:{$kitchenOrder->id_kitchen_order}",
            true,
            self::ALERT_WINDOW_SECONDS
        );

        if (! $isFirstInWindow) {
            // Tetap kirim sinyal supaya Kitchen Display sync, tapi tanpa bunyi
            $this->sendSilent($kitchenOrder, $type, 'high', '3600s');

            return;
        }

        $this->messaging->send(CloudMessage::fromArray([
            'topic' => $this->topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->data($kitchenOrder, $type),
            'android' => [
                'priority' => 'high',
                'ttl' => '3600s',
                'notification' => [
                    'channel_id' => self::CHANNEL_ID,
                    'sound' => self::SOUND,
                    'tag' => "kitchen-order-{$kitchenOrder->id_kitchen_order}",
                ],
            ],
        ]));
    }

    private function sendSilent(KitchenOrder $kitchenOrder, string $type, string $priority, string $ttl): void
    {
        $this->messaging->send(CloudMessage::fromArray([
            'topic' => $this->topic,
            'data' => $this->data($kitchenOrder, $type),
            'android' => [
                'priority' => $priority,
                'ttl' => $ttl,
            ],
        ]));
    }

    /** FCM mewajibkan semua nilai data bertipe string. */
    private function data(KitchenOrder $kitchenOrder, string $type): array
    {
        return [
            'type' => $type,
            'kitchen_order_id' => (string) $kitchenOrder->id_kitchen_order,
            'order_number' => (string) $kitchenOrder->order_number,
            'status' => (string) $kitchenOrder->status,
        ];
    }

    private function tableLabel(KitchenOrder $kitchenOrder): ?string
    {
        return filled($kitchenOrder->table_number) ? "Meja {$kitchenOrder->table_number}" : null;
    }

    private function joinParts(array $parts): string
    {
        return implode(' · ', array_filter($parts));
    }
}
```

- [ ] **Step 7: Jalankan — pastikan lolos**

Run: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter FcmKitchenPushNotifierTest`
Expected: PASS, 4 tests.

Kalau assertion struktur payload gagal karena versi `kreait/firebase-php` menormalisasi key secara berbeda (mis. `ttl`), sesuaikan **assertion** mengikuti output nyata `json_encode($message)` — struktur yang dikirim ke FCM itulah yang benar. Jangan melemahkan assertion `topic`, `channel_id`, `sound`, `data.type`, dan ada/tidaknya `notification`.

- [ ] **Step 8: Binding berdasarkan `FCM_ENABLED`**

Ganti isi `AppServiceProvider::register()` dari Task 1:

```php
    public function register(): void
    {
        $this->app->bind(\App\Contracts\KitchenPushNotifier::class, function ($app) {
            if (! config('services.fcm.enabled')) {
                return new \App\Services\Push\NullKitchenPushNotifier();
            }

            return new \App\Services\Push\FcmKitchenPushNotifier(
                $app->make(\Kreait\Firebase\Contract\Messaging::class),
                config('services.fcm.kitchen_topic')
            );
        });
    }
```

- [ ] **Step 9: Jalankan semua test kitchen + regresi**

Run: `DB_CONNECTION=sqlite DB_DATABASE=":memory:" php artisan test --filter "KitchenPushTriggerTest|FcmKitchenPushNotifierTest|SalesReportTest"`
Expected: PASS, 15 tests.

- [ ] **Step 10: Commit**

```bash
git status --short   # pastikan storage/app/firebase/ TIDAK muncul
git add composer.json composer.lock config/firebase.php config/services.php .env.example phpunit.xml app/Providers/AppServiceProvider.php app/Services/Push/FcmKitchenPushNotifier.php tests/Feature/FcmKitchenPushNotifierTest.php
git commit -m "feat(kitchen): send kitchen order push via FCM topic"
```

---

### Task 3: Backend — command diagnosa + uji kirim sungguhan

**Files:**
- Create: `app/Console/Commands/KitchenPushTest.php`

**Interfaces:**
- Consumes: `config('services.fcm.*')`, `FcmKitchenPushNotifier::CHANNEL_ID`, `FcmKitchenPushNotifier::SOUND` (Task 2)
- Produces: `php artisan kitchen:push-test` — exit code 0 kalau FCM menerima pesan, 1 kalau gagal

- [ ] **Step 1: Buat command**

`app/Console/Commands/KitchenPushTest.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\Push\FcmKitchenPushNotifier;
use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Throwable;

class KitchenPushTest extends Command
{
    protected $signature = 'kitchen:push-test';

    protected $description = 'Kirim satu notifikasi uji ke topic Kitchen Display untuk memastikan konfigurasi FCM benar';

    public function handle(): int
    {
        $topic = config('services.fcm.kitchen_topic');

        $this->line('FCM_ENABLED       : ' . (config('services.fcm.enabled') ? 'true' : 'false'));
        $this->line('FCM_KITCHEN_TOPIC : ' . $topic);

        if (! config('services.fcm.enabled')) {
            $this->warn('FCM_ENABLED=false — order sungguhan TIDAK akan mengirim push. Pesan uji tetap dicoba.');
        }

        try {
            app(Messaging::class)->send(CloudMessage::fromArray([
                'topic' => $topic,
                'notification' => [
                    'title' => 'Tes Notifikasi Dapur',
                    'body' => 'Kalau tablet ini berbunyi, push Kitchen Display sudah jalan.',
                ],
                'data' => ['type' => 'kitchen_push_test'],
                'android' => [
                    'priority' => 'high',
                    'ttl' => '60s',
                    'notification' => [
                        'channel_id' => FcmKitchenPushNotifier::CHANNEL_ID,
                        'sound' => FcmKitchenPushNotifier::SOUND,
                    ],
                ],
            ]));
        } catch (Throwable $e) {
            $this->error('Gagal mengirim: ' . get_class($e) . ' — ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Terkirim ke topic '{$topic}'.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Uji kirim sungguhan ke topic dev**

Pastikan `.env` lokal berisi `FCM_KITCHEN_TOPIC=kitchen-orders-dev` (bukan topic production), lalu:

Run: `php artisan kitchen:push-test`
Expected: baris terakhir `Terkirim ke topic 'kitchen-orders-dev'.` dan exit code 0.

Kalau gagal: `InvalidArgument`/`file not found` → path `FIREBASE_CREDENTIALS` salah; `403`/`PERMISSION_DENIED` → Firebase Cloud Messaging API (V1) belum Enabled di console; `cURL error 28` → jaringan. Laporkan error apa adanya, jangan ditelan.

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/KitchenPushTest.php
git commit -m "feat(kitchen): add kitchen:push-test diagnostic command"
```

---

### Task 4: Flutter — logika murni: diff order dan coalescer sync

**Files:**
- Create: `lib/app/modules/kitchen_display/utils/kitchen_order_diff.dart`
- Create: `lib/app/services/sync_coalescer.dart`
- Test: `test/kitchen_order_diff_test.dart`, `test/sync_coalescer_test.dart`

**Interfaces:**
- Consumes: `KitchenOrder`, `KitchenOrderItem`, `KitchenOrderStatus` dari `lib/app/models/kitchen_order.dart`; `StationType` dari `lib/app/models/station_type.dart`
- Produces:
  - `KitchenOrderDiff.compute({required List<KitchenOrder> previous, required List<KitchenOrder> current}) → KitchenOrderDiffResult` dengan field `List<KitchenOrder> added`, `List<KitchenOrder> updated`
  - `SyncCoalescer(Future<void> Function() task)` dengan `Future<void> run()` dan getter `bool isRunning`

- [ ] **Step 1: Buat branch**

```bash
cd /d/KAFE_DILANGIT/pos_dilangit && git checkout -b feat/kitchen-fcm-push
```

(Perubahan uncommitted milik user ikut terbawa ke branch baru — biarkan, jangan di-stage.)

- [ ] **Step 2: Tulis test diff yang gagal**

`test/kitchen_order_diff_test.dart`:

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:pos_dilangit/app/models/kitchen_order.dart';
import 'package:pos_dilangit/app/models/station_type.dart';
import 'package:pos_dilangit/app/modules/kitchen_display/utils/kitchen_order_diff.dart';

KitchenOrder order(String id, int itemCount) {
  return KitchenOrder(
    id: id,
    orderNumber: 'ORD-$id',
    customerName: 'Walk-in Customer',
    orderTime: DateTime(2026, 9, 18, 12),
    items: List.generate(
      itemCount,
      (i) => KitchenOrderItem(productName: 'Menu $i', quantity: 1),
    ),
    status: KitchenOrderStatus.pending,
    createdByStation: StationType.kasir,
  );
}

void main() {
  test('order dengan ID baru masuk ke added', () {
    final diff = KitchenOrderDiff.compute(
      previous: [order('1', 2)],
      current: [order('2', 1), order('1', 2)],
    );

    expect(diff.added.map((o) => o.id), ['2']);
    expect(diff.updated, isEmpty);
  });

  test('order lama yang itemnya bertambah masuk ke updated', () {
    final diff = KitchenOrderDiff.compute(
      previous: [order('1', 2)],
      current: [order('1', 3)],
    );

    expect(diff.added, isEmpty);
    expect(diff.updated.map((o) => o.id), ['1']);
    expect(diff.updated.first.items.length, 3);
  });

  test('tidak ada perubahan menghasilkan diff kosong', () {
    final diff = KitchenOrderDiff.compute(
      previous: [order('1', 2), order('2', 1)],
      current: [order('2', 1), order('1', 2)],
    );

    expect(diff.added, isEmpty);
    expect(diff.updated, isEmpty);
    expect(diff.hasChanges, isFalse);
  });

  test('order yang hilang dari pending (sudah completed) bukan perubahan yang perlu alert', () {
    final diff = KitchenOrderDiff.compute(
      previous: [order('1', 2), order('2', 1)],
      current: [order('2', 1)],
    );

    expect(diff.hasChanges, isFalse);
  });

  test('list awal kosong: semua order masuk ke added', () {
    final diff = KitchenOrderDiff.compute(
      previous: [],
      current: [order('1', 1), order('2', 1)],
    );

    expect(diff.added.length, 2);
  });
}
```

- [ ] **Step 3: Jalankan — pastikan gagal**

Run: `flutter test test/kitchen_order_diff_test.dart`
Expected: FAIL — `Target of URI doesn't exist: '.../kitchen_order_diff.dart'`.

- [ ] **Step 4: Implementasi diff**

`lib/app/modules/kitchen_display/utils/kitchen_order_diff.dart`:

```dart
import '../../../models/kitchen_order.dart';

class KitchenOrderDiffResult {
  /// Order pending dengan ID yang belum pernah ada di layar.
  final List<KitchenOrder> added;

  /// Order pending yang sudah ada di layar tetapi itemnya bertambah.
  final List<KitchenOrder> updated;

  const KitchenOrderDiffResult({required this.added, required this.updated});

  bool get hasChanges => added.isNotEmpty || updated.isNotEmpty;
}

class KitchenOrderDiff {
  /// Bandingkan list pending di layar ([previous]) dengan data server ([current]).
  ///
  /// Backend selalu MENAMBAH baris item saat ada item susulan
  /// (KitchenOrder::addItems), jadi jumlah item yang bertambah adalah tanda
  /// pasti "ada item tambahan" — tidak perlu membandingkan timestamp.
  static KitchenOrderDiffResult compute({
    required List<KitchenOrder> previous,
    required List<KitchenOrder> current,
  }) {
    final previousById = {for (final o in previous) o.id: o};
    final added = <KitchenOrder>[];
    final updated = <KitchenOrder>[];

    for (final order in current) {
      final before = previousById[order.id];
      if (before == null) {
        added.add(order);
      } else if (order.items.length > before.items.length) {
        updated.add(order);
      }
    }

    return KitchenOrderDiffResult(added: added, updated: updated);
  }
}
```

- [ ] **Step 5: Jalankan — pastikan lolos**

Run: `flutter test test/kitchen_order_diff_test.dart`
Expected: PASS, 5 tests.

- [ ] **Step 6: Tulis test coalescer yang gagal**

`test/sync_coalescer_test.dart`:

```dart
import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:pos_dilangit/app/services/sync_coalescer.dart';

void main() {
  test('run() saat idle menjalankan task sekali', () async {
    var runs = 0;
    final coalescer = SyncCoalescer(() async => runs++);

    await coalescer.run();

    expect(runs, 1);
    expect(coalescer.isRunning, isFalse);
  });

  test('permintaan saat task berjalan digabung menjadi tepat satu run susulan', () async {
    var runs = 0;
    final gate = Completer<void>();
    final coalescer = SyncCoalescer(() async {
      runs++;
      if (runs == 1) await gate.future;
    });

    final first = coalescer.run();
    // Tiga sinyal push masuk selagi sync pertama masih jalan
    coalescer.run();
    coalescer.run();
    coalescer.run();
    expect(runs, 1);

    gate.complete();
    await first;

    expect(runs, 2, reason: 'Sinyal yang datang di tengah sync tidak boleh hilang, tapi juga tidak perlu 3x');
  });

  test('task yang melempar error tidak mengunci coalescer', () async {
    var runs = 0;
    final coalescer = SyncCoalescer(() async {
      runs++;
      if (runs == 1) throw Exception('network down');
    });

    await coalescer.run();
    await coalescer.run();

    expect(runs, 2);
    expect(coalescer.isRunning, isFalse);
  });
}
```

- [ ] **Step 7: Jalankan — pastikan gagal**

Run: `flutter test test/sync_coalescer_test.dart`
Expected: FAIL — `Target of URI doesn't exist: '.../sync_coalescer.dart'`.

- [ ] **Step 8: Implementasi coalescer**

`lib/app/services/sync_coalescer.dart`:

```dart
/// Menjamin hanya satu sync berjalan pada satu waktu, tanpa kehilangan sinyal:
/// kalau run() dipanggil saat sync masih berjalan, tepat SATU sync susulan
/// dijalankan setelahnya (order bisa saja masuk setelah query pertama dikirim).
class SyncCoalescer {
  SyncCoalescer(this._task);

  final Future<void> Function() _task;
  bool _running = false;
  bool _queued = false;

  bool get isRunning => _running;

  Future<void> run() async {
    if (_running) {
      _queued = true;
      return;
    }

    _running = true;
    try {
      do {
        _queued = false;
        try {
          await _task();
        } catch (e) {
          print('⚠️ Sync task error: $e');
        }
      } while (_queued);
    } finally {
      _running = false;
    }
  }
}
```

- [ ] **Step 9: Jalankan — pastikan lolos**

Run: `flutter test test/kitchen_order_diff_test.dart test/sync_coalescer_test.dart`
Expected: PASS, 8 tests.

(Jangan jalankan `flutter test` tanpa path: `test/widget_test.dart` bawaan template kemungkinan sudah gagal sebelum perubahan ini dan bukan bagian scope.)

- [ ] **Step 10: Commit**

```bash
git add lib/app/modules/kitchen_display/utils/kitchen_order_diff.dart lib/app/services/sync_coalescer.dart test/kitchen_order_diff_test.dart test/sync_coalescer_test.dart
git commit -m "feat(kitchen): add order diff and sync coalescer"
```

---

### Task 5: Flutter — setup Firebase di Android

**Files:**
- Modify: `pubspec.yaml`, `pubspec.lock` (via `flutter pub add`)
- Modify: `android/settings.gradle.kts`, `android/app/build.gradle.kts`
- Modify: `android/app/src/main/AndroidManifest.xml`
- Modify: `android/app/src/main/kotlin/dilangit/pos_dilangit/MainActivity.kt`
- Create: `android/app/src/main/res/raw/kitchen_alert.mp3` (salinan dari `assets/sounds/kitchen_alert.mp3`)
- Modify: `lib/main.dart`
- Sudah ada: `android/app/google-services.json`

**Interfaces:**
- Produces: variabel lokal `firebaseReady` (bool) di `main()` — dipakai Task 6 untuk konstruktor `PushService`. Notification channel Android `kitchen_orders_v1` dengan sound `kitchen_alert`.

- [ ] **Step 1: Tambah dependency**

```bash
flutter pub add firebase_core firebase_messaging
```

Expected: `pubspec.yaml` mendapat dua baris baru di `dependencies`. (Versi dipilih otomatis oleh pub supaya cocok dengan Flutter 3.44.)

- [ ] **Step 2: Plugin google-services**

`android/settings.gradle.kts` — di blok `plugins { ... }` tambahkan baris terakhir:

```kotlin
    id("com.google.gms.google-services") version "4.4.2" apply false
```

`android/app/build.gradle.kts` — di blok `plugins { ... }`, setelah `id("dev.flutter.flutter-gradle-plugin")`:

```kotlin
    id("com.google.gms.google-services")
```

- [ ] **Step 3: Manifest**

Di `android/app/src/main/AndroidManifest.xml`, setelah baris `BLUETOOTH_CONNECT`:

```xml

    <!-- Notifikasi order dapur (Android 13+) -->
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

Di dalam `<application>`, tepat sebelum komentar `<!-- Don't delete the meta-data below.`:

```xml
        <!-- Channel default untuk push FCM: harus sama dengan MainActivity.kt dan backend -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_channel_id"
            android:value="kitchen_orders_v1" />
```

- [ ] **Step 4: Sound resource**

```bash
mkdir -p android/app/src/main/res/raw && cp assets/sounds/kitchen_alert.mp3 android/app/src/main/res/raw/kitchen_alert.mp3
```

- [ ] **Step 5: Notification channel native**

Ganti seluruh isi `android/app/src/main/kotlin/dilangit/pos_dilangit/MainActivity.kt`:

```kotlin
package dilangit.pos_dilangit

import android.app.NotificationChannel
import android.app.NotificationManager
import android.media.AudioAttributes
import android.net.Uri
import android.os.Build
import android.os.Bundle
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        createKitchenOrdersChannel()
    }

    // Channel untuk push order dapur. Suara channel Android tidak bisa diubah
    // setelah dibuat — kalau suara perlu diganti, naikkan versi ID-nya (v2)
    // di sini, di AndroidManifest.xml, dan di backend (FcmKitchenPushNotifier).
    private fun createKitchenOrdersChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val sound = Uri.parse("android.resource://$packageName/${R.raw.kitchen_alert}")
        val audioAttributes = AudioAttributes.Builder()
            .setUsage(AudioAttributes.USAGE_NOTIFICATION)
            .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
            .build()

        val channel = NotificationChannel(
            "kitchen_orders_v1",
            "Order Dapur",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            description = "Notifikasi order baru dan item tambahan untuk dapur/bar"
            setSound(sound, audioAttributes)
            enableVibration(true)
        }

        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
    }
}
```

- [ ] **Step 6: Init Firebase di `main.dart`**

Tambahkan import:

```dart
import 'package:firebase_core/firebase_core.dart';
```

Ganti `void main() {` dan baris pertama body-nya menjadi:

```dart
Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Firebase hanya untuk push order dapur. Kalau gagal init (mis. build
  // non-Android), POS tetap harus jalan — Kitchen Display turun ke timer 60 detik.
  bool firebaseReady = false;
  try {
    await Firebase.initializeApp();
    firebaseReady = true;
  } catch (e) {
    print('⚠️ Firebase init gagal, push order dapur non-aktif: $e');
  }

  // Initialize global services
```

Sisa body `main()` tidak berubah di task ini. (`firebaseReady` sengaja belum dipakai — dipakai di Task 6; abaikan warning `unused_local_variable` sampai saat itu.)

- [ ] **Step 7: Verifikasi build**

Run: `flutter analyze lib/main.dart`
Expected: tidak ada **error** (warning `unused_local_variable` untuk `firebaseReady` boleh).

Run: `flutter build apk --debug`
Expected: `✓ Built build/app/outputs/flutter-apk/app-debug.apk`. Build pertama bisa 5–10 menit.

Kalau build gagal dengan `minSdkVersion ... cannot be smaller than version NN declared in library [firebase_messaging]`: di `android/app/build.gradle.kts` ganti `minSdk = flutter.minSdkVersion` menjadi `minSdk = NN` (angka dari pesan error), lalu build ulang.

- [ ] **Step 8: Commit**

```bash
git add pubspec.yaml android/settings.gradle.kts android/app/build.gradle.kts android/app/src/main/AndroidManifest.xml android/app/src/main/kotlin/dilangit/pos_dilangit/MainActivity.kt android/app/src/main/res/raw/kitchen_alert.mp3 android/app/google-services.json lib/main.dart
git add pubspec.lock   # catatan: file ini sudah punya perubahan user sebelumnya; ikut ter-commit karena lock harus konsisten dengan pubspec.yaml
git commit -m "feat(kitchen): set up Firebase Messaging and kitchen notification channel on Android"
```

---

### Task 6: Flutter — PushService, KitchenSyncService, dan rewiring Kitchen Display

**Files:**
- Create: `lib/app/services/kitchen_sync_service.dart`
- Create: `lib/app/services/push_service.dart`
- Delete: `lib/app/services/kitchen_polling_service.dart`
- Modify: `lib/app/services/notification_service.dart` (tambah `playOrderChime`)
- Modify: `lib/app/modules/home/controllers/home_controller.dart`, `lib/app/modules/auth/controllers/auth_controller.dart` (unsubscribe di `logout()`)
- Modify: `lib/app/services/api_service.dart` (hapus `createKitchenOrder`)
- Modify: `lib/app/modules/transaction/dialogs/add_item_dialog.dart` (hapus 2 method + 2 pemanggilnya)
- Modify: `lib/app/modules/kitchen_display/controllers/kitchen_display_controller.dart`
- Modify: `lib/app/modules/kitchen_display/views/kitchen_display_view.dart`
- Modify: `lib/main.dart`

**Interfaces:**
- Consumes: `KitchenOrderDiff.compute`, `SyncCoalescer` (Task 4); `firebaseReady` di `main()` (Task 5)
- Produces:
  - `KitchenSyncService`: `void start(Future<void> Function() onSync)`, `void stop()`, `Future<void> requestSync()`, `bool get isActive`
  - `PushService({required bool firebaseReady})`: `RxBool kitchenPushEnabled`, `Future<void> ensureKitchenPushOnDisplayOpen()`, `Future<void> setKitchenPush(bool enabled)`, `Future<void> onLogout()`
  - `NotificationService.playOrderChime()`

- [ ] **Step 1: `KitchenSyncService`**

`lib/app/services/kitchen_sync_service.dart`:

```dart
import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:get/get.dart';

import 'sync_coalescer.dart';

/// Menentukan KAPAN Kitchen Display mengambil ulang data dari server.
/// Push FCM hanyalah sinyal; data selalu diambil utuh lewat callback onSync.
///
/// Pemicu: push (lewat PushService), app kembali ke foreground, timer pengaman,
/// dan refresh manual. Timer pengaman menutup kasus push yang telat / hilang.
class KitchenSyncService extends GetxService with WidgetsBindingObserver {
  static const Duration safetyInterval = Duration(seconds: 60);

  SyncCoalescer? _coalescer;
  Timer? _safetyTimer;

  /// true selama layar Kitchen Display terbuka.
  bool get isActive => _coalescer != null;

  void start(Future<void> Function() onSync) {
    stop();
    _coalescer = SyncCoalescer(onSync);
    WidgetsBinding.instance.addObserver(this);
    _safetyTimer = Timer.periodic(safetyInterval, (_) => requestSync());
  }

  void stop() {
    _safetyTimer?.cancel();
    _safetyTimer = null;
    if (_coalescer != null) {
      WidgetsBinding.instance.removeObserver(this);
    }
    _coalescer = null;
  }

  Future<void> requestSync() async {
    await _coalescer?.run();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Push data-only bisa tertahan selama app di background — kejar saat kembali
    if (state == AppLifecycleState.resumed) {
      requestSync();
    }
  }

  @override
  void onClose() {
    stop();
    super.onClose();
  }
}
```

- [ ] **Step 2: `playOrderChime` di `NotificationService`**

Tambahkan method ini di `lib/app/services/notification_service.dart`, tepat sebelum komentar `// Stop the looping notification sound`:

```dart
  /// Bunyi sekali (tidak loop) — dipakai saat push masuk tetapi layar
  /// Kitchen Display tidak sedang terbuka, jadi tidak ada tombol accept
  /// yang akan menghentikan loop.
  Future<void> playOrderChime() async {
    if (!soundEnabled.value) return;
    try {
      await _audioPlayer.stop();
      await _audioPlayer.setReleaseMode(ReleaseMode.release);
      await _audioPlayer.play(AssetSource('sounds/kitchen_alert.mp3'));
    } catch (e) {
      print('🔊 Audio Error (chime): $e');
    }
  }

```

- [ ] **Step 3: `PushService`**

`lib/app/services/push_service.dart`:

```dart
import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'auth_service.dart';
import 'kitchen_sync_service.dart';
import 'notification_service.dart';

/// Push FCM untuk order dapur.
///
/// Device berlangganan topic begitu Kitchen Display dibuka pertama kali, dan
/// tetap berlangganan (termasuk saat app ditutup) sampai logout atau dimatikan
/// lewat toggle di AppBar Kitchen Display.
class PushService extends GetxService {
  PushService({required this.firebaseReady});

  /// false kalau Firebase gagal init — semua method menjadi no-op yang aman.
  final bool firebaseReady;

  /// Override saat testing lokal: --dart-define=KITCHEN_TOPIC=kitchen-orders-dev
  static const String kitchenTopic = String.fromEnvironment(
    'KITCHEN_TOPIC',
    defaultValue: 'kitchen-orders',
  );
  static const String kitchenRoute = '/kitchen-display';
  static const String mainRoute = '/main-navigation';
  static const String _enabledKey = 'kitchen_push_enabled';

  final RxBool kitchenPushEnabled = false.obs;

  StreamSubscription<RemoteMessage>? _foregroundSub;
  StreamSubscription<RemoteMessage>? _openedSub;

  @override
  void onInit() {
    super.onInit();
    _init();
  }

  Future<void> _init() async {
    final prefs = await SharedPreferences.getInstance();
    kitchenPushEnabled.value = prefs.getBool(_enabledKey) ?? false;

    if (!firebaseReady) return;

    _foregroundSub = FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    _openedSub = FirebaseMessaging.onMessageOpenedApp.listen(
      (_) => _openKitchenDisplay(),
    );

    // Subscribe ulang tiap start: idempotent, dan memulihkan langganan kalau
    // subscribe sebelumnya gagal karena offline.
    if (kitchenPushEnabled.value) {
      _subscribe();
    }

    // App dibuka dari keadaan mati lewat tap notifikasi
    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      _openKitchenDisplayWhenReady();
    }
  }

  /// Dipanggil saat Kitchen Display dibuka. Menyalakan push kecuali user
  /// pernah mematikannya secara eksplisit lewat toggle.
  Future<void> ensureKitchenPushOnDisplayOpen() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getBool(_enabledKey);
    if (saved == false) return; // dimatikan eksplisit lewat toggle
    if (saved == true) {
      // Sudah aktif: cukup pastikan langganan, tanpa meminta izin lagi
      if (firebaseReady) await _subscribe();
      return;
    }
    await setKitchenPush(true); // pertama kali: minta izin + subscribe
  }

  Future<void> setKitchenPush(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_enabledKey, enabled);
    kitchenPushEnabled.value = enabled;

    if (!firebaseReady) return;

    if (enabled) {
      await _requestPermission();
      await _subscribe();
    } else {
      await _unsubscribe();
    }
  }

  /// Logout eksplisit: berhenti berlangganan dan lupakan preferensi, supaya
  /// login berikutnya + buka Kitchen Display menyalakannya lagi.
  /// (Session expired TIDAK memanggil ini — tablet tetap berbunyi supaya staf
  /// tahu harus login ulang.)
  Future<void> onLogout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_enabledKey);
    kitchenPushEnabled.value = false;
    if (firebaseReady) await _unsubscribe();
  }

  Future<void> _requestPermission() async {
    try {
      final settings = await FirebaseMessaging.instance.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) {
        Get.snackbar(
          'Notifikasi Diblokir',
          'Tablet tidak akan berbunyi saat aplikasi ditutup. Izinkan notifikasi di Pengaturan Android.',
          snackPosition: SnackPosition.TOP,
          backgroundColor: Colors.orange,
          colorText: Colors.white,
          duration: const Duration(seconds: 5),
          icon: const Icon(Icons.notifications_off, color: Colors.white),
        );
      }
    } catch (e) {
      print('⚠️ Gagal meminta izin notifikasi: $e');
    }
  }

  Future<void> _subscribe() async {
    try {
      await FirebaseMessaging.instance
          .subscribeToTopic(kitchenTopic)
          .timeout(const Duration(seconds: 10));
      print('✅ Subscribed to FCM topic: $kitchenTopic');
    } catch (e) {
      // Offline saat subscribe: dicoba lagi otomatis pada start berikutnya
      print('⚠️ Gagal subscribe topic $kitchenTopic: $e');
    }
  }

  Future<void> _unsubscribe() async {
    try {
      await FirebaseMessaging.instance
          .unsubscribeFromTopic(kitchenTopic)
          .timeout(const Duration(seconds: 10));
    } catch (e) {
      print('⚠️ Gagal unsubscribe topic $kitchenTopic: $e');
    }
  }

  void _handleForegroundMessage(RemoteMessage message) {
    final type = message.data['type']?.toString() ?? '';
    if (!type.startsWith('kitchen_')) return;

    // Layar Kitchen Display terbuka: sync, controller yang mengurus bunyi + badge
    if (Get.isRegistered<KitchenSyncService>()) {
      final sync = Get.find<KitchenSyncService>();
      if (sync.isActive) {
        sync.requestSync();
        return;
      }
    }

    // App terbuka di layar lain. message.notification == null berarti push
    // senyap (status berubah / push susulan yang diredam server) → diam.
    final notification = message.notification;
    if (notification == null) return;

    if (Get.isRegistered<NotificationService>()) {
      Get.find<NotificationService>().playOrderChime();
    }

    Get.snackbar(
      notification.title ?? '🔔 Order Baru!',
      notification.body ?? '',
      snackPosition: SnackPosition.TOP,
      backgroundColor: const Color(0xFFFF9800),
      colorText: Colors.white,
      duration: const Duration(seconds: 6),
      icon: const Icon(Icons.notifications_active, color: Colors.white),
      mainButton: TextButton(
        onPressed: _openKitchenDisplay,
        child: const Text('BUKA', style: TextStyle(color: Colors.white)),
      ),
    );
  }

  Future<void> _openKitchenDisplay() async {
    if (!await AuthService.isLoggedIn()) return;

    if (Get.currentRoute == kitchenRoute) {
      if (Get.isRegistered<KitchenSyncService>()) {
        Get.find<KitchenSyncService>().requestSync();
      }
      return;
    }

    Get.toNamed(kitchenRoute);
  }

  /// Saat cold start, route awal masih splash/login. Tunggu sampai app
  /// mendarat di halaman utama (maks 10 detik) baru buka Kitchen Display.
  Future<void> _openKitchenDisplayWhenReady() async {
    for (var i = 0; i < 40; i++) {
      await Future.delayed(const Duration(milliseconds: 250));
      if (Get.currentRoute == mainRoute) {
        await _openKitchenDisplay();
        return;
      }
    }
  }

  @override
  void onClose() {
    _foregroundSub?.cancel();
    _openedSub?.cancel();
    super.onClose();
  }
}
```

- [ ] **Step 4: Daftarkan service di `main.dart`**

Ganti import `import 'app/services/kitchen_polling_service.dart';` menjadi:

```dart
import 'app/services/kitchen_sync_service.dart';
import 'app/services/push_service.dart';
```

Ganti baris `Get.put<KitchenPollingService>(KitchenPollingService(), permanent: true);` menjadi:

```dart
  Get.put<KitchenSyncService>(KitchenSyncService(), permanent: true);
  Get.put<PushService>(
    PushService(firebaseReady: firebaseReady),
    permanent: true,
  );
```

- [ ] **Step 5: Rewiring `KitchenDisplayController`**

Di `lib/app/modules/kitchen_display/controllers/kitchen_display_controller.dart`:

a. Ganti import `'../../../services/kitchen_polling_service.dart'` menjadi:

```dart
import '../../../services/kitchen_sync_service.dart';
import '../../../services/push_service.dart';
import '../utils/kitchen_order_diff.dart';
```

b. Ganti field `_pollingService` (2 baris) menjadi:

```dart
  final KitchenSyncService _syncService = Get.find<KitchenSyncService>();

  // Alert hanya untuk perubahan SETELAH load pertama (load awal tidak berbunyi)
  bool _initialLoadDone = false;
```

c. Ganti body `onInit()` menjadi:

```dart
  @override
  void onInit() {
    super.onInit();
    _autoConnectKitchenPrinter(); // Auto-connect to Kitchen printer
    fetchAllOrders(); // Initial load
    _syncService.start(_syncFromServer); // push / resume / timer 60 detik
    if (Get.isRegistered<PushService>()) {
      Get.find<PushService>().ensureKitchenPushOnDisplayOpen();
    }
  }
```

d. **Hapus** method `_startPolling()`, `_listenToNewOrders()`, dan `_handleNewOrders(...)` seluruhnya.

e. Ganti seluruh method `fetchAllOrders()` dengan:

```dart
  /// Refresh manual / load awal: menampilkan loading dan error.
  Future<void> fetchAllOrders() => _loadOrders(silent: false);

  /// Dipanggil KitchenSyncService (push, app resume, timer pengaman).
  Future<void> _syncFromServer() => _loadOrders(silent: true);

  /// Ambil state PENUH dari server lalu bandingkan dengan yang ada di layar.
  /// Tidak memakai since_timestamp / jam device, jadi tidak ada order yang bisa
  /// terlewat permanen: apa pun yang pending di server pasti tampil.
  Future<void> _loadOrders({required bool silent}) async {
    if (!silent) isLoading.value = true;
    try {
      final token = await AuthService.getToken();
      if (token == null) {
        if (!silent) {
          Get.snackbar('Error', 'Token tidak ditemukan. Silakan login kembali.');
        }
        return;
      }

      final today = DateTime.now();
      final todayStart = DateTime(today.year, today.month, today.day);
      final todayEnd = todayStart.add(const Duration(days: 1));
      bool isToday(KitchenOrder order) =>
          !order.orderTime.isBefore(todayStart) &&
          order.orderTime.isBefore(todayEnd);

      final pendingResponse = await _apiService.getKitchenOrders(
        token: token,
        status: 'pending',
        limit: 100,
      );

      final completedResponse = await _apiService.getKitchenOrders(
        token: token,
        status: 'completed',
        sinceTimestamp: todayStart.toIso8601String(),
        limit: 50,
      );

      if (pendingResponse['success'] == true) {
        final freshPending = _parseOrdersFromResponse(
          pendingResponse['data'],
        ).where(isToday).toList();

        final diff = KitchenOrderDiff.compute(
          previous: pendingOrders,
          current: freshPending,
        );

        pendingOrders.value = freshPending;

        if (_initialLoadDone) {
          _alertChanges(diff);
        }
        _initialLoadDone = true;
      }

      if (completedResponse['success'] == true) {
        completedOrders.value = _parseOrdersFromResponse(
          completedResponse['data'],
        ).where(isToday).toList();
      }
    } catch (e) {
      if (!silent) {
        Get.snackbar(
          'Error',
          'Gagal load orders: $e',
          snackPosition: SnackPosition.TOP,
          backgroundColor: Colors.red,
          colorText: Colors.white,
        );
      } else {
        print('⚠️ Kitchen sync gagal (dicoba lagi otomatis): $e');
      }
    } finally {
      if (!silent) isLoading.value = false;
    }
  }

  void _alertChanges(KitchenOrderDiffResult diff) {
    if (!diff.hasChanges) return;

    newOrderBadge.value += diff.added.length;

    // Bunyi loop sampai order di-accept (perilaku yang sudah ada)
    notificationService.playOrderNotification();

    final String title;
    final String message;
    if (diff.added.isNotEmpty) {
      title = '🔔 Order Baru!';
      message =
          '${diff.added.length} order baru dari ${diff.added.first.createdByStation.displayName}';
    } else {
      title = '➕ Item Tambahan';
      message =
          'Order #${diff.updated.first.orderNumber} mendapat item tambahan';
    }

    Get.snackbar(
      title,
      message,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: const Color(0xFFFF9800),
      colorText: Colors.white,
      duration: const Duration(seconds: 2),
      icon: const Icon(Icons.notifications_active, color: Colors.white),
    );
  }
```

f. Hapus **kedua** blok `// Mark activity untuk fast polling` beserta `if (Get.isRegistered<KitchenPollingService>()) { ... }`-nya (di `acceptOrder` dan di method complete order).

g. Di `onClose()`, ganti `_pollingService.stopPolling();` menjadi `_syncService.stop();`.

h. Tambahkan method toggle, tepat sebelum `@override void onClose()`:

```dart
  // Toggle push order untuk device ini
  Future<void> toggleKitchenPush() async {
    if (!Get.isRegistered<PushService>()) return;
    final push = Get.find<PushService>();
    final enable = !push.kitchenPushEnabled.value;
    await push.setKitchenPush(enable);
    Get.snackbar(
      'Notifikasi Order ${enable ? 'Aktif' : 'Non-aktif'}',
      enable
          ? 'Device ini berbunyi saat ada order, termasuk ketika aplikasi ditutup'
          : 'Device ini tidak lagi menerima notifikasi order',
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: enable ? Colors.green : Colors.grey,
      colorText: Colors.white,
      duration: const Duration(seconds: 2),
    );
  }

```

- [ ] **Step 6: Toggle di AppBar**

Di `lib/app/modules/kitchen_display/views/kitchen_display_view.dart` tambahkan import:

```dart
import '../../../services/push_service.dart';
```

Di `actions: [`, tepat sebelum komentar `// Badge with new order count`, sisipkan:

```dart
          // Push notification toggle (device ini)
          Obx(() {
            final enabled = Get.isRegistered<PushService>() &&
                Get.find<PushService>().kitchenPushEnabled.value;
            return IconButton(
              icon: Icon(
                enabled
                    ? Icons.notifications_active
                    : Icons.notifications_off,
              ),
              onPressed: controller.toggleKitchenPush,
              tooltip: enabled
                  ? 'Notifikasi order: ON'
                  : 'Notifikasi order: OFF',
            );
          }),

```

- [ ] **Step 7: Unsubscribe saat logout eksplisit**

**Jangan** menaruh unsubscribe di `AuthService.logout()`: method itu juga dipanggil oleh handler session-expired (401) di `inventory_controller`, `menu_management_controller`, `checkout_controller`, dan `report_controller`. Sesuai spec, session expired tidak boleh unsubscribe (tablet tetap berbunyi supaya staf tahu harus login ulang). Unsubscribe hanya di dua jalur logout yang ditekan user.

Di `lib/app/modules/home/controllers/home_controller.dart` tambahkan import (sesuaikan kedalaman path dengan import `auth_service.dart` yang sudah ada di file itu):

```dart
import '../../../services/push_service.dart';
```

Di method `logout()`, tepat sebelum `await AuthService.logout();`:

```dart
      // Device yang logout tidak boleh terus menerima push order dapur
      if (Get.isRegistered<PushService>()) {
        await Get.find<PushService>().onLogout();
      }
```

Di `lib/app/modules/auth/controllers/auth_controller.dart` tambahkan import yang sama, lalu di method `logout()`, tepat sebelum `await _clearUserData();`, sisipkan blok `if (Get.isRegistered<PushService>()) { ... }` yang sama persis.

- [ ] **Step 8: Hapus panggilan `createKitchenOrder` yang redundan**

Latar: `ApiService.addOrderItem` → `PosController` di backend **sudah** membuat/merge kitchen order. Panggilan `createKitchenOrder` sesudahnya selalu ditolak 422 (mengirim `order_number`, backend minta `order_id`) — dan kalau suatu saat "diperbaiki" akan menggandakan item di dapur.

Di `lib/app/modules/transaction/dialogs/add_item_dialog.dart`:
- Hapus blok `// Create kitchen orders for kitchen items` di `_processAllCartItems` (dari `try {` sampai `catch (e) { print('❌ Failed to create kitchen order for cart items: $e'); }`), ganti dengan satu komentar:
  `// Kitchen order dibuat otomatis oleh backend di addOrderItem (PosController) — jangan dibuat lagi dari sini.`
- Hapus seluruh method `_createKitchenOrderForCartItems`.
- Cari pemanggilan `_createKitchenOrderForAddedItem(` (sekitar baris 2861): hapus blok try/catch pembungkusnya dengan cara yang sama, beri komentar yang sama.
- Hapus seluruh method `_createKitchenOrderForAddedItem`.

Di `lib/app/services/api_service.dart`: hapus method `createKitchenOrder` beserta komentar `// POST request untuk create kitchen order baru`.

Verifikasi tidak ada sisa:

Run: `grep -rn "createKitchenOrder\|_createKitchenOrderFor\|KitchenPollingService\|kitchen_polling_service\|markActivity" lib/`
Expected: tidak ada output.

- [ ] **Step 9: Hapus service lama**

```bash
git rm lib/app/services/kitchen_polling_service.dart
```

- [ ] **Step 10: Verifikasi**

Run: `flutter analyze lib/`
Expected: tidak ada baris `error •` di output. (Warning/info lama milik codebase bukan scope — cek dengan `flutter analyze lib/ | grep "error •"`, harus kosong.)

Run: `flutter test test/kitchen_order_diff_test.dart test/sync_coalescer_test.dart`
Expected: PASS, 8 tests.

Run: `flutter build apk --debug`
Expected: `✓ Built build/app/outputs/flutter-apk/app-debug.apk`

- [ ] **Step 11: Commit**

```bash
git add lib/main.dart lib/app/services/kitchen_sync_service.dart lib/app/services/push_service.dart lib/app/services/notification_service.dart lib/app/modules/home/controllers/home_controller.dart lib/app/modules/auth/controllers/auth_controller.dart lib/app/services/api_service.dart lib/app/modules/transaction/dialogs/add_item_dialog.dart lib/app/modules/kitchen_display/controllers/kitchen_display_controller.dart lib/app/modules/kitchen_display/views/kitchen_display_view.dart
git commit -m "feat(kitchen): replace polling with FCM-triggered full sync"
```

---

### Task 7: Dokumentasi dan checklist rilis

**Files:**
- Create: `phpunit/docs/KITCHEN_PUSH_FCM.md`
- Modify: `phpunit/CLAUDE.md` (bagian "Kitchen Order System")
- Modify: `pos_dilangit/CLAUDE.md` dan `D:\KAFE_DILANGIT\CLAUDE.md` (baris `KitchenPollingService`)

**Interfaces:**
- Consumes: semua nama dari Task 1–6.

- [ ] **Step 1: Tulis `phpunit/docs/KITCHEN_PUSH_FCM.md`**

```markdown
# Kitchen Display — Push FCM

Order baru / item tambahan dikirim ke tablet dapur lewat FCM topic. Push hanyalah **sinyal**;
Kitchen Display selalu mengambil ulang seluruh order pending dari `GET /kitchen/orders`, dan tetap
sync tiap 60 detik sebagai jaring pengaman. Desain lengkap:
`docs/superpowers/specs/2026-09-18-kitchen-fcm-push-design.md`.

## Deploy backend (sekali)

1. PHP server ≥ 8.2, lalu `composer install --no-dev`.
2. Upload `firebase-credentials.json` ke `storage/app/firebase/` (manual — file ini tidak ada di git).
3. `.env` production:
   ```
   FIREBASE_CREDENTIALS=storage/app/firebase/firebase-credentials.json
   FIREBASE_HTTP_CLIENT_TIMEOUT=5
   FCM_ENABLED=true
   FCM_KITCHEN_TOPIC=kitchen-orders
   ```
4. `php artisan optimize:clear`
5. `php artisan kitchen:push-test` → harus `Terkirim ke topic 'kitchen-orders'.`
   Tablet yang sudah subscribe akan berbunyi.

Backend aman di-deploy lebih dulu: APK lama tetap polling seperti biasa.

## Setup tiap tablet dapur/bar (sekali per device)

1. Install APK baru, login, buka **Kitchen Display** → izinkan notifikasi saat diminta.
2. Android Settings → Apps → pos_dilangit → Battery → **Unrestricted** (matikan battery optimization).
   Tanpa ini Android bisa menahan push saat layar mati.
3. Pastikan ikon lonceng di AppBar Kitchen Display menyala (Notifikasi order: ON).

## Uji manual (wajib sebelum dipakai operasional)

| # | Kondisi tablet | Aksi di kasir | Harapan |
|---|---|---|---|
| 1 | Kitchen Display terbuka | Buat order berisi item dapur | Kartu muncul ≤ 2 detik, bunyi loop sampai di-accept |
| 2 | Kitchen Display terbuka | Tambah item ke order yang masih pending | Kartu yang sama bertambah itemnya + snackbar "Item Tambahan" |
| 3 | App di background | Buat order | Notifikasi sistem + bunyi; tap → Kitchen Display |
| 4 | App ditutup (swipe) + layar mati | Buat order | Notifikasi sistem + bunyi |
| 5 | Mode pesawat 2 menit, lalu online | Buat order saat offline | Order muncul saat online kembali (≤ 60 detik) |
| 6 | Dua tablet | Accept order di tablet A | Order pindah ke "sudah di print" di tablet B tanpa refresh |
| 7 | Cart 5 item dapur sekaligus | Proses cart | Tablet berbunyi sekali, bukan 5 kali; kelima item tampil |

## Troubleshooting

- Tidak ada push sama sekali → `php artisan kitchen:push-test`; cek `FCM_ENABLED`, lalu
  `storage/logs/laravel.log` untuk `Kitchen push failed`.
- Push masuk saat app terbuka tapi tidak saat ditutup → battery optimization / izin notifikasi.
- Notifikasi muncul tanpa suara order → channel `kitchen_orders_v1` dibuat saat app pertama dibuka;
  buka app sekali. Mengganti suara butuh ID channel baru (`_v2`) di `MainActivity.kt`,
  `AndroidManifest.xml`, dan `FcmKitchenPushNotifier::CHANNEL_ID`.
- Rotate kredensial: generate key baru di Firebase Console → timpa file → hapus key lama di
  Google Cloud IAM. Tidak ada perubahan kode.
```

- [ ] **Step 2: Update `phpunit/CLAUDE.md`**

Di akhir bagian `### Kitchen Order System`, tambahkan paragraf:

```markdown
**Push (FCM):** setiap kitchen order dibuat / ditambah item / berubah status, `KitchenPushDispatcher` mengirim event ke `KitchenPushNotifier` setelah DB commit dan setelah response (`DB::afterCommit` + `defer`, tanpa queue worker). Trigger ada di `KitchenOrder::createFromOrderItems()`, `KitchenOrder::addItems()`, dan `KitchenController::updateKitchenOrderStatusNewTable()` — jangan kirim push dari controller lain. `FCM_ENABLED=false` memakai `NullKitchenPushNotifier`. Push hanyalah sinyal; Flutter selalu re-fetch `GET /kitchen/orders`. Lihat `docs/KITCHEN_PUSH_FCM.md`.
```

- [ ] **Step 3: Update deskripsi service di kedua CLAUDE.md Flutter**

Di `pos_dilangit/CLAUDE.md` dan `D:\KAFE_DILANGIT\CLAUDE.md`, ganti baris bullet `KitchenPollingService — Polls GET /kitchen/orders with adaptive intervals ...` menjadi:

```markdown
- `KitchenSyncService` — Memicu re-fetch penuh `GET /kitchen/orders` saat: push FCM masuk, app resume, timer pengaman 60 detik, refresh manual. Tidak ada `since_timestamp`; diff dilakukan di `KitchenOrderDiff`.
- `PushService` — FCM topic `kitchen-orders` (override: `--dart-define=KITCHEN_TOPIC=...`). Subscribe saat Kitchen Display pertama dibuka, unsubscribe saat logout. Channel Android `kitchen_orders_v1` dibuat di `MainActivity.kt`.
```

Di bagian "Key data flows" file yang sama, ganti `KitchenPollingService starts on view open.` menjadi `KitchenSyncService starts on view open; FCM push triggers an immediate sync.`

- [ ] **Step 4: Commit (dua repo)**

```bash
cd /d/KAFE_DILANGIT/phpunit && git add docs/KITCHEN_PUSH_FCM.md CLAUDE.md docs/superpowers/specs/2026-09-18-kitchen-fcm-push-design.md docs/superpowers/plans/2026-09-18-kitchen-fcm-push.md && git commit -m "docs(kitchen): document FCM push deploy, tablet setup, and manual test matrix"
cd /d/KAFE_DILANGIT/pos_dilangit && git add CLAUDE.md && git commit -m "docs: describe KitchenSyncService and PushService"
```

(`D:\KAFE_DILANGIT\CLAUDE.md` berada di luar kedua repo — cukup disimpan, tidak ada commit. `pos_dilangit/CLAUDE.md` saat ini untracked; commit ini sekaligus mulai melacaknya.)

---

## Yang tidak bisa diverifikasi oleh plan ini

Matriks uji manual di `docs/KITCHEN_PUSH_FCM.md` (tablet fisik: background, app ditutup, layar mati, dua tablet) hanya bisa dijalankan oleh tim di device asli. Sampai itu dijalankan, yang terbukti hanyalah: trigger backend (PHPUnit), bentuk payload FCM (PHPUnit), FCM menerima pesan (`kitchen:push-test`), logika diff/coalescer (Dart test), dan APK ter-build.
