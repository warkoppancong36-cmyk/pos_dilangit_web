# Kitchen Display — Push FCM

Order baru / item tambahan dikirim ke tablet dapur lewat FCM topic. Push hanyalah **sinyal**;
Kitchen Display selalu mengambil ulang seluruh order pending dari `GET /kitchen/orders`, dan tetap
sync tiap 60 detik sebagai jaring pengaman. Desain lengkap:
`docs/superpowers/specs/2026-09-18-kitchen-fcm-push-design.md`.

Backend **tidak memakai library Firebase** — `App\Services\Push\FcmClient` berbicara langsung ke
FCM HTTP v1 (JWT RS256 → token OAuth → `messages:send`) dengan `Http` bawaan Laravel dan ekstensi
`openssl`. Tidak ada paket composer baru yang perlu di-install di server.

## Deploy backend (sekali)

1. Upload kode. Tidak perlu `composer install` untuk fitur ini (tidak ada dependency baru).
   Pastikan ekstensi PHP `openssl` dan `curl` aktif (standar di hampir semua hosting).
2. Upload `firebase-credentials.json` ke `storage/app/firebase/` (manual — file ini tidak ada di git).
3. `.env` production:
   ```
   FIREBASE_CREDENTIALS=storage/app/firebase/firebase-credentials.json
   FCM_TIMEOUT=5
   FCM_ENABLED=true
   FCM_KITCHEN_TOPIC=kitchen-orders
   ```
4. `php artisan optimize:clear`
5. `php artisan kitchen:push-test` → harus `Terkirim ke topic 'kitchen-orders'.`
   Tablet yang sudah subscribe akan berbunyi.

Access token Google di-cache 55 menit lewat cache store Laravel (`CACHE_STORE`), dan peredam bunyi
juga memakai cache. Kalau `CACHE_STORE=database`, tabel `cache` harus ada.

Backend aman di-deploy lebih dulu: APK lama tetap polling seperti biasa.

## Setup tiap tablet dapur/bar (sekali per device)

1. Install APK baru, login, buka **Kitchen Display** → izinkan notifikasi saat diminta.
2. Android Settings → Apps → pos_dilangit → Battery → **Unrestricted** (matikan battery optimization).
   Tanpa ini Android bisa menahan push saat layar mati.
3. Pastikan ikon lonceng di AppBar Kitchen Display menyala (Notifikasi order: ON).

Device mulai menerima push saat pertama kali membuka Kitchen Display dan tetap menerima (termasuk
saat app ditutup) sampai logout atau lonceng dimatikan. Tablet kasir yang tidak perlu berbunyi:
matikan loncengnya.

## Uji manual (wajib sebelum dipakai operasional)

| # | Kondisi tablet | Aksi di kasir | Harapan |
|---|---|---|---|
| 1 | Kitchen Display terbuka | Buat order berisi item dapur | Kartu muncul ≤ 2 detik, bunyi loop sampai di-accept |
| 2 | Kitchen Display terbuka | Tambah item ke order yang masih pending | Kartu yang sama bertambah itemnya + snackbar "Item Tambahan" |
| 3 | App terbuka di layar lain (mis. POS) | Buat order | Bunyi sekali + snackbar dengan tombol BUKA |
| 4 | App di background | Buat order | Notifikasi sistem + bunyi; tap → Kitchen Display |
| 5 | App ditutup (swipe) + layar mati | Buat order | Notifikasi sistem + bunyi |
| 6 | Mode pesawat 2 menit, lalu online | Buat order saat offline | Order muncul saat online kembali (≤ 60 detik) |
| 7 | Dua tablet | Accept order di tablet A | Order pindah ke "sudah di print" di tablet B tanpa refresh |
| 8 | Cart 5 item dapur sekaligus | Proses cart | Tablet berbunyi sekali, bukan 5 kali; kelima item tampil |
| 9 | — | Tambah item dapur dari dialog transaksi | Item muncul **satu kali** di dapur; tidak ada snackbar "Kitchen Order Warning" |

Testing dari laptop tanpa membunyikan tablet production: backend lokal pakai
`FCM_KITCHEN_TOPIC=kitchen-orders-dev`, app dijalankan dengan
`flutter run --dart-define=KITCHEN_TOPIC=kitchen-orders-dev`.

## Troubleshooting

- Tidak ada push sama sekali → `php artisan kitchen:push-test`; cek `FCM_ENABLED`, lalu
  `storage/logs/laravel.log` untuk `Kitchen push failed`.
- `kitchen:push-test` gagal dengan `QueryException ... cache` → cache store tidak terjangkau
  (DB mati / tabel `cache` belum ada).
- `403` / `PERMISSION_DENIED` → Firebase Cloud Messaging API (V1) belum Enabled di Firebase Console.
- Push masuk saat app terbuka tapi tidak saat ditutup → battery optimization / izin notifikasi.
- Notifikasi muncul tanpa suara order → channel `kitchen_orders_v1` dibuat saat app pertama dibuka;
  buka app sekali. Mengganti suara butuh ID channel baru (`_v2`) di `MainActivity.kt`,
  `AndroidManifest.xml`, dan `FcmKitchenPushNotifier::CHANNEL_ID`.
- Rotate kredensial: generate key baru di Firebase Console → timpa file → hapus key lama di
  Google Cloud IAM. Tidak ada perubahan kode (cache token ikut `private_key_id`, jadi otomatis baru).
