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
