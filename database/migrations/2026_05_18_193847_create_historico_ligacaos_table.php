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
        Schema::create('historico_ligacaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained()->cascadeOnDelete();
            $table->string('status_ligacao')->default('pendente');
            $table->integer('duracao')->nullable();
            $table->text('transcricao_ia')->nullable();
            $table->dateTime('data_ligacao')->nullable();
            $table->dateTime('proxima_tentativa')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_ligacaos');
    }
};
