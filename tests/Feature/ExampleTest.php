<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_public_results_home(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->has('competitions'));
    }

    public function test_authenticated_users_can_view_the_public_results_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Home'));
    }
}
