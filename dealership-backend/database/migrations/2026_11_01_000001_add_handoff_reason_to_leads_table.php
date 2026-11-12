<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Why the bot handed this lead to sales. Fixed list so the admin
            // dashboard can filter on it; free-text detail goes in
            // interest_summary. Keep in sync with HandoffToSalesTool::REASONS.
            $table->enum('handoff_reason', [
                'financing',
                'test_drive',
                'visit',
                'negotiation',
                'deposit',
                'notify_when_available',
                'other',
            ])->nullable()->after('interest_summary');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('handoff_reason');
        });
    }
};
