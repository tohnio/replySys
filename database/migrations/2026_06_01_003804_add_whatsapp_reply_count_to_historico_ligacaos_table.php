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
            $table->integer('whatsapp_reply_count')->default(0);
            $table->string('last_whatsapp_message_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_ligacaos', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_reply_count', 'last_whatsapp_message_id']);
        });
    }
};
