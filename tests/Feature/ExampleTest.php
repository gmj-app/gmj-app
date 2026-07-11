<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GuideAccoladeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $response = $this->get(route('about'))
            ->assertOk()
            ->assertSee('How It Works | Guide My Journey', false)
            ->assertSee('Turn')
            ->assertSee('fan requests')
            ->assertSee('content roadmap.')
            ->assertSee('Explore creator boards')
            ->assertSee('Open My Hub')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('data-workflow-stages', false)
            ->assertSeeInOrder(['A fan shares the spark', 'The community adds its signal', 'The strongest ideas rise', 'The creator chooses the next move'])
            ->assertSee('One platform. Two powerful roles.')
            ->assertSee('Help shape what gets made next')
            ->assertSee('Replace noise with a clear creative signal')
            ->assertSee('data-lifecycle', false)
            ->assertSeeInOrder(['Suggested', 'Community backed', 'Approved', 'Scheduled', 'Published 2 days ago'])
            ->assertSee('Votes carry weight')
            ->assertSee('People, not anonymous numbers')
            ->assertSee('No algorithm chooses for you')
            ->assertSee('Every journey builds history')
            ->assertSee('Your community already has ideas. Give them somewhere better to go.')
            ->assertSee('href="'.route('faq').'"', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('accolade-founding', false)
            ->assertSee('accolade-og', false)
            ->assertSee('guide-accolade__number', false)
            ->assertDontSee('href="#"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $this->assertSame(4, substr_count($response->getContent(), '<li class="relative border-l'));
    }

    public function test_how_it_works_page_uses_authenticated_cta_and_nav_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('about'))
            ->assertOk()
            ->assertSee('How it Works')
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('My Hub')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('href="'.route('creators.create').'"', false)
            ->assertDontSee('href="'.route('register').'"', false);
    }

    public function test_how_it_works_demo_accolades_use_the_shared_avatar_component(): void
    {
        $component = file_get_contents(resource_path('views/components/how-it-works/demo-avatars.blade.php'));
        $pageTemplates = collect(glob(resource_path('views/pages/how-it-works/*.blade.php')))
            ->map(fn (string $path): string => file_get_contents($path))
            ->implode("\n");

        $this->assertStringContainsString('<x-guide-avatar', $component);
        $this->assertStringNotContainsString('guide_number <=', $pageTemplates);
        $this->assertStringNotContainsString('guide_number >=', $pageTemplates);
    }

    public function test_how_it_works_avatar_previews_reuse_the_cached_tier_query(): void
    {
        app(GuideAccoladeResolver::class)->forgetCache();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('about'))->assertOk();

        $tierQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => str_contains($query['query'], 'guide_accolades')
                && str_contains($query['query'], 'rule_type'),
        );

        $this->assertCount(1, $tierQueries);
    }
}
