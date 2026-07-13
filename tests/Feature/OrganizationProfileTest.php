<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationProfileTest extends TestCase
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
            'name' => 'Acme Manufacturing Pvt. Ltd.',
            'code' => 'ACME01',
            'address' => '123 Industrial Area',
            'state' => 'Maharashtra',
            'district' => 'Pune',
            'pincode' => '411001',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_company_settings_page_renders(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get('http://localhost/settings/company');
        $response->assertStatus(200);
    }

    public function test_can_save_organization_profile_with_all_required_fields(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload());

        $response->assertRedirect(route('settings.company'));
        $this->assertDatabaseHas('company_profile', [
            'id' => 1,
            'name' => 'Acme Manufacturing Pvt. Ltd.',
            'code' => 'ACME01',
            'district' => 'Pune',
            'is_active' => 1,
        ]);
    }

    public function test_organization_code_is_required(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload(['code' => '']));

        $response->assertSessionHasErrors('code');
    }

    public function test_invalid_pan_format_is_rejected(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload(['pan' => 'NOTAPAN']));

        $response->assertSessionHasErrors('pan');
    }

    public function test_valid_pan_format_is_accepted(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload(['pan' => 'ABCDE1234F']));

        $response->assertSessionDoesntHaveErrors('pan');
        $this->assertDatabaseHas('company_profile', ['pan' => 'ABCDE1234F']);
    }

    public function test_invalid_gstin_format_is_rejected(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload(['gstin' => 'INVALID']));

        $response->assertSessionHasErrors('gstin');
    }

    public function test_invalid_pincode_is_rejected(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/settings/company', $this->validPayload(['pincode' => '12345']));

        $response->assertSessionHasErrors('pincode');
    }
}
