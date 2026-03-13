<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Owning user');
            $table->string('title')->nullable()->comment('Optional note title');
            $table->longText('body')->comment('Rich text body content');
            $table->string('mood')->nullable()->comment('Mood enum value, maps to CSS class');
            $table->string('color_override')->nullable()->comment('Custom color override for theming');
            $table->boolean('is_public')->default(false)->comment('Whether visible to other users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
