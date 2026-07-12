<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\ReportCacheService;

/**
 * Keeps report_transaction_cache.items_detail in sync with order item writes.
 *
 * Necessary because several flows (e.g. processDirectPayment) create the
 * Order — which triggers OrderObserver — BEFORE its items exist, and never
 * touch the order row again, leaving the cached items_detail empty.
 */
class OrderItemObserver
{
    public function __construct(private ReportCacheService $reportCache)
    {
    }

    public function created(OrderItem $orderItem): void
    {
        $this->refreshOrderCache($orderItem);
    }

    public function updated(OrderItem $orderItem): void
    {
        $this->refreshOrderCache($orderItem);
    }

    public function deleted(OrderItem $orderItem): void
    {
        $this->refreshOrderCache($orderItem);
    }

    private function refreshOrderCache(OrderItem $orderItem): void
    {
        $order = $orderItem->order;

        if ($order) {
            // Item changes only affect items_detail/items_count — the daily
            // revenue summary is derived from the orders table and is kept
            // up to date by OrderObserver
            $this->reportCache->updateReportCache($order, withDailySummary: false);
        }
    }
}
