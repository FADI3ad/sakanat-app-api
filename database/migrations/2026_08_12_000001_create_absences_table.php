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
        Schema::create('absences', function (Blueprint $table) {
            $table->id();

            // الطالب (resident) صاحب البلاغ
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // السكن المرتبط بالبلاغ
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->onDelete('cascade');

            // السرير المرتبط بالطالب وقت البلاغ
            $table->foreignId('bed_id')
                  ->constrained('beds')
                  ->onDelete('cascade');

            // تاريخ بداية الغياب
            $table->date('start_date');

            // تاريخ نهاية الغياب
            $table->date('end_date');

            // سبب الغياب / السفر (اختياري)
            $table->text('reason')->nullable();

            $table->timestamps();

            // فهارس للأداء
            $table->index(['property_id', 'start_date', 'end_date']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
