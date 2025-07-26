<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth', \App\Http\Middleware\LogBroadcastAuth::class]]);

        // Debug: Broadcast auth isteklerini logla
        \Illuminate\Support\Facades\Event::listen('Illuminate\Broadcasting\BroadcastEvent', function ($event) {
            \Log::info('Broadcast event triggered', [
                'event' => get_class($event)
            ]);
        });

        require base_path('routes/channels.php');
    }
} 