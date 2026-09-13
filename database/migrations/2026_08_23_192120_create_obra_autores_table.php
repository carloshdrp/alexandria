<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_autores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('autor_id')->constrained('autores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('ordem')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
            $table->unique(['obra_id', 'autor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_autores');
    }
};
