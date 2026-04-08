<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name')->comment('Unique login username, derived from name slug');
        });

        // Populate existing users with a username from their name
        foreach (User::all() as $user) {
            $base = Str::slug($user->name);
            $username = $base;
            $counter = 1;
            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base.'-'.$counter;
                $counter++;
            }
            $user->update(['username' => $username]);
        }

        // Now make it non-nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
