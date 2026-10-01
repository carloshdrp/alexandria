<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('enfileirada_em')->nullable()->after('situacao');
        });

        DB::statement('UPDATE reservas SET enfileirada_em = created_at');

        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('enfileirada_em')->nullable(false)->change();
        });

        DB::statement('DROP INDEX idx_reservas_fila');
        DB::statement('CREATE INDEX idx_reservas_fila ON reservas (obra_id, enfileirada_em) WHERE situacao = 1');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_reservas_fila');
        DB::statement('CREATE INDEX idx_reservas_fila ON reservas (obra_id, created_at) WHERE situacao = 1');

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('enfileirada_em');
        });
    }
};
