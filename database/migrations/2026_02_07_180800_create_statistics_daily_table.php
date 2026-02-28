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
        Schema::create('statistics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->integer('alerts_sent')->default(0);
            $table->integer('alerts_delivered')->default(0);
            $table->integer('alerts_failed')->default(0);
            $table->decimal('devices_online_avg', 5, 2)->nullable()->comment('Média de dispositivos online no dia');
            $table->decimal('delivery_rate', 5, 2)->nullable()->comment('Taxa de entrega em %');
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['user_id', 'date']);
            $table->index('user_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics_daily');
    }
};
