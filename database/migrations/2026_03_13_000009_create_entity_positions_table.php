<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_positions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('Primary key UUID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('User who positioned this entity');
            $table->uuid('entity_id')->comment('UUID of the positioned entity');
            $table->string('entity_type')->comment('Morph type alias of the positioned entity');
            $table->float('x')->comment('Horizontal position on desktop canvas');
            $table->float('y')->comment('Vertical position on desktop canvas');
            $table->integer('z_index')->default(0)->comment('Stacking order on desktop canvas');
            $table->timestamps();

            $table->unique(['user_id', 'entity_id', 'entity_type'], 'entity_pos_user_entity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_positions');
    }
};
