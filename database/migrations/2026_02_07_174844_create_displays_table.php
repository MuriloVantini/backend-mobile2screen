<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->comment('tv, rpi'); // tipo de dispositivo
            $table->string('location')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->macAddress('mac_address')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen')->nullable();
            $table->string('connection_token', 500)->unique()->comment('Token para conexão WebSocket');
            $table->json('metadata')->nullable()->comment('Informações extras: resolução, firmware, etc');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('is_online');
            $table->index('type');
            $table->index(['user_id', 'is_online']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
