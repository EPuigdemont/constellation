<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            // Drop manually-declared FKs before altering column types.
            // Laravel will recreate the table (SQLite) or use ALTER COLUMN.
            $table->dropForeign(['user_id']);
            $table->dropForeign(['friend_id']);
        });

        Schema::table('friendships', function (Blueprint $table) {
            // Fix type mismatch: users.id is unsignedBigInteger ($table->id()),
            // so FK columns must match instead of using UUID (varchar).
            $table->unsignedBigInteger('user_id')
                ->comment('FK to users.id — owner of the friendship request')
                ->change();
            $table->unsignedBigInteger('friend_id')
                ->comment('FK to users.id — recipient of the friendship request')
                ->change();
        });

        Schema::table('friendships', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('friend_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['friend_id']);
        });

        Schema::table('friendships', function (Blueprint $table) {
            $table->uuid('user_id')->change();
            $table->uuid('friend_id')->change();
        });

        Schema::table('friendships', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('friend_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
