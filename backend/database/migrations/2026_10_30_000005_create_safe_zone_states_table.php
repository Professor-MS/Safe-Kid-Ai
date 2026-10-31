<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safe_zone_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('safe_zone_id')->constrained('safe_zones')->cascadeOnDelete();
            $table->boolean('is_inside');
            $table->timestamp('last_evaluated_at');
            $table->timestamps();

            $table->unique(['child_id', 'safe_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_zone_states');
    }
};

