<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diary_entries', function (Blueprint $table): void {
            $table->text('title')->change();
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->text('title')->nullable()->change();
        });

        Schema::table('reminders', function (Blueprint $table): void {
            $table->text('title')->change();
        });

        Schema::table('images', function (Blueprint $table): void {
            $table->text('alt')->nullable()->change();
            $table->text('title')->nullable()->change();
        });

        Schema::table('important_dates', function (Blueprint $table): void {
            $table->text('label')->change();
        });
    }

    public function down(): void
    {
        Schema::table('diary_entries', function (Blueprint $table): void {
            $table->string('title')->change();
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->string('title')->nullable()->change();
        });

        Schema::table('reminders', function (Blueprint $table): void {
            $table->string('title')->change();
        });

        Schema::table('images', function (Blueprint $table): void {
            $table->string('alt')->nullable()->change();
            $table->string('title')->nullable()->change();
        });

        Schema::table('important_dates', function (Blueprint $table): void {
            $table->string('label')->change();
        });
    }
};
