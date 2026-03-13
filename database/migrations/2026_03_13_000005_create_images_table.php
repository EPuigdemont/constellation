<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Owning user');
            $table->string('path')->comment('Storage path relative to disk root');
            $table->string('disk')->default('private')->comment('Filesystem disk name');
            $table->string('alt')->nullable()->comment('Alt text for accessibility');
            $table->boolean('is_public')->default(false)->comment('Whether visible to other users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
