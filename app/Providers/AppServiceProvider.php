<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseItem;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use App\Observers\PurchaseItemObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Push\FcmClient::class, function () {
            return new \App\Services\Push\FcmClient(
                config('services.fcm.credentials'),
                config('services.fcm.timeout')
            );
        });

        // FCM_ENABLED=false (lokal/testing) → push jadi no-op
        $this->app->bind(\App\Contracts\KitchenPushNotifier::class, function ($app) {
            if (! config('services.fcm.enabled')) {
                return new \App\Services\Push\NullKitchenPushNotifier();
            }

            return new \App\Services\Push\FcmKitchenPushNotifier(
                $app->make(\App\Services\Push\FcmClient::class),
                config('services.fcm.kitchen_topic')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route model binding for Category
        Route::model('category', Category::class);
        Route::bind('category', function ($value) {
            return Category::where('id_category', $value)->firstOrFail();
        });

        // Register observers
        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);
        PurchaseItem::observe(PurchaseItemObserver::class);
    }
}
