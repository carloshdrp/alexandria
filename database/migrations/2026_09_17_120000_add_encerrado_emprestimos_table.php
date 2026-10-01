<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->timestamp('encerrado_em')->nullable()->after('devolvido_em');
        });
    }

    public function down(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dropColumn('encerrado_em');
        });
    }
};
