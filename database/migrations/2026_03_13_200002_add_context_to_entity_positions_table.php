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
            $table->string('context')->default('desktop')->after('entity_type')->comment('View context: desktop or vision_board');
        });

        // Replace old unique index with one that includes context
        Schema::table('entity_positions', function (Blueprint $table): void {
            $table->dropUnique('entity_pos_user_entity_unique');
            $table->unique(['user_id', 'entity_id', 'entity_type', 'context'], 'entity_pos_user_entity_ctx_unique');
        });
    }

    public function down(): void
    {
        Schema::table('entity_positions', function (Blueprint $table): void {
            $table->dropUnique('entity_pos_user_entity_ctx_unique');
            $table->unique(['user_id', 'entity_id', 'entity_type'], 'entity_pos_user_entity_unique');
            $table->dropColumn('context');
        });
    }
};
