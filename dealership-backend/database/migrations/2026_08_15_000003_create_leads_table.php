<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // often unknown until bot asks
            $table->string('phone'); // WhatsApp number, E.164-ish, e.g. 62812xxxxxxx
            $table->string('wa_thread_id')->unique(); // WhatsApp conversation/contact id
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete(); // primary car of interest
            $table->text('interest_summary')->nullable(); // bot-maintained running summary
            $table->enum('status', [
                'bot_handling',      // bot is actively responding
                'needs_handoff',     // flagged, waiting for a human to pick up
                'human_handling',    // a staff member has taken over
                'visit_scheduled',
                'converted',         // purchased
                'lost',
            ])->default('bot_handling');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
