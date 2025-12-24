<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

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
        // Paginator pakai Bootstrap 5
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Hindari masalah panjang index pada MySQL lama
        Schema::defaultStringLength(191);

        // Tarik pengaturan dari config(app.*) yang membaca .env
        $tz     = config('app.timezone', 'UTC');
        $locale = config('app.locale', 'en');

        // Set timezone PHP & Carbon agar format() mengikuti WIB
        if (function_exists('date_default_timezone_set')) {
            @date_default_timezone_set($tz);
        }
        Carbon::setLocale($locale);

        // Jika pakai CarbonImmutable uncomment ini:
        // \Illuminate\Support\Facades\Date::use(CarbonImmutable::class);

        // (Opsional) paksa https di production:
        // if ($this->app->environment('production')) {
        //     \Illuminate\Support\Facades\URL::forceScheme('https');
        // }
    }
}
