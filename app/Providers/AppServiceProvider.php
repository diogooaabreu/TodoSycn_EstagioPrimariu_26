<?php

namespace App\Providers;

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

        // Se estiver em ambiente mobile (NativePHP)
        if (app()->runningInConsole() === false && PHP_OS_FAMILY === 'Linux') {
            config(['database.default' => 'sqlite']);
        }


        // No Android usa SQLite, no browser usa MySQL
//        if (PHP_OS === 'Linux' && str_contains(php_uname('r'), 'android')
//            || isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === '127.0.0.1') {
//            config(['database.default' => 'sqlite']);
//        } else {
//            config(['database.default' => 'mysql']);
//        }
        //// Força sempre a ligação ao MySQL Railway
             //config(['database.default' => 'mysql']);
    }
}
