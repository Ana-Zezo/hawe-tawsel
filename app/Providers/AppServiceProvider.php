<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Essa\APIToolKit\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExceptionHandler::class, Handler::class);
        // $app->register()
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        $lang = $request->header('lang', 'en');
        App::setLocale($lang);
    }

}