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
        Schema::table('historico_ligacaos', function (Blueprint $table) {
            $table->string('vapi_call_id')->nullable()->after('ordem_servico_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_ligacaos', function (Blueprint $table) {
            $table->dropColumn('vapi_call_id');
        });
    }
};
