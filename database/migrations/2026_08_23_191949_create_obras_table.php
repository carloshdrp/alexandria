<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obras', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->char('isbn', 13)->nullable()->unique();
            $table->foreignId('editora_id')->constrained('editoras')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('capa_path', 2048)->nullable();
            $table->smallInteger('ano_publicacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('obras', function (Blueprint $table) {
            $table->index('editora_id');
            $table->index('user_id');
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};
