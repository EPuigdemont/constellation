<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->string('mood')->nullable()->after('alt')->comment('Mood enum value, maps to CSS mood class');
            $table->string('color_override')->nullable()->after('mood')->comment('Custom hex color override for theming');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->dropColumn(['mood', 'color_override']);
        });
    }
};
