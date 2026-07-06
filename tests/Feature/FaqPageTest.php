<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_accurately_explains_the_current_free_mvp(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('Basics')
            ->assertSee('Guide My Journey is a community suggestion board for creators.')
            ->assertSee('Reaction channels are one use case, but the platform is built for creators broadly.')
            ->assertSee('For Fans and Guides')
            ->assertSee('A Guide is a fan who helps shape a creator')
            ->assertSee('Resources are the limits that help keep participation meaningful.')
            ->assertSee('If you unfavorite a creator, your active votes')
            ->assertSee('Suggestions and Voting')
            ->assertSee('Yes. If you suggest something you care about, you can also use one of your votes on it.')
            ->assertSee('its votes stop counting against active limits.')
            ->assertSee('Already Seen means the creator has already seen')
            ->assertSee('For Creators')
            ->assertSee('Hold for review keeps new suggestions private until approved.')
            ->assertSee('creator-specific tags')
            ->assertSee('Creator-level blocking is planned.')
            ->assertSee('Votes provide signal, not obligation.')
            ->assertSee('Platform and YouTube')
            ->assertSee('not affiliated with, endorsed by, or operated by YouTube.')
            ->assertSee('Fans can suggest topics, questions, ideas, or links, as well as YouTube videos.')
            ->assertSee('The core product is free first.')
            ->assertDontSee('claim or create a page')
            ->assertDontSee('picks')
            ->assertDontSee('fans decide what creators make');
    }

    public function test_faq_remains_available_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('faq'))
            ->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('My Hub')
            ->assertSee('What is a Guide?');
    }
}
