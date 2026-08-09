<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_open_the_bilingual_guide(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/guide')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Guide'));
    }
}
