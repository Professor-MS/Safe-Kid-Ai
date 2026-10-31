<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->tinyInteger('battery_level')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Composite index for fast temporal location lookups per child
            $table->index(['child_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_history');
    }
};

