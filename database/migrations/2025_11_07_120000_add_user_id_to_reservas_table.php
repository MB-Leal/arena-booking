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
        // Verifica se a tabela 'reservas' existe antes de tentar alterá-la.
        if (Schema::hasTable('reservas')) {
            // Adiciona a coluna user_id à tabela 'reservas' se ela não existir.
            if (!Schema::hasColumn('reservas', 'user_id')) {
                Schema::table('reservas', function (Blueprint $table) {
                    // Adiciona a FK para o usuário (cliente) que fez a reserva.
                    // É NULLABLE porque o gestor pode criar reservas para clientes não cadastrados (user_id = null).
                    $table->foreignId('user_id')
                          ->nullable()
                          ->after('manager_id') // Coloca após manager_id (pode ser ajustado)
                          ->constrained('users') // Assumindo que a tabela de usuários é 'users'
                          ->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (Schema::hasColumn('reservas', 'user_id')) {
                // Dropar a foreign key antes de dropar a coluna
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
