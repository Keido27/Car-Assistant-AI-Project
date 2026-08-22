<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->enum('sender', ['bot', 'human', 'customer'])->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // set when sender = human
            $table->text('message');
            $table->json('meta')->nullable(); // tool calls made, confidence flags, WA message id, etc.
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['lead_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
