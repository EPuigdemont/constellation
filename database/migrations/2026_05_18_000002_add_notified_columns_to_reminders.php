<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->timestamp('approaching_notified_at')->nullable()->after('is_completed')
                ->comment('Set when an approaching-deadline notification has been dispatched for this reminder');
            $table->timestamp('due_notified_at')->nullable()->after('approaching_notified_at')
                ->comment('Set when a due notification has been dispatched for this reminder');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->dropColumn(['approaching_notified_at', 'due_notified_at']);
        });
    }
};
