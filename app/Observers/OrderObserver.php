<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\ReportCacheService;

class OrderObserver
{
    public function __construct(private ReportCacheService $reportCache)
    {
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->reportCache->updateReportCache($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Update cache when order status changes or amount changes
        if ($order->wasChanged(['status', 'total_amount', 'discount_amount', 'tax_amount'])) {
            $this->reportCache->updateReportCache($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        $this->reportCache->removeOrder($order);
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        $this->reportCache->updateReportCache($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        $this->reportCache->removeOrder($order);
    }
}
