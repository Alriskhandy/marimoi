<?php

use App\Models\MailLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bersihkan Log Email lebih tua dari MailLog::RETENTION_DAYS (butuh cron `php artisan schedule:run` tiap menit).
Schedule::command('model:prune', ['--model' => [MailLog::class]])->daily();
