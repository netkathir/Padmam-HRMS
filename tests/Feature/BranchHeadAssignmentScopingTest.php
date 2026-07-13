<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchHeadAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchHeadAssignmentScopingTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(array $overrides = []): Branch
    {
        static $seq = 0;
        $seq++;

        return Branch::create(array_merge([
            'name' => "Branch $seq",
            'code' => "BR$seq",
            'is_active' => true,
        ], $overrides));
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin', 'is_active' => true]);

        return User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    private function candidateUser(string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'employee'], ['display_name' => 'Employee', 'is_active' => true]);

        static $seq = 0;
        $seq++;

        return User::create([
            'name' => "Candidate $seq",
            'email' => $email,
            'username' => "candidate$seq",
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    public function test_index_only_shows_assignments_for_the_currently_selected_branch(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->superAdmin();
        $userA = $this->candidateUser('a@example.com');
        $userB = $this->candidateUser('b@example.com');

        BranchHeadAssignment::assign([
            'branch_id' => $branchA->id, 'user_id' => $userA->id, 'effective_from' => now()->toDateString(),
        ], $admin->id);
        BranchHeadAssignment::assign([
            'branch_id' => $branchB->id, 'user_id' => $userB->id, 'effective_from' => now()->toDateString(),
        ], $admin->id);

        // Auto-resolves current branch to branchA (first by id).
        $response = $this->actingAs($admin)->get('http://localhost/branch-admin/head-assignments');
        $response->assertSee($userA->name);
        $response->assertDontSee($userB->name);
    }

    public function test_switching_branch_immediately_changes_the_list(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->superAdmin();
        $userA = $this->candidateUser('a@example.com');
        $userB = $this->candidateUser('b@example.com');

        BranchHeadAssignment::assign([
            'branch_id' => $branchA->id, 'user_id' => $userA->id, 'effective_from' => now()->toDateString(),
        ], $admin->id);
        BranchHeadAssignment::assign([
            'branch_id' => $branchB->id, 'user_id' => $userB->id, 'effective_from' => now()->toDateString(),
        ], $admin->id);

        $this->actingAs($admin)->get('http://localhost/branch-admin/head-assignments')
            ->assertSee($userA->name)->assertDontSee($userB->name);

        // Switch to branch B via the Branch Switcher — the list must
        // immediately reflect branch B's assignment instead.
        $this->actingAs($admin)->post('http://localhost/branch-admin/branch-switcher/switch', ['branch_id' => $branchB->id]);

        $this->actingAs($admin)->get('http://localhost/branch-admin/head-assignments')
            ->assertSee($userB->name)->assertDontSee($userA->name);
    }

    public function test_store_forces_currently_selected_branch_regardless_of_submitted_branch_id(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $candidate = $this->candidateUser('candidate@example.com');

        $admin = $this->superAdmin();
        // Auto-resolves current branch to branchA (first by id).
        $this->actingAs($admin)->get('http://localhost/branch-admin/head-assignments');

        $response = $this->actingAs($admin)->post('http://localhost/branch-admin/head-assignments', [
            'branch_id' => $branchB->id, // attacker/careless client attempts branch B
            'user_id' => $candidate->id,
            'effective_from' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('branch-admin.head-assignments.index'));
        $this->assertDatabaseHas('branch_head_assignments', [
            'user_id' => $candidate->id,
            'branch_id' => $branchA->id,
        ]);
        $this->assertDatabaseMissing('branch_head_assignments', [
            'user_id' => $candidate->id,
            'branch_id' => $branchB->id,
        ]);
    }

    public function test_cross_branch_edit_update_deactivate_destroy_are_blocked(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->superAdmin();
        $userB = $this->candidateUser('b@example.com');

        $assignment = BranchHeadAssignment::assign([
            'branch_id' => $branchB->id, 'user_id' => $userB->id, 'effective_from' => now()->toDateString(),
        ], $admin->id);

        // Auto-resolves current branch to branchA — branchB's assignment is
        // now out of scope for every action below.
        $this->actingAs($admin)->get('http://localhost/branch-admin/head-assignments');

        $this->actingAs($admin)->get("http://localhost/branch-admin/head-assignments/{$assignment->id}/edit")->assertStatus(403);
        $this->actingAs($admin)->put("http://localhost/branch-admin/head-assignments/{$assignment->id}", [
            'effective_from' => now()->toDateString(), 'status' => 'inactive',
        ])->assertStatus(403);
        $this->actingAs($admin)->post("http://localhost/branch-admin/head-assignments/{$assignment->id}/deactivate")->assertStatus(403);
        $this->actingAs($admin)->delete("http://localhost/branch-admin/head-assignments/{$assignment->id}")->assertStatus(403);
    }
}
