<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\HomepageAdvertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminAdvertisementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['super_admin.emails' => ['admin@example.com'], 'filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_super_admin_routes_are_server_side_protected(): void
    {
        $this->get('/super-admin')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['email' => 'normal@example.com']))->get('/super-admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['email' => 'ADMIN@example.com']))->get('/super-admin')->assertOk();
        config(['super_admin.emails' => []]);
        $this->actingAs(User::factory()->create(['email' => 'admin@example.com']))->get('/super-admin')->assertForbidden();
    }

    public function test_admin_can_create_update_toggle_and_soft_delete_an_ad(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->actingAs($admin)->post(route('super-admin.ads.store'), [
            'internal_name' => 'Launch', 'advertiser_name' => 'Acme', 'image' => UploadedFile::fake()->image('ad.jpg', 1200, 1500),
            'destination_url' => 'https://example.com/offer', 'alt_text' => 'Acme launch offer', 'cta_label' => 'Learn more', 'placement' => 1, 'is_active' => 1,
        ])->assertRedirect(route('super-admin.ads.index'));
        $ad = HomepageAdvertisement::firstOrFail();
        Storage::disk('public')->assertExists($ad->image_path);
        $this->assertSame($admin->id, $ad->created_by_user_id);

        $this->actingAs($admin)->patch(route('super-admin.ads.toggle', $ad))->assertRedirect();
        $this->assertFalse($ad->fresh()->is_active);
        $this->actingAs($admin)->delete(route('super-admin.ads.destroy', $ad))->assertRedirect();
        $this->assertSoftDeleted($ad);
        Storage::disk('public')->assertExists($ad->image_path);
    }

    public function test_validation_rejects_unsafe_urls_and_invalid_images(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->actingAs($admin)->post(route('super-admin.ads.store'), [
            'internal_name' => 'Bad', 'image' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
            'destination_url' => 'javascript:alert(1)', 'alt_text' => 'Bad', 'placement' => 1, 'is_active' => 1,
        ])->assertInvalid(['image', 'destination_url']);
    }

    public function test_admin_preview_matches_the_public_sponsored_pill(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->get(route('super-admin.ads.create'))
            ->assertOk()
            ->assertSee('absolute bottom-4 right-4 rounded-full bg-indigo-600', false)
            ->assertSee('Sponsored');
    }

    public function test_active_ads_are_composed_by_visual_position_and_clicks_are_tracked(): void
    {
        Creator::factory()->create(['display_name' => 'First Creator']);
        Creator::factory()->create(['display_name' => 'Second Creator']);
        $ad = HomepageAdvertisement::create(['internal_name' => 'Missioned Souls campaign', 'advertiser_name' => 'Missioned Souls', 'image_path' => 'advertisements/homepage/ad.jpg', 'destination_url' => 'https://example.com', 'alt_text' => 'Music and stories created with purpose for a growing community.', 'cta_label' => 'Listen now', 'placement' => 1, 'is_active' => true]);
        Storage::disk('public')->put($ad->image_path, 'image');

        $response = $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Sponsored', 'First Creator', 'Second Creator', 'Add Creator Account'])
            ->assertSee('rel="noopener noreferrer sponsored"', false)
            ->assertSee('data-home-grid-tile', false)
            ->assertSee('data-home-compact-card', false)
            ->assertSee('data-sponsored-card', false)
            ->assertSee('min-h-[19rem] md:h-[19rem] 2xl:h-72', false)
            ->assertSee('data-home-card-banner', false)
            ->assertSee('data-home-card-name', false)
            ->assertSee('Missioned Souls')
            ->assertSee('data-home-card-bio', false)
            ->assertSee('Music and stories created with purpose for a growing community.')
            ->assertSee('data-home-card-footer', false)
            ->assertSee('Listen now')
            ->assertSee('absolute bottom-4 right-4 rounded-full bg-indigo-600', false)
            ->assertDontSee('absolute left-4 top-4', false);
        $this->assertSame(3, substr_count($response->getContent(), 'data-home-compact-card'));
        $this->get(route('ads.click', $ad))->assertRedirect('https://example.com');
        $this->assertSame(1, $ad->fresh()->click_count);
    }

    public function test_matching_sponsored_campaign_decorates_the_standard_creator_card(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Missioned Souls',
            'bio' => 'Purposeful music and stories for a growing community.',
            'avatar_path' => null,
        ]);
        $ad = HomepageAdvertisement::create([
            'internal_name' => 'Missioned Souls campaign',
            'advertiser_name' => 'Missioned Souls',
            'image_path' => 'advertisements/homepage/missioned-souls.jpg',
            'destination_url' => 'https://example.com/missioned-souls',
            'alt_text' => 'Tour Announcement with Ticket Links',
            'cta_label' => 'Listen now',
            'placement' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put($ad->image_path, 'image');

        $response = $this->get('/')->assertOk();

        $response
            ->assertSee('data-sponsored-card', false)
            ->assertSee('data-home-card-identity', false)
            ->assertSee('data-home-card-avatar', false)
            ->assertSee('Missioned Souls')
            ->assertSee('Purposeful music and stories for a growing community.')
            ->assertDontSee('Tour Announcement with Ticket Links')
            ->assertSee('followers')
            ->assertSee('requests')
            ->assertSee('published')
            ->assertSee('rounded-full', false);
        $this->assertSame(1, substr_count($response->getContent(), 'Missioned Souls avatar'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-home-compact-card'));
    }

    public function test_scheduling_and_disabled_state_exclude_ads(): void
    {
        foreach ([
            ['internal_name' => 'Future', 'starts_at' => now()->addDay(), 'is_active' => true],
            ['internal_name' => 'Expired', 'ends_at' => now()->subDay(), 'is_active' => true],
            ['internal_name' => 'Disabled', 'is_active' => false],
        ] as $attributes) {
            HomepageAdvertisement::create($attributes + ['image_path' => 'ad.jpg', 'destination_url' => 'https://example.com', 'alt_text' => 'Ad', 'placement' => 1]);
        }

        $this->get('/')->assertDontSee('Sponsored');
    }
}
