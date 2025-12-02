<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker Performance Evaluation - every 6 hours
Schedule::command('workers:evaluate')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/worker-evaluation.log'));

// Job Distribution - every minute
Schedule::command('jobs:distribute')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
