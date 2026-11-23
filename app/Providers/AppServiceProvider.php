<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tombol "Hubungi Admin" di halaman login & daftar (isi di config/contact.php dan .env).
        Route::pushMiddlewareToGroup('web', \App\Http\Middleware\InjectAdminContact::class);
    }
}
