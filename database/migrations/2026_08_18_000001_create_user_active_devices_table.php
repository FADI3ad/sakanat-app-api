<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_active_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('personal_access_token_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->char('device_identifier', 64);
            $table->timestamp('created_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('revoked_at')->nullable();

            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_active_devices');
    }
};
