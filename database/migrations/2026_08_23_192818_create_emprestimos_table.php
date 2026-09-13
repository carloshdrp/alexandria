<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('exemplar_id')->constrained('exemplares')->restrictOnDelete();
            $table->timestamp('retirado_em');
            $table->date('prazo_devolucao');
            $table->timestamp('devolvido_em')->nullable();
            $table->unsignedTinyInteger('situacao')->default(1);
            $table->unsignedTinyInteger('qtd_renovacoes')->default(0);
            $table->timestamps();
        });

        Schema::table('emprestimos', function (Blueprint $table) {
            $table->index('prazo_devolucao');
        });

        DB::statement('CREATE UNIQUE INDEX uniq_exemplar_em_aberto ON emprestimos (exemplar_id) WHERE devolvido_em IS NULL');
        DB::statement('CREATE INDEX idx_emprestimos_usuario_aberto ON emprestimos (user_id) WHERE devolvido_em IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('emprestimos');
    }
};
