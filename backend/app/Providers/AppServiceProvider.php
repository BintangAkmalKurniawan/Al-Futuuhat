<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
        // KNF-09: di luar produksi, mengakses relasi yang belum di-eager load
        // langsung memunculkan galat supaya query N+1 ketahuan saat pengembangan.
        Model::preventLazyLoading(! app()->isProduction());
    }
}
