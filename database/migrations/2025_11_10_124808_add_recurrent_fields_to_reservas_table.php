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

            // Verifica se a coluna is_fixed existe antes de adicionar

            if (!Schema::hasColumn('reservas', 'is_fixed')) {

                // Coluna que indica se a reserva é recorrente/fixa

                $table->boolean('is_fixed')->default(false)->after('status');

            }



            // Verifica se a coluna day_of_week existe antes de adicionar

            if (!Schema::hasColumn('reservas', 'day_of_week')) {

                // Dia da semana para reservas fixas (0=Dom, 1=Seg, etc.)

                $table->tinyInteger('day_of_week')->nullable()->after('is_fixed');

            }



            // Opcional: Adicionar um campo para agrupar reservas recorrentes, se você usa isso.

            if (!Schema::hasColumn('reservas', 'recurrent_series_id')) {

                 $table->unsignedBigInteger('recurrent_series_id')->nullable()->after('day_of_week');

            }

        });

    }



    /**

     * Reverse the migrations.

     */

    public function down(): void

    {

        Schema::table('reservas', function (Blueprint $table) {

            $table->dropColumn(['is_fixed', 'day_of_week', 'recurrent_series_id']);

        });

    }

};
