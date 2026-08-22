<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate table (not a JSON column) so we can reorder, caption, and
        // delete individual photos from the admin UI without rewriting a blob.
        Schema::create('car_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // key in S3-compatible storage (R2/Spaces)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_photos');
    }
};
