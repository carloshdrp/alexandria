<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('exemplar_id')->nullable()->constrained('exemplares')->restrictOnDelete();
            $table->unsignedTinyInteger('situacao')->default(1);
            $table->timestamp('disponibilizada_em')->nullable();
            $table->timestamp('expira_em')->nullable();
            $table->timestamps();
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->index('expira_em');
        });

        DB::statement('CREATE UNIQUE INDEX uniq_reserva_viva ON reservas (obra_id, user_id) WHERE situacao IN (1, 2)');
        DB::statement('CREATE INDEX idx_reservas_fila ON reservas (obra_id, created_at) WHERE situacao = 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
