<?php
// File: database/migrations/2026_07_11_000004_drop_duplicate_branch_admin_role_tables.php
// Purpose: Consolidation — remove the duplicate Role/Permission schema built
//          specifically for Branch Administration now that the same fields/
//          flags live on the existing roles/role_permissions tables (see the
//          two preceding migrations). These tables are new-in-this-feature and
//          unused by anything else, safe to drop without touching production
//          data on the existing roles/permissions/users/branches tables.
// Author: System
// Date: 2026-07-11

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('branch_admin_user_roles');
        Schema::dropIfExists('branch_admin_role_permissions');
        Schema::dropIfExists('branch_admin_roles');
    }

    public function down(): void
    {
        // Structural rollback only — recreating these tables here would just
        // reintroduce the duplicate schema this migration exists to remove.
        // Restore from the original migrations if this ever needs reverting.
    }
};
