<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicProfileRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_route_resolves_creators_and_guides_with_literal_at_sign(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $guide = User::factory()->create([
            'public_handle' => 'guide-handle',
            'public_display_name' => 'Public Guide',
            'public_profile_enabled' => true,
        ]);

        $canonicalUrl = route('creator.queue', $creator);

        $this->get('/@jfragment')->assertOk()
            ->assertSee($creator->display_name)
            ->assertSee('<link rel="canonical" href="'.$canonicalUrl.'">', false)
            ->assertSee('<meta property="og:url" content="'.$canonicalUrl.'">', false);
        $this->get('/%40jfragment')->assertOk()->assertSee($creator->display_name);
        $this->get('/@guide-handle')->assertOk()->assertSee($guide->public_display_name);
        $this->assertStringEndsWith('/@jfragment', $canonicalUrl);
        $this->assertStringEndsWith('/@guide-handle', $guide->publicGuideProfileUrl());
    }

    public function test_legacy_profiles_redirect_permanently_and_preserve_query_parameters(): void
    {
        Creator::factory()->create(['slug' => 'jfragment']);
        User::factory()->create([
            'public_handle' => 'legacy-guide',
            'public_display_name' => 'Legacy Guide',
            'public_profile_enabled' => true,
        ]);

        $this->get('/jfragment?q=music&page=2')
            ->assertRedirect('/@jfragment?page=2&q=music')
            ->assertStatus(301);
        $this->get('/legacy-guide')->assertRedirect('/@legacy-guide')->assertStatus(301);
    }

    public function test_unknown_invalid_hidden_and_soft_deleted_profiles_are_not_public(): void
    {
        Creator::factory()->create(['slug' => 'hidden-creator', 'status' => 'disabled']);
        $deleted = Creator::factory()->create(['slug' => 'deleted-creator']);
        $deleted->delete();
        User::factory()->create([
            'public_handle' => 'hidden-guide',
            'public_display_name' => 'Hidden Guide',
            'public_profile_enabled' => false,
        ]);

        $this->get('/@missing-profile')->assertNotFound();
        $this->get('/@bad.handle')->assertNotFound();
        $this->get('/@hidden-creator')->assertNotFound();
        $this->get('/@deleted-creator')->assertNotFound();
        $this->get('/@hidden-guide')->assertNotFound();
    }

    public function test_creator_precedence_and_case_canonicalization_are_deterministic(): void
    {
        Creator::factory()->create(['slug' => 'shared-handle']);
        User::factory()->create([
            'public_handle' => 'shared-handle',
            'public_display_name' => 'Shared Guide',
            'public_profile_enabled' => true,
        ]);

        $this->get('/@shared-handle')->assertOk()->assertDontSee('Shared Guide');
        $this->get('/@Shared-Handle')->assertRedirect('/@shared-handle')->assertStatus(301);
    }

    public function test_application_routes_remain_ahead_of_the_legacy_fallback(): void
    {
        $this->get('/faq')->assertOk();
        $this->get('/login')->assertOk();
        $this->assertSame('faq', Route::getRoutes()->match(request()->create('/faq'))->getName());
    }
}
