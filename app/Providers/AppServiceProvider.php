<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- 1. Tambahkan Baris Ini

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
        // 2. Tambahkan Logika "Paksa HTTPS" ini
        // Jika aplikasi berjalan di mode 'production' (Hosting),
        // maka paksa semua link (CSS, JS, Gambar) pakai HTTPS.
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}