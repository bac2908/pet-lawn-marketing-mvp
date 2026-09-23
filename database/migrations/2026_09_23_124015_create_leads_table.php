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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('pet_type');
            $table->string('location');
            $table->unsignedInteger('budget');
            $table->text('pain_point')->nullable();
            $table->string('interest_level')->comment('Expected: low, medium, high');
            $table->string('source')->nullable();
            $table->integer('score')->default(0);
            $table->string('segment')->default('COLD');
            $table->string('status')->default('new')
                ->comment('Expected: new, contacted, qualified, converted, lost');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
