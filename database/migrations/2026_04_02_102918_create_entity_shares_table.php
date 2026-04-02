<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entity_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->comment('User who owns the entity');
            $table->unsignedBigInteger('friend_id')->comment('User with whom the entity is shared');
            $table->string('entity_id')->comment('ID of the shared entity');
            $table->string('entity_type')->comment('Type of the shared entity (diary_entry, note, postit, image, etc.)');
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('friend_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['owner_id', 'friend_id', 'entity_id', 'entity_type']);
            $table->index(['entity_id', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_shares');
    }
};
