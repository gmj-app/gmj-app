<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_how_it_works_page_explains_the_product_and_uses_guest_cta(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('How It Works | Guide My Journey', false)
            ->assertSee('How it works')
            ->assertSee('Fans')
            ->assertSee('suggest')
            ->assertSee('Communities')
            ->assertSee('vote')
            ->assertSee('Creators')
            ->assertSee('decide')
            ->assertSee('Guide My Journey turns scattered comments, DMs, and requests into one organized board creators can actually use.')
            ->assertSee('data-journey-infographic', false)
            ->assertSee('Fans suggest')
            ->assertSee('Fans submit ideas, topics, videos, links, and questions.')
            ->assertSee('Communities vote')
            ->assertSee('The best ideas rise as the community focuses the signal.')
            ->assertSee('Creators decide')
            ->assertSee('Creators review the board and choose what to make next.')
            ->assertSee('Voting guides the journey, but creators always stay in control.')
            ->assertSeeInOrder(['01', 'Fans suggest', '02', 'Communities vote', '03', 'Creators decide'])
            ->assertSee("Start guiding a creator's journey", false)
            ->assertSee('Find a creator and add your signal to the board.')
            ->assertSee('Explore creators')
            ->assertSee('Create free account')
            ->assertSee(route('home'), false)
            ->assertSee(route('register'), false)
            ->assertDontSee('About Guide My Journey')
            ->assertDontSee('>About<', false)
            ->assertDontSee('One shared signal')
            ->assertDontSee('How the journey works')
            ->assertDontSee('From scattered comments to one organized creator journey.')
            ->assertDontSee('The creator stays in control')
            ->assertDontSee('Why it matters')
            ->assertSee('href="'.route('dashboard').'"', false);
    }

    public function test_how_it_works_page_uses_authenticated_cta_and_nav_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('about'))
            ->assertOk()
            ->assertSee('How it Works')
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('My Hub')
            ->assertSee(route('dashboard'), false)
            ->assertDontSee('Create free account');
    }
}
