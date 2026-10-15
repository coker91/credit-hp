<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The command gates the local sending time and selects only H-1 / due-date installments.
Schedule::command('whatsapp:reminders')->everyMinute()->withoutOverlapping(30);
