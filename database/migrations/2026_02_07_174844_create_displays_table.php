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
        Schema::create('displays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Refeitório", "Recepção"
            $table->string('location')->nullable();
            $table->string('ip_address')->nullable(); // Para auditoria
            $table->string('status')->default('offline'); // online, offline, maintenance
            // Um token único para a TV se autenticar/identificar ao ligar (modo kiosk)
            $table->uuid('device_uuid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('displays');
    }
};
