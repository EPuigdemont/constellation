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
            $table->unsignedInteger('image_width')->nullable()->after('title')->comment('Original uploaded image width in pixels for default canvas sizing');
            $table->unsignedInteger('image_height')->nullable()->after('image_width')->comment('Original uploaded image height in pixels for default canvas sizing');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table): void {
            $table->dropColumn(['image_width', 'image_height']);
        });
    }
};
