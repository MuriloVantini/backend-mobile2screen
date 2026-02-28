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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_alert_failed')->default(true);
            $table->boolean('notify_device_offline')->default(true);
            $table->boolean('notify_weekly_report')->default(false);
            $table->boolean('notify_device_connected')->default(true);
            $table->boolean('notify_limit_reached')->default(true);
            $table->string('notification_email')->nullable();
            $table->string('notification_phone', 50)->nullable();
            $table->string('timezone', 50)->default('America/Sao_Paulo');
            $table->string('language', 10)->default('pt-BR');
            $table->string('theme', 20)->default('light')->comment('light, dark, auto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
