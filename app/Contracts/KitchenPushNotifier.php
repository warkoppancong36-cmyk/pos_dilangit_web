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
