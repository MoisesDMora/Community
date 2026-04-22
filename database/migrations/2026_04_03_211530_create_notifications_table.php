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
        Schema::create('notifications', function (Blueprint $column) {
            $column->id();
            $column->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $column->string('type')->default('property_change');
            $column->string('title');
            $column->text('message')->nullable();
            $column->json('data')->nullable(); // To store old/new property details
            $column->boolean('is_read')->default(false);
            $column->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
