<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('important_dates', function (Blueprint $table): void {
            $table->boolean('is_done')->default(false)->after('recurs_annually')->comment('Whether this date has been acknowledged/completed');
        });
    }

    public function down(): void
    {
        Schema::table('important_dates', function (Blueprint $table): void {
            $table->dropColumn('is_done');
        });
    }
};
