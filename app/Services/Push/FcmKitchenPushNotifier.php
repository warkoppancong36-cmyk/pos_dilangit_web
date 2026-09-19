<?php

namespace App\Services\Push;

use App\Contracts\KitchenPushNotifier;
use App\Models\KitchenOrder;
use Illuminate\Support\Facades\Cache;

/**
 * Melempar exception kalau FCM gagal — yang menangkap dan me-log adalah KitchenPushDispatcher.
 */
class FcmKitchenPushNotifier implements KitchenPushNotifier
{
    /** Harus sama dengan channel yang dibuat di MainActivity.kt (Flutter). */
    public const CHANNEL_ID = 'kitchen_orders_v1';

    /** Nama file di android/app/src/main/res/raw tanpa ekstensi. */
    public const SOUND = 'kitchen_alert';

    /** Cart berisi N item = N push beruntun; hanya satu yang boleh berbunyi per jendela ini. */
    private const ALERT_WINDOW_SECONDS = 5;

    public function __construct(
        private FcmClient $client,
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

        $this->client->send([
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
        ]);
    }

    private function sendSilent(KitchenOrder $kitchenOrder, string $type, string $priority, string $ttl): void
    {
        $this->client->send([
            'topic' => $this->topic,
            'data' => $this->data($kitchenOrder, $type),
            'android' => [
                'priority' => $priority,
                'ttl' => $ttl,
            ],
        ]);
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
