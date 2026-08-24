<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Meta's message id, used to dedupe webhook retries (Meta redelivers
            // if we don't ack fast enough). Only set for sender=customer rows
            // that came in via the webhook.
            $table->string('wa_message_id')->nullable()->unique()->after('sender');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('wa_message_id');
        });
    }
};