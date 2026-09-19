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
