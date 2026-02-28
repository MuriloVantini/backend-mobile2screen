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
        Schema::create('alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('status', 20)->comment('pending, delivered, failed, acknowledged, dismissed');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable()->comment('Quando o usuário visualizou');
            $table->timestamp('dismissed_at')->nullable()->comment('Quando o usuário fechou o alerta');
            $table->text('error_message')->nullable()->comment('Mensagem de erro caso falhe');
            $table->integer('retry_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('alert_id');
            $table->index('device_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['alert_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_deliveries');
    }
};
