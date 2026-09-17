<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emergency-access resilience: track when a user last set a usable LOCAL
 * password. Domain (AD) accounts start with a random unusable hash, so this
 * stays null for them until an admin provisions a temp password or the person
 * sets one via a link. It drives the "needs local password" count and, later,
 * fallback eligibility (only accounts with a real local password may fall back
 * to local auth when the DC is unreachable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('local_password_set_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('local_password_set_at');
        });
    }
};
