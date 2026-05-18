<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('allow_shared_reminders')->default(true)->after('diary_display_mode')
                ->comment('Whether other users can share reminders with this user');
            $table->boolean('notify_shared_reminders')->default(true)->after('allow_shared_reminders')
                ->comment('Whether shared reminders trigger toast notifications');
            $table->unsignedSmallInteger('reminder_approaching_minutes')->default(30)->after('notify_shared_reminders')
                ->comment('Minutes before remind_at to fire an approaching-reminder notification');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['allow_shared_reminders', 'notify_shared_reminders', 'reminder_approaching_minutes']);
        });
    }
};
