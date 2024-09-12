<?php
declare(strict_types=1);

namespace App\Providers;

use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class, function () {
            return new RabbitMQService();
        });
    }

    public function boot(): void
    {
    }
}
