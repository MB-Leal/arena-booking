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
        Schema::table('reservas', function (Blueprint $table) {

            // Verifica antes de adicionar 'is_fixed'
            if (!Schema::hasColumn('reservas', 'is_fixed')) {
                $table->boolean('is_fixed')->default(false)->after('status');
            }

            // Verifica antes de adicionar 'day_of_week'
            if (!Schema::hasColumn('reservas', 'day_of_week')) {
                $table->tinyInteger('day_of_week')->nullable()->index()->after('is_fixed');
            }

            // Verifica antes de adicionar 'recurrent_series_id'
            if (!Schema::hasColumn('reservas', 'recurrent_series_id')) {
                $table->uuid('recurrent_series_id')->nullable()->index()->after('day_of_week');
            }

            // Verifica antes de adicionar 'week_index'
            if (!Schema::hasColumn('reservas', 'week_index')) {
                $table->integer('week_index')->nullable()->after('recurrent_series_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Também é uma boa prática verificar antes de remover,
            // embora 'dropIfExists' seja o ideal.
            // Mas para consistência com o up(), faremos assim:

            $columnsToDrop = ['is_fixed', 'day_of_week', 'recurrent_series_id', 'week_index'];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('reservas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
