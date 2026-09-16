<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Second language column for the wording catalogue. `target` stays the Lao
 * override (default behaviour); `target_en` holds the English translation used
 * when the UI language is switched to English. Empty target_en → the string
 * falls back to its Lao source, so English mode is never broken, just partial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->text('target_en')->nullable()->after('target');
        });
    }

    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropColumn('target_en');
        });
    }
};
