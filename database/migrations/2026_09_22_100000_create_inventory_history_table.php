<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail for inventory-item actions (create / update /
 * activate / deactivate / delete / restore). Mirrors the per-module *_history
 * tables so it plugs into the Settings › Audit log union: record_id = the item,
 * user_name = the ACTOR (which staff), created_at = when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('inventory_items')->cascadeOnDelete();  // the item
            $table->string('action', 32);    // create / update / activate / deactivate / delete / restore
            $table->string('status', 32)->nullable();   // resulting item status
            $table->unsignedBigInteger('user_id')->nullable();   // actor id
            $table->string('user_name', 256)->nullable();        // actor name
            $table->string('role', 64)->nullable();              // actor role
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['record_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_history');
    }
};
