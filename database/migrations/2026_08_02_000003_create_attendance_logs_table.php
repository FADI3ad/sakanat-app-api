<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // السكن المرتبط بالسجل
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->onDelete('cascade');

            // السرير المرتبط (يحدد الطالب المعين)
            $table->foreignId('bed_id')
                  ->constrained('beds')
                  ->onDelete('cascade');

            // الطالب (resident) صاحب الحساب
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // تاريخ اليوم المسجَّل
            $table->date('date');

            // حالة الحضور
            $table->enum('status', ['present', 'absent', 'late'])->default('absent');

            // وقت تسجيل الدخول الفعلي (null لو غائب)
            $table->timestamp('checked_in_at')->nullable();

            // إحداثيات الطالب وقت المسح
            $table->decimal('scanned_latitude', 10, 8)->nullable();
            $table->decimal('scanned_longitude', 11, 8)->nullable();

            // المسافة من السكن بالمتر
            $table->float('distance_from_property')->nullable();

            $table->timestamps();

            // لا يجوز تكرار سجل لنفس الطالب + نفس اليوم
            $table->unique(['bed_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
