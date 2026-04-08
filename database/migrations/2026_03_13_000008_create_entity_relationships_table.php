<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->uuid('entity_a_id')->comment('UUID of the first entity');
            $table->string('entity_a_type')->comment('Morph type alias of the first entity');
            $table->uuid('entity_b_id')->comment('UUID of the second entity');
            $table->string('entity_b_type')->comment('Morph type alias of the second entity');
            $table->string('relationship_type')->comment('Type of relationship between entities');
            $table->string('direction')->nullable()->comment('Direction of the relationship');
            $table->timestamps();

            $table->index(['entity_a_id', 'entity_a_type'], 'entity_rel_a_morph_index');
            $table->index(['entity_b_id', 'entity_b_type'], 'entity_rel_b_morph_index');
            $table->unique(['e_a_id', 'e_a_type', 'e_b_id', 'e_b_type', 'r_type'], 'entity_rel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_relationships');
    }
};
