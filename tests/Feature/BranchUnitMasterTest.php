<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchUnitMasterTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin', 'is_active' => true]);

        return User::create([
            'name' => 'Super Admin', 'email' => 'super@example.com', 'username' => 'superadmin',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pune Branch',
            'code' => 'PUN01',
            'address' => '123 MG Road',
            'state' => 'Maharashtra',
            'district' => 'Pune',
            'city' => 'Pune',
            'pincode' => '411001',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_branch_start_date_is_optional(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload());

        $response->assertRedirect(route('masters.branches.index'));
        $this->assertDatabaseHas('branches', ['code' => 'PUN01', 'start_date' => null]);
    }

    public function test_closure_date_cannot_be_before_start_date(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload([
            'start_date' => '2026-06-01',
            'closure_date' => '2026-05-01',
        ]));

        $response->assertSessionHasErrors('closure_date');
    }

    public function test_closure_date_on_or_after_start_date_is_accepted(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload([
            'start_date' => '2026-06-01',
            'closure_date' => '2026-06-01',
        ]));

        $response->assertSessionDoesntHaveErrors('closure_date');
        $branch = Branch::where('code', 'PUN01')->firstOrFail();
        $this->assertEquals('2026-06-01', $branch->closure_date->toDateString());
    }

    public function test_branch_name_must_be_unique_at_database_level(): void
    {
        $admin = $this->superAdmin();

        Branch::create([
            'name' => 'Pune Branch', 'code' => 'EXIST1', 'address' => 'x',
            'state' => 'Maharashtra', 'district' => 'Pune', 'city' => 'Pune', 'pincode' => '411001',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload(['code' => 'NEWCODE']));

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('branches', ['code' => 'NEWCODE']);
    }

    public function test_new_fields_are_saved(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload([
            'unit_type' => 'Factory',
            'pf_establishment_number' => 'PF123456',
            'esi_employer_code' => 'ESI98765',
            'weekly_off_days' => ['sunday', 'saturday'],
        ]));

        $response->assertRedirect(route('masters.branches.index'));
        $branch = Branch::where('code', 'PUN01')->firstOrFail();
        $this->assertEquals('Factory', $branch->unit_type);
        $this->assertEquals('PF123456', $branch->pf_establishment_number);
        $this->assertEquals('ESI98765', $branch->esi_employer_code);
        $this->assertEqualsCanonicalizing(['sunday', 'saturday'], $branch->weekly_off_days);
    }

    public function test_invalid_phone_format_is_rejected(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/branches', $this->validPayload([
            'phone' => 'not-a-phone-###',
        ]));

        $response->assertSessionHasErrors('phone');
    }

    public function test_applicable_holidays_includes_global_and_own_branch(): void
    {
        $branchA = Branch::create([
            'name' => 'Branch A', 'code' => 'BRA', 'address' => 'x',
            'state' => 'Maharashtra', 'district' => 'Pune', 'city' => 'Pune', 'pincode' => '411001',
            'is_active' => true,
        ]);
        $branchB = Branch::create([
            'name' => 'Branch B', 'code' => 'BRB', 'address' => 'x',
            'state' => 'Maharashtra', 'district' => 'Pune', 'city' => 'Pune', 'pincode' => '411001',
            'is_active' => true,
        ]);

        \App\Models\Holiday::forceCreate(['name' => 'Global Holiday', 'date' => '2026-01-26', 'year' => 2026, 'type' => 'national', 'branch_id' => null, 'is_active' => true]);
        \App\Models\Holiday::forceCreate(['name' => 'Branch A Holiday', 'date' => '2026-03-15', 'year' => 2026, 'type' => 'regional', 'branch_id' => $branchA->id, 'is_active' => true]);
        \App\Models\Holiday::forceCreate(['name' => 'Branch B Holiday', 'date' => '2026-04-20', 'year' => 2026, 'type' => 'regional', 'branch_id' => $branchB->id, 'is_active' => true]);

        $applicable = $branchA->applicableHolidays()->pluck('name')->all();

        $this->assertContains('Global Holiday', $applicable);
        $this->assertContains('Branch A Holiday', $applicable);
        $this->assertNotContains('Branch B Holiday', $applicable);
    }
}
