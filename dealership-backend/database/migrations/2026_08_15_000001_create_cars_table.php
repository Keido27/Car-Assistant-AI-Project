<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('variant')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('price'); // in IDR, smallest unit (rupiah, no decimals)
            $table->unsignedInteger('mileage')->nullable(); // km
            $table->enum('transmission', ['manual', 'automatic', 'cvt'])->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric'])->nullable();
            $table->text('condition_notes')->nullable(); // free text the bot is grounded in
            $table->string('color')->nullable();
            $table->string('plate_region')->nullable(); // e.g. "B" for Jakarta plates — common buyer question
            $table->enum('status', ['ready', 'booked', 'sold'])->default('ready');
            $table->string('stock_number')->unique(); // internal reference, shown to customers e.g. "STK-0231"
            $table->timestamps();
            $table->softDeletes(); // keep sold-car history instead of hard delete

            $table->index(['brand', 'model', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
