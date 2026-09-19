<?php

namespace App\Console\Commands;

use App\Services\Push\FcmClient;
use App\Services\Push\FcmKitchenPushNotifier;
use Illuminate\Console\Command;
use Throwable;

class KitchenPushTest extends Command
{
    protected $signature = 'kitchen:push-test';

    protected $description = 'Kirim satu notifikasi uji ke topic Kitchen Display untuk memastikan konfigurasi FCM benar';

    public function handle(FcmClient $client): int
    {
        $topic = config('services.fcm.kitchen_topic');

        $this->line('FCM_ENABLED          : ' . (config('services.fcm.enabled') ? 'true' : 'false'));
        $this->line('FCM_KITCHEN_TOPIC    : ' . $topic);
        $this->line('FIREBASE_CREDENTIALS : ' . config('services.fcm.credentials'));

        if (! config('services.fcm.enabled')) {
            $this->warn('FCM_ENABLED=false — order sungguhan TIDAK akan mengirim push. Pesan uji tetap dicoba.');
        }

        try {
            $client->send([
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
            ]);
        } catch (Throwable $e) {
            $this->error('Gagal mengirim: ' . get_class($e) . ' — ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Terkirim ke topic '{$topic}'.");

        return self::SUCCESS;
    }
}
