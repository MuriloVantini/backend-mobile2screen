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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // 'free', 'pro', 'enterprise'
            $table->integer('max_devices')->comment('Limite de dispositivos (-1 = ilimitado)');
            $table->integer('max_alerts_per_month')->nullable()->comment('Limite de alertas por mês (-1 = ilimitado)');
            $table->json('features')->nullable()->comment('Recursos específicos do plano');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamp('created_at')->useCurrent();
        });

        // Inserir planos padrão
        DB::table('plans')->insert([
            [
                'name' => 'free',
                'max_devices' => 5,
                'max_alerts_per_month' => 100,
                'price' => 0.00,
                'features' => json_encode([
                    'api_access' => false,
                    'webhooks' => false,
                    'support' => 'community'
                ])
            ],
            [
                'name' => 'pro',
                'max_devices' => 20,
                'max_alerts_per_month' => 1000,
                'price' => 49.90,
                'features' => json_encode([
                    'api_access' => true,
                    'webhooks' => true,
                    'support' => 'email',
                    'custom_branding' => false
                ])
            ],
            [
                'name' => 'enterprise',
                'max_devices' => -1,
                'max_alerts_per_month' => -1,
                'price' => 199.90,
                'features' => json_encode([
                    'api_access' => true,
                    'webhooks' => true,
                    'support' => 'priority',
                    'custom_branding' => true,
                    'sla' => true
                ])
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
