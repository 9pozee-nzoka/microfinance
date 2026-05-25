<?php

use App\Jobs\ProcessSmsScheduleJob;
use App\Models\SmsSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run all active SMS schedules daily at 8 AM
Schedule::call(function () {
    SmsSchedule::where('status', 'active')
        ->where('trigger_type', '!=', 'manual')
        ->each(fn ($schedule) => ProcessSmsScheduleJob::dispatch($schedule));
})->dailyAt('08:00')->name('sms-schedules-daily')->withoutOverlapping();
