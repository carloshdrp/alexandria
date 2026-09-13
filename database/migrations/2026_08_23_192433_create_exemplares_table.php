<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exemplares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->string('codigo_patrimonio')->unique();
            $table->unsignedTinyInteger('estado_conservacao');
            $table->unsignedTinyInteger('situacao')->default(1);
            $table->unsignedTinyInteger('motivo_baixa')->nullable();
            $table->timestamp('baixado_em')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('exemplares', function (Blueprint $table) {
            $table->index('obra_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemplares');
    }
};
