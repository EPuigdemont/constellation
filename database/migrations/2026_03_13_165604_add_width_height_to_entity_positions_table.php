<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entity_positions', function (Blueprint $table): void {
            $table->float('width')->nullable()->after('z_index')->comment('Custom card width on desktop canvas');
            $table->float('height')->nullable()->after('width')->comment('Custom card height on desktop canvas');
        });
    }

    public function down(): void
    {
        Schema::table('entity_positions', function (Blueprint $table): void {
            $table->dropColumn(['width', 'height']);
        });
    }
};
