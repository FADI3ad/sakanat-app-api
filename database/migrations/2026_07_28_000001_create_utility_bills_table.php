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
        Schema::create('utility_bills', function (Blueprint $table) {
            $table->id();

            // Property reference
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');

            // نوع الفاتورة: كهرباء / مياه / غاز / أخرى
            $table->enum('type', ['electricity', 'water', 'gas', 'other']);

            // الشهر بصيغة YYYY-MM (مثلاً: 2026-07)
            $table->string('month', 7);

            // المبلغ (اختياري — قد لا يكون معروفاً بعد)
            $table->decimal('amount', 10, 2)->nullable();

            // حالة الدفع
            $table->boolean('is_paid')->default(false);

            // تاريخ الدفع الفعلي (يُسجَّل عند الدفع)
            $table->timestamp('paid_at')->nullable();

            // ملاحظات إضافية
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_bills');
    }
};
