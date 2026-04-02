<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->string('name')->comment('Tag display name');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->comment('Owning user, null for system tags');
            $table->string('color')->nullable()->comment('Optional display color');
            $table->timestamps();

            $table->unique(['name', 'user_id'], 'tags_name_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
