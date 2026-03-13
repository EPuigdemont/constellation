<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table): void {
            $table->foreignUuid('tag_id')->constrained()->cascadeOnDelete()->comment('Reference to tags table');
            $table->uuid('taggable_id')->comment('UUID of the tagged entity');
            $table->string('taggable_type')->comment('Morph type alias of the tagged entity');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
            $table->index(['taggable_id', 'taggable_type'], 'taggables_taggable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
