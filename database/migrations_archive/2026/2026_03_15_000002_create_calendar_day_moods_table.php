<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_day_moods', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Owning user');
            $table->date('date')->comment('The calendar day');
            $table->string('mood', 30)->comment('Mood/color applied to this day');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_day_moods');
    }
};
