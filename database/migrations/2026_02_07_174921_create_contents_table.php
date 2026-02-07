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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Quem criou
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type'); // enum: 'info', 'marketing', 'alert' (emergência tem prioridade)
            $table->string('media_path')->nullable(); // Para imagens/vídeos
            $table->string('media_type')->nullable(); // image, video
            $table->integer('duration_seconds')->default(15); // Tempo de exibição
            $table->dateTime('start_at')->nullable(); // Agendamento
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
