<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize calendar_day_moods.date from "Y-m-d H:i:s" to "Y-m-d" so that
     * Eloquent's updateOrCreate WHERE clause (which sends a plain date string)
     * can match existing rows via a simple string comparison in SQLite.
     */
    public function up(): void
    {
        // SQLite's date() function strips the time component, returning "Y-m-d".
        // The column name must be unquoted inside date() so SQLite treats it as
        // a column reference, not a string literal.
        DB::statement('UPDATE calendar_day_moods SET date = date(date)');
    }

    public function down(): void
    {
        // No rollback needed — the data is semantically identical; only the format changes.
    }
};
