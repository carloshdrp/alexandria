<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprestimo_renovacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprestimo_id')->constrained('emprestimos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('sequencia');
            $table->date('prazo_anterior');
            $table->date('prazo_novo');
            $table->timestamp('renovado_em');
            $table->timestamps();
            $table->unique(['emprestimo_id', 'sequencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprestimo_renovacoes');
    }
};
