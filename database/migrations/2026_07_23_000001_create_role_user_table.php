<?php
// File: database/migrations/2026_07_23_000001_create_role_user_table.php
// Purpose: Module 11 (FSD 15.1) — "Role: Multi-select, mandatory, at least one
//          role." Additive pivot alongside the existing singular `users.role_id`/
//          `User::role()`, which is kept unchanged as the "primary role" for
//          backward compatibility with every existing permission check. This
//          migration also backfills one role_user row per existing user from
//          their current role_id, so every pre-existing user ends up with
//          exactly the one role they already had — zero behavior change until
//          a user is explicitly given a second role.
// Author: System
// Date: 2026-07-23

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('role_id');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->unique(['user_id', 'role_id']);
            });
        }

        // Backfill: one row per existing user from their current role_id.
        // insertOrIgnore + the unique index above make this idempotent.
        $now  = now();
        $rows = DB::table('users')->whereNotNull('role_id')->select('id', 'role_id')->get();

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('role_user')->insertOrIgnore(
                $chunk->map(fn ($u) => [
                    'user_id' => $u->id, 'role_id' => $u->role_id,
                    'created_at' => $now, 'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
