# Kitchen Display: Push FCM + Sync yang Benar

Tanggal: 2026-09-18
Status: disetujui (2026-09-18), direvisi setelah pembacaan kode
Scope: `phpunit/` (Laravel API) dan `pos_dilangit/` (Flutter, Android saja)

## Masalah

Kitchen Display saat ini tahu ada order baru lewat polling `GET /kitchen/orders` tiap 5–30 detik
(`KitchenPollingService`). Tidak ada push. Order bisa tidak muncul di layar dapur karena:

1. **Item tambahan ke order pending tidak pernah muncul.** `KitchenOrder::addItems()` hanya
   `touch()` `updated_at`, sedangkan polling memfilter `created_at > since_timestamp`, dan
   `_handleNewOrders` membuang ID yang sudah ada di list.
2. **`since_timestamp` memakai jam device**, dikirim tanpa offset timezone. Order yang dibuat di
   sela waktu query server dan `DateTime.now()` di device terlewat permanen.
3. **Interval adaptif rusak**: setelah order pertama masuk, polling terkunci di 5 detik selamanya.
4. **Tablet diam kalau app di background / layar mati** — polling hanya hidup selama layar
   Kitchen Display terbuka.
5. **Panggilan redundan yang selalu 422.** Add-item dialog memanggil `POST /kitchen/orders` dengan
   `order_number` + `created_by_station`, sedangkan backend mewajibkan `order_id` + `station`, jadi
   selalu ditolak. Ini *tidak* menyebabkan miss: `POST addOrderItem` (`PosController`) sudah membuat /
   me-merge kitchen order di server. Justru kalau validasinya dilonggarkan, setiap item akan muncul
   **dua kali** di dapur. Perbaikannya: hapus panggilan redundan di Flutter; backend tidak diubah.

## Tujuan

- Order baru / item tambahan sampai ke tablet dapur dalam ~1–2 detik, termasuk saat app di
  background atau ditutup (tablet tetap bunyi).
- Tidak ada order yang hilang permanen walau sebuah push gagal terkirim.

## Prinsip desain

FCM bersifat best-effort (bisa telat karena Doze, di-throttle, atau hilang saat device offline
melewati TTL). Karena itu **FCM hanya sinyal "ada perubahan"**; kebenaran data selalu datang dari
`sync()` yang mengambil ulang state penuh dari server. Timer 60 detik menjadi jaring pengaman.

```
Kasir/Bar ──POST──▶ Laravel ──(after commit, after response)──▶ FCM topic "kitchen-orders"
                                                                      │
Tablet dapur ◀── notifikasi sistem + bunyi (background/killed) ───────┤
Tablet dapur ◀── onMessage (foreground) ──▶ sync() ──GET /kitchen/orders──▶ diff by id + jumlah item
                         ▲
   app resume · timer 60s · tap notifikasi · refresh manual
```

## Keputusan

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Target pengiriman | FCM **topic** `kitchen-orders` | Semua Kitchen Display melihat order yang sama. Tanpa tabel token, tanpa endpoint register, tanpa urusan token kedaluwarsa. |
| Library server | **Tanpa library** — `FcmClient` sendiri (FCM HTTP v1 via `Http` + `openssl`) | Rencana awal `kreait/laravel-firebase` batal saat implementasi: butuh `lcobucci/jwt` ≥ 4.3, sedangkan project terkunci di 4.0.4 oleh `tymon/jwt-auth`. Meng-upgrade paket JWT yang menempel ke auth demi notifikasi tidak sepadan risikonya. Bonus: deploy tanpa `composer install`. |
| Waktu kirim | `DB::afterCommit()` + `defer()` | Tidak mengirim untuk transaksi yang rollback; tidak memperlambat checkout; **tidak butuh queue worker** (production diduga shared hosting). |
| Kegagalan FCM | Di-log, tidak pernah menggagalkan order | Push adalah side effect. |
| Platform | Android saja | Sesuai kondisi lapangan. Build Windows/web tidak disentuh. |

Ditolak: token per device (kode ~2x, belum ada kebutuhan targeting), WebSocket/Reverb (mati saat
app background).

## Backend (`phpunit/`)

### Konfigurasi
- Tidak ada dependency composer baru.
- `.env` / `.env.example`:
  - `FIREBASE_CREDENTIALS=storage/app/firebase/firebase-credentials.json` (sudah ada di disk,
    ter-ignore oleh `storage/app/.gitignore`)
  - `FCM_ENABLED=false` (default; `true` di production)
  - `FCM_KITCHEN_TOPIC=kitchen-orders` (pakai `kitchen-orders-dev` di lokal supaya testing tidak
    membunyikan tablet production)
- HTTP timeout ke FCM: 5 detik (`FCM_TIMEOUT`).

### Komponen
- `App\Contracts\KitchenPushNotifier` — interface:
  `orderCreated(KitchenOrder)`, `itemsAdded(KitchenOrder, int $newItemsCount)`,
  `statusChanged(KitchenOrder)`.
- `App\Services\Push\FcmClient` — JWT RS256 → access token OAuth (di-cache 55 menit) → `messages:send`.
- `App\Services\Push\FcmKitchenPushNotifier` — bentuk payload + peredam bunyi, memakai `FcmClient`. Melempar exception kalau gagal;
  yang menangkap dan me-`Log::warning` adalah `KitchenPushDispatcher` (satu tempat saja).
- `App\Services\Push\KitchenPushDispatcher` — `afterCommit` + `defer` + try/catch.
- `App\Services\Push\NullKitchenPushNotifier` — no-op, dipakai saat `FCM_ENABLED=false`.
- Binding di `AppServiceProvider` berdasarkan `FCM_ENABLED`.

### Titik trigger
Keempat jalur pembuatan kitchen order (`PosController` ×3, `KitchenController` ×1) berakhir di dua
method model, jadi trigger dipasang di sana — bukan di controller:

| Lokasi | Event |
|---|---|
| `KitchenOrder::createFromOrderItems()` (setelah item dibuat) | `orderCreated` |
| `KitchenOrder::addItems()` | `itemsAdded` |
| `KitchenController::updateKitchenOrderStatusNewTable()` | `statusChanged` |

Pemanggilan dibungkus helper: `DB::afterCommit(fn () => defer(fn () => $notifier->...))`.

### Payload

| Event | `notification` | `data.type` | Android |
|---|---|---|---|
| Order baru | `Order Baru #{order_number}` / `Meja {n} · {x} item · dari {station}` | `kitchen_order_created` | priority `high`, channel `kitchen_orders_v1`, sound `kitchen_alert`, TTL 3600s |
| Item tambahan | `Tambahan Order #{order_number}` / `+{x} item · Meja {n}` | `kitchen_items_added` | sama |
| Status berubah | — (data-only, senyap) | `kitchen_status_changed` | priority `normal`, TTL 300s |

`data` selalu berisi `kitchen_order_id` dan `order_number`. Tidak ada data sensitif di payload;
detail order tetap harus diambil lewat API ber-token Sanctum.

### Peredam bunyi beruntun
Cart berisi N item = N kali `addOrderItem` = N push dalam beberapa detik. Push bernotifikasi
(berbunyi) untuk kitchen order yang sama dibatasi satu per 5 detik (`Cache::add`); push susulan di
dalam jendela itu diturunkan menjadi data-only senyap — sinyal sync tetap sampai, tablet tidak
berbunyi N kali. Notifikasi memakai `tag` `kitchen-order-{id}` supaya saling menggantikan di tray.

### Diagnosa
Command `php artisan kitchen:push-test` mengirim satu pesan uji ke topic dan mencetak hasil /
error-nya — untuk memastikan kredensial dan `FCM_ENABLED` benar di production.

### Perubahan API
Tidak ada. Kontrak `GET /kitchen/orders` dan `POST /kitchen/orders` tidak berubah, sehingga APK lama
tetap jalan.

## Flutter (`pos_dilangit/`)

### Setup
- Dependency: `firebase_core`, `firebase_messaging` (tanpa `flutter_local_notifications`).
- Gradle: plugin `com.google.gms.google-services` di `settings.gradle.kts` dan
  `app/build.gradle.kts`. `google-services.json` sudah ada di `android/app/`.
- Manifest: izin `POST_NOTIFICATIONS`; meta-data default channel `kitchen_orders_v1`.
- `MainActivity.kt` membuat notification channel `kitchen_orders_v1` secara native (importance
  high, suara `kitchen_alert`). ID diberi versi karena suara channel Android tidak bisa diubah
  setelah dibuat.
- Salin `assets/sounds/kitchen_alert.mp3` → `android/app/src/main/res/raw/kitchen_alert.mp3`.
- `main()` menjadi `async`: `WidgetsFlutterBinding.ensureInitialized()` + `Firebase.initializeApp()`
  sebelum `runApp`. Inisialisasi Firebase dijaga try/catch — kegagalan Firebase tidak boleh
  menghalangi POS kasir jalan.

### Komponen
- `PushService` (GetxService)
  - `enableKitchenPush()`: minta izin notifikasi (Android 13+), subscribe topic, simpan flag
    `kitchen_push_enabled` di SharedPreferences. `disableKitchenPush()`: kebalikannya.
  - `onMessage` (foreground): jika layar Kitchen Display terbuka → `KitchenSyncService.sync()`
    (bunyi in-app yang sudah ada tetap dipakai). Jika tidak terbuka → bunyi sekali + snackbar
    in-app dengan tombol "Buka" ke Kitchen Display.
  - Tap notifikasi (`onMessageOpenedApp` / `getInitialMessage`) → navigasi ke Kitchen Display.
- `KitchenSyncService` — **menggantikan** `KitchenPollingService` (file lama dihapus)
  - `sync()`: ambil semua pending + completed hari ini (logika `fetchAllOrders` sekarang),
    dengan guard anti-concurrent; jika `sync()` dipanggil saat masih berjalan, satu sync susulan
    dijadwalkan supaya sinyal tidak hilang.
  - Pemicu: pesan FCM, app resume (`WidgetsBindingObserver`), timer 60 detik, refresh manual.
  - Tidak ada lagi `since_timestamp`, `lastPolledAt`, atau interval adaptif.
- `KitchenOrderDiff` — fungsi murni, mudah di-unit-test:
  input list pending lama + baru → `added` (ID baru) dan `updated` (ID sama dan jumlah item
  bertambah — `addItems()` selalu menambah baris item, jadi `updated_at` tidak diperlukan).
- `KitchenDisplayController`
  - Hasil `sync()` → list diganti dengan data server; `added` → bunyi + badge + snackbar
    "Order Baru"; `updated` → bunyi + snackbar "Item tambahan #X". Load awal tidak berbunyi
    (perilaku sekarang).
  - `onInit` memanggil `PushService.enableKitchenPush()`.
  - Pemanggilan `markActivity()` dihapus.
- Add-item dialog: hapus `_createKitchenOrderForCartItems` / `_createKitchenOrderForAddedItem`
  beserta `ApiService.createKitchenOrder` (redundan, lihat Masalah #5).
- Logout eksplisit (`HomeController.logout()`, `AuthController.logout()`): unsubscribe topic.
  Bukan di `AuthService.logout()`, karena method itu juga dipakai handler session-expired. Session expired (401) **tidak**
  unsubscribe — tablet tetap berbunyi supaya staf tahu harus login ulang.
- Topic bisa di-override saat build: `--dart-define=KITCHEN_TOPIC=kitchen-orders-dev`.

### Kapan device menerima push
Device subscribe saat **pertama kali membuka Kitchen Display** dan tetap subscribe (termasuk saat
app ditutup) sampai logout. Di pengaturan Kitchen Display yang sudah ada ditambah satu toggle
"Notifikasi order di device ini" untuk mematikannya tanpa logout.

## Penanganan error

| Kondisi | Perilaku |
|---|---|
| FCM down / kredensial salah | Order tetap sukses; warning di log; tablet menyusul via timer 60 detik. |
| Push hilang / telat | Timer 60 detik + sync saat resume. |
| Tablet offline lama | Notifikasi tertahan sampai TTL 1 jam; saat online, sync mengambil state penuh. |
| Izin notifikasi ditolak | Realtime tetap jalan saat app foreground; snackbar peringatan sekali bahwa tablet tidak akan bunyi di background. |
| Firebase gagal init di device | App tetap jalan; Kitchen Display turun ke timer 60 detik. |
| `defer()` di hosting non-FastCGI | Push terkirim sebelum koneksi ditutup → request bisa lebih lambat maksimal 5 detik (batas timeout) hanya saat FCM bermasalah. |

## Testing

- **PHPUnit (fake notifier di-bind ke container):** `orderCreated` terpanggil sekali saat kitchen
  order dibuat; `itemsAdded` saat merge ke order pending; `statusChanged` saat status diubah;
  tidak terpanggil saat transaksi rollback; order tetap 201 saat notifier melempar exception;
  push susulan dalam 5 detik menjadi data-only; `POST /kitchen/orders` memicu tepat satu push.
- **Dart unit test:** `KitchenOrderDiff` — order baru, item bertambah, tidak berubah, order hilang
  dari pending; `SyncCoalescer` — sync yang diminta saat sync berjalan dijalankan tepat sekali lagi.
- **Manual di tablet asli (oleh tim):** foreground, background, app di-kill, layar mati, mode
  pesawat lalu online lagi, dua tablet sekaligus.

## Rilis

1. **Backend dulu** — backward compatible; APK lama tetap polling seperti sekarang.
   Di server: upload kode + `firebase-credentials.json` manual, set `FCM_ENABLED=true`.
   Tidak perlu `composer install` (tidak ada dependency baru); butuh ekstensi `openssl` + `curl`.
2. **APK baru** ke tablet dapur/bar. Di tiap tablet: izinkan notifikasi dan **matikan battery
   optimization** untuk app ini.
3. Setelah stabil: rotate service account key (key saat ini pernah ter-paste di chat).

## Di luar scope

- Push untuk platform Windows/web/iOS.
- Targeting per user/outlet (token per device).
- Optimasi `Schema::hasTable` + `KitchenOrder::count()` per request — tidak lagi mendesak karena
  frekuensi request turun dari tiap 5 detik ke tiap 60 detik per device.
- Auto-print di sisi Kitchen Display (tetap ditangani dari sisi kasir).
