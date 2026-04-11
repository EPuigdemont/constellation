<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Guest account expiration timestamp - null if not a guest or converted to full account
            $table->timestamp('guest_expires_at')->nullable()->after('tier')->comment('When guest account expires; NULL if not a guest or converted to full account');

            // Track when guest account was created
            $table->timestamp('guest_created_at')->nullable()->after('guest_expires_at')->comment('When the guest account was created');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['guest_expires_at', 'guest_created_at']);
        });
    }
};
