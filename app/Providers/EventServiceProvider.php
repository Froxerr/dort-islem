<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\QuizCompleted;
use App\Listeners\ProcessQuizBadgeResult;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        QuizCompleted::class => [
            ProcessQuizBadgeResult::class,
        ]
    ];

    public function boot(): void
    {
        //
    }
} 