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
