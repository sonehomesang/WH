<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow username-only accounts: staff without an email address sign in by
 * username, so email becomes optional. The unique index stays (MySQL permits
 * multiple NULLs), and username is enforced as the required identifier in the
 * Users form. Existing email accounts are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 256)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Fails if any null emails exist — fill them before rolling back.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 256)->nullable(false)->change();
        });
    }
};
