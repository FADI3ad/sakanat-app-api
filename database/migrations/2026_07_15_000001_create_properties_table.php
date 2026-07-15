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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            // Owner reference (must be a property_owner type user)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Simplified fields
            $table->string('title');                     // اسم/عنوان السكن (مثلاً: شقة النيل)
            $table->string('city');                      // المدينة
            $table->string('floor')->nullable();         // الطابق
            $table->text('address_details')->nullable(); // تفاصيل العنوان
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->double('radius')->nullable();
            $table->boolean('is_available')->default(true); // هل السكن متاح؟
            $table->text('description')->nullable();         // وصف إضافي

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
