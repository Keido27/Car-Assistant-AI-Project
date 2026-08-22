<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // 'whatsapp' = created by the bot from an inbound message.
            // 'manual' = staff added it directly (walk-in, phone call, etc).
            $table->enum('source', ['whatsapp', 'manual'])->default('whatsapp')->after('wa_thread_id');
            // wa_thread_id was required+unique because every lead used to come
            // from WhatsApp. Manual leads don't have one, so it must be nullable now.
            $table->string('wa_thread_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->string('wa_thread_id')->nullable(false)->change();
        });
    }
};
