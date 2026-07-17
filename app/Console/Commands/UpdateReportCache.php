<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ReportCacheService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateReportCache extends Command
{
    protected $signature = 'report:update-cache
        {--date= : Rebuild a single date (Y-m-d)}
        {--from= : Start of a date range (Y-m-d)}
        {--to= : End of a date range (Y-m-d, defaults to today)}
        {--all : Rebuild every date that has orders}
        {--force : Delete existing cache rows for the date(s) first}';

    protected $description = 'Rebuild report cache tables (report_transaction_cache & report_sales_daily) via ReportCacheService';

    public function handle(ReportCacheService $reportCache)
    {
        $force = $this->option('force');
        $dates = $this->resolveDates();

        if ($dates->isEmpty()) {
            $this->info('Nothing to rebuild — no matching dates with orders.');

            return 0;
        }

        $this->info("🚀 Rebuilding report cache for {$dates->count()} date(s)...");

        foreach ($dates as $date) {
            $date = Carbon::parse($date);

            try {
                if ($force) {
                    DB::table('report_transaction_cache')
                        ->whereDate('order_date', $date)
                        ->delete();
                }

                $count = 0;
                Order::whereDate('created_at', $date)
                    ->chunkById(200, function ($orders) use ($reportCache, &$count) {
                        foreach ($orders as $order) {
                            $reportCache->updateReportCache($order, withDailySummary: false);
                            $count++;
                        }
                    }, 'id_order');

                $reportCache->updateDailySummary($date);

                $this->info("   ✓ {$date->format('Y-m-d')}: {$count} transactions cached");
            } catch (\Exception $e) {
                $this->error("   ✗ {$date->format('Y-m-d')}: " . $e->getMessage());
            }
        }

        $this->info('✅ Report cache rebuild finished!');

        return 0;
    }

    private function resolveDates()
    {
        if ($this->option('all')) {
            return DB::table('orders')
                ->selectRaw('DATE(created_at) as order_day')
                ->distinct()
                ->orderBy('order_day')
                ->pluck('order_day');
        }

        if ($this->option('from')) {
            $from = Carbon::parse($this->option('from'));
            $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::today();

            return collect(CarbonPeriod::create($from, $to)->toArray())
                ->map(fn ($d) => $d->format('Y-m-d'));
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

        return collect([$date->format('Y-m-d')]);
    }
}
