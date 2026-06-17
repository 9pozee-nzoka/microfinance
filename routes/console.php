<?php

use App\Jobs\ProcessSmsScheduleJob;
use App\Models\SmsSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update loan arrears hourly so cached columns stay in sync with schedules.
// Payments also trigger an immediate recalculation, but this catches loans that
// become overdue without a payment event.
Schedule::command('loans:update-arrears')->hourly()->name('update-loan-arrears')->withoutOverlapping();

// Run all active SMS schedules daily at 8 AM
Schedule::call(function () {
    SmsSchedule::where('status', 'active')
        ->where('trigger_type', '!=', 'manual')
        ->each(fn ($schedule) => ProcessSmsScheduleJob::dispatch($schedule));
})->dailyAt('08:00')->name('sms-schedules-daily')->withoutOverlapping();
