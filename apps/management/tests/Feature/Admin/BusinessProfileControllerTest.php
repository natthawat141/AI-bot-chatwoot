<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_non_admin_cannot_view_the_profile(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/business-profile')->assertForbidden();
    }

    public function test_non_admin_cannot_update_the_profile(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->put('/admin/business-profile', ['business_name' => 'x', 'business_description' => 'y'])
            ->assertForbidden();
    }

    public function test_admin_can_view_the_profile_with_defaults(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/business-profile')
            ->assertOk();

        $this->assertDatabaseCount('business_profile', 1);
    }

    public function test_admin_can_update_the_profile(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/business-profile', [
                'business_name' => 'Aion Property',
                'business_description' => 'ให้บริการซื้อขายและเช่าอสังหาริมทรัพย์',
                'services_offered' => 'ขาย, เช่า, ฝากขาย',
                'service_areas' => 'กรุงเทพ, นนทบุรี',
                'business_hours' => '09:00-18:00',
                'contact_channels' => 'LINE, โทรศัพท์',
                'conversation_tone' => 'เป็นกันเอง, มืออาชีพ',
                'always_escalate_topics' => 'ข้อพิพาททางกฎหมาย, การเจรจาราคาพิเศษ',
            ])
            ->assertRedirect('/admin/business-profile');

        $this->assertDatabaseHas('business_profile', [
            'id' => 1,
            'business_name' => 'Aion Property',
            'business_description' => 'ให้บริการซื้อขายและเช่าอสังหาริมทรัพย์',
        ]);
    }

    public function test_business_name_and_description_are_required(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/business-profile', ['business_name' => '', 'business_description' => ''])
            ->assertSessionHasErrors(['business_name', 'business_description']);
    }
}
