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
            $table->renameColumn('vapi_call_id', 'external_call_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_ligacaos', function (Blueprint $table) {
            $table->renameColumn('external_call_id', 'vapi_call_id');
        });
    }
};
