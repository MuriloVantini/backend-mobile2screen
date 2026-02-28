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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type', 20)->comment('info, warning, critical, success');
            $table->integer('duration_seconds')->nullable()->comment('Tempo de exibição (NULL = indefinido)');
            $table->integer('priority')->default(0)->comment('Prioridade de exibição');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('Quando o alerta expira');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
            $table->index('type');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
