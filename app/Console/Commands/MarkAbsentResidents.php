<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentResidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent
                            {--date= : تاريخ محدد بالصيغة Y-m-d (افتراضي: اليوم)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تسجيل الطلاب الغائبين بعد انتهاء وقت الكيرفيو لكل سكن';

    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date') ?? Carbon::today()->toDateString();
        $now  = Carbon::now()->format('H:i:s');

        $this->info("⏰ تشغيل سجل الغياب ليوم: {$date} - الوقت الحالي: {$now}");

        // جيب كل السكنات التي انتهى وقت كيرفيوها الآن (في الدقيقة الحالية)
        $properties = Property::whereNotNull('curfew_time')
            ->whereRaw("TIME_FORMAT(curfew_time, '%H:%i') = ?", [Carbon::now()->format('H:i')])
            ->get();

        if ($properties->isEmpty()) {
            $this->line('لا توجد سكنات بوقت كيرفيو في هذه الدقيقة.');
            return Command::SUCCESS;
        }

        $totalMarked = 0;

        foreach ($properties as $property) {
            $count = $this->attendanceService->markAbsentsForProperty($property, $date);
            $totalMarked += $count;

            $this->line("✅ [{$property->title}] → تم تسجيل {$count} غائب");
        }

        $this->info("🎯 إجمالي السجلات: {$totalMarked} طالب غائب");

        return Command::SUCCESS;
    }
}
