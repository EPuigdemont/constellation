<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Owning user');
            $table->string('title')->comment('Reminder title');
            $table->text('body')->nullable()->comment('Optional reminder details');
            $table->dateTime('remind_at')->comment('When the reminder is due');
            $table->string('mood')->nullable()->comment('Mood theme for display');
            $table->boolean('is_completed')->default(false)->comment('Whether the reminder has been dismissed/completed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
