<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entity_positions', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('height')
                ->comment('Whether the card is hidden on its canvas context');
        });
    }

    public function down(): void
    {
        Schema::table('entity_positions', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
