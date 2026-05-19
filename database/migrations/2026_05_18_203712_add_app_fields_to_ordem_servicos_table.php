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
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->string('modelo')->nullable()->after('cliente_id');
            $table->date('data_pronto')->nullable()->after('data_entrada');
            $table->string('status_pagamento')->default('pendente')->after('valor_orcamento');
            $table->decimal('valor_pago', 10, 2)->default(0)->after('status_pagamento');
            $table->dateTime('data_entregue')->nullable()->after('data_reparo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->dropColumn(['modelo', 'data_pronto', 'status_pagamento', 'valor_pago', 'data_entregue']);
        });
    }
};
