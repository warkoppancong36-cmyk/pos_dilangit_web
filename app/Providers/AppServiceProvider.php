<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\PurchaseItem;
use App\Observers\PurchaseItemObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        PurchaseItem::observe(PurchaseItemObserver::class);
    }
}
