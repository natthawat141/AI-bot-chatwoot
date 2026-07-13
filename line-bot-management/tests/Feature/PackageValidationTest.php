<?php

namespace Tests\Feature;

use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_name_th_is_required(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/packages/create')
            ->post('/admin/packages', ['name_th' => '', 'currency' => 'THB'])
            ->assertSessionHasErrors('name_th');
    }

    public function test_effective_until_must_not_precede_effective_from(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/packages/create')
            ->post('/admin/packages', [
                'name_th' => 'แพ็กเกจทดสอบ',
                'effective_from' => '2026-07-01',
                'effective_until' => '2026-06-01',
            ])
            ->assertSessionHasErrors('effective_until');
    }

    public function test_it_creates_a_package_with_valid_data(): void
    {
        $category = PackageCategory::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/packages', [
                'name_th' => 'แพ็กเกจเลเซอร์',
                'category_id' => $category->id,
                'price' => 1500,
                'currency' => 'THB',
                'is_active' => true,
                'is_published' => true,
            ])
            ->assertRedirect('/admin/packages')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('packages', ['name_th' => 'แพ็กเกจเลเซอร์', 'price' => 1500]);
    }
}
