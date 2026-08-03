<?php

use App\Console\Commands\MarkAbsentResidents;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Attendance Curfew Scheduler
|--------------------------------------------------------------------------
| يشتغل كل دقيقة ويتحقق إذا حان وقت الكيرفيو لأي سكن،
| وإذا حان → يسجل الغائبين تلقائياً.
|
| لتفعيله على السيرفر أضف هذا السطر في crontab:
|   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command(MarkAbsentResidents::class)->everyMinute();
