<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic append-only audit trail shared by the master-data modules that manage
 * several small entities (uom, org units/departments, facility
 * locations/buildings/rooms). Instead of one *_history table per entity, every
 * row here carries its own `module` + denormalized `record_label`, so the
 * Settings › Audit union can read it directly without joining a parent table.
 *
 * user_name = the ACTOR (which staff), created_at = when. Append-only: no
 * updated_at, rows are never modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_history', function (Blueprint $table) {
            $table->id();
            $table->string('module', 32);              // uom / unit / department / location / building / room
            $table->unsignedBigInteger('record_id');   // parent PK (no FK — points at several tables)
            $table->string('record_label', 256)->nullable();   // denormalized name/number for display
            $table->string('action', 32);              // create / update / activate / deactivate / delete / restore
            $table->string('status', 32)->nullable();  // resulting status (active/inactive)
            $table->unsignedBigInteger('user_id')->nullable();   // actor id
            $table->string('user_name', 256)->nullable();        // actor name
            $table->string('role', 64)->nullable();              // actor role
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['module', 'record_id']);
            $table->index(['module', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_history');
    }
};
