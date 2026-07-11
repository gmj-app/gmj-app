<?php

namespace Tests\Feature;

use App\Models\GuideAccolade;
use App\Models\User;
use App\Services\GuideAccoladeService;
use App\Services\GuideNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideAccoladeTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_guide_numbers_are_backfilled_by_created_at_then_id(): void
    {
        $users = User::withoutEvents(fn () => [
            User::factory()->create(['created_at' => '2026-01-02 00:00:00']),
            User::factory()->create(['created_at' => '2026-01-01 00:00:00']),
            User::factory()->create(['created_at' => '2026-01-01 00:00:00']),
        ]);

        app(GuideNumberService::class)->backfillMissingGuideNumbers();

        $this->assertSame(3, $users[0]->fresh()->guide_number);
        $this->assertSame(1, $users[1]->fresh()->guide_number);
        $this->assertSame(2, $users[2]->fresh()->guide_number);
    }

    public function test_assign_numbers_command_is_ordered_and_idempotent(): void
    {
        $users = User::withoutEvents(fn () => [
            User::factory()->create(['created_at' => '2026-01-03 00:00:00']),
            User::factory()->create(['created_at' => '2026-01-01 00:00:00']),
            User::factory()->create(['created_at' => '2026-01-02 00:00:00']),
        ]);

        $this->artisan('guides:assign-numbers')
            ->expectsOutput("Assigned Guide #1 to user ID {$users[1]->id}")
            ->expectsOutput("Assigned Guide #2 to user ID {$users[2]->id}")
            ->expectsOutput("Assigned Guide #3 to user ID {$users[0]->id}")
            ->expectsOutput('Assigned guide numbers to 3 users.')
            ->assertSuccessful();

        $assignedNumbers = collect($users)
            ->map(fn (User $user): int => $user->fresh()->guide_number)
            ->all();

        $this->assertSame([3, 1, 2], $assignedNumbers);
        $this->assertCount(3, array_unique($assignedNumbers));

        $this->artisan('guides:assign-numbers')
            ->expectsOutput('Assigned guide numbers to 0 users.')
            ->assertSuccessful();

        $this->assertSame([3, 1, 2], collect($users)
            ->map(fn (User $user): int => $user->fresh()->guide_number)
            ->all());
    }

    public function test_assign_numbers_command_does_not_overwrite_existing_guide_numbers(): void
    {
        $existing = User::factory()->create(['guide_number' => 10]);
        $missingUsers = User::withoutEvents(fn () => [
            User::factory()->create(['created_at' => '2026-01-01 00:00:00']),
            User::factory()->create(['created_at' => '2026-01-02 00:00:00']),
        ]);

        $this->artisan('guides:assign-numbers')
            ->expectsOutput("Assigned Guide #11 to user ID {$missingUsers[0]->id}")
            ->expectsOutput("Assigned Guide #12 to user ID {$missingUsers[1]->id}")
            ->expectsOutput('Assigned guide numbers to 2 users.')
            ->assertSuccessful();

        $this->assertSame(10, $existing->fresh()->guide_number);
        $this->assertSame(11, $missingUsers[0]->fresh()->guide_number);
        $this->assertSame(12, $missingUsers[1]->fresh()->guide_number);
    }

    public function test_new_user_gets_next_stable_guide_number(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        app(GuideNumberService::class)->assignIfMissing($first->fresh());

        $this->assertSame(1, $first->fresh()->guide_number);
        $this->assertSame(2, $second->fresh()->guide_number);
        $this->assertSame('#1', $first->fresh()->guideNumberLabel());
    }

    public function test_early_guide_accolade_boundaries_are_synced(): void
    {
        $service = app(GuideAccoladeService::class);

        $guideOne = User::factory()->create(['guide_number' => 1]);
        $guideOneHundred = User::factory()->create(['guide_number' => 100]);
        $guideOneHundredOne = User::factory()->create(['guide_number' => 101]);
        $guideFiveHundred = User::factory()->create(['guide_number' => 500]);
        $guideFiveHundredOne = User::factory()->create(['guide_number' => 501]);

        foreach ([$guideOne, $guideOneHundred, $guideOneHundredOne, $guideFiveHundred, $guideFiveHundredOne] as $user) {
            $service->syncEarlyGuideAccolades($user->fresh());
        }

        $this->assertSame('Founding Guide', $guideOne->fresh()->primaryGuideAccolade()?->label);
        $this->assertSame('Founding Guide', $guideOneHundred->fresh()->primaryGuideAccolade()?->label);
        $this->assertSame('OG Guide', $guideOneHundredOne->fresh()->primaryGuideAccolade()?->label);
        $this->assertSame('OG Guide', $guideFiveHundred->fresh()->primaryGuideAccolade()?->label);
        $this->assertNull($guideFiveHundredOne->fresh()->primaryGuideAccolade());
        $this->assertSame(['OG Guide (#101)'], $guideOneHundredOne->fresh()->guideAccoladeTooltipLines());
        $this->assertSame('OG Guide number 101', $guideOneHundredOne->fresh()->guideAccoladeAriaLine());

        $this->assertFalse($guideOne->fresh()->guideAccolades()->where('code', 'og_guide')->exists());
    }

    public function test_early_guide_avatar_helpers_resolve_normalized_tiers(): void
    {
        $foundingGuide = User::factory()->make(['guide_number' => 45]);
        $foundingBoundary = User::factory()->make(['guide_number' => 100]);
        $ogGuide = User::factory()->make(['guide_number' => 101]);
        $ogGuideMidTier = User::factory()->make(['guide_number' => 233]);
        $ogBoundary = User::factory()->make(['guide_number' => 500]);
        $guideFiveHundredOne = User::factory()->make(['guide_number' => 501]);
        $unnumberedGuide = User::factory()->make(['guide_number' => null]);

        $this->assertTrue($foundingGuide->isFoundingGuide());
        $this->assertSame('#45', $foundingGuide->foundingGuideNumberLabel());
        $this->assertSame('Founding Guide', $foundingGuide->guideAccoladeLabel());
        $this->assertSame('Founding Guide (#45)', $foundingGuide->guideAccoladeTooltipLine());
        $this->assertSame('ring-[3px] ring-yellow-400', $foundingGuide->guideAvatarRingClass());
        $this->assertSame('founding_guide', $foundingGuide->guideAvatarAccolade()['key']);
        $this->assertSame('gold', $foundingGuide->guideAvatarAccolade()['css_variant']);
        $this->assertSame('Founding Guide', $foundingBoundary->guideAccoladeLabel());

        $this->assertSame([
            'key' => 'og_guide',
            'label' => 'OG Guide',
            'guide_number' => 101,
            'plate_text' => '#101',
            'css_variant' => 'silver',
        ], $ogGuide->guideAvatarAccolade());
        $this->assertSame('OG Guide (#233)', $ogGuideMidTier->guideAccoladeTooltipLine());
        $this->assertSame('ring-[3px] ring-slate-300', $ogGuideMidTier->guideAvatarRingClass());
        $this->assertSame('OG Guide', $ogBoundary->guideAccoladeLabel());

        foreach ([$guideFiveHundredOne, $unnumberedGuide] as $guide) {
            $this->assertFalse($guide->isFoundingGuide());
            $this->assertNull($guide->foundingGuideNumberLabel());
            $this->assertNull($guide->guideAccoladeLabel());
            $this->assertNull($guide->guideAccoladeTooltipLine());
            $this->assertNull($guide->guideAvatarAccolade());
        }
    }

    public function test_guide_avatar_renders_silver_plate_and_no_plate_without_accolade(): void
    {
        $ogAvatar = $this->blade('<x-guide-avatar :user="$user" size="sm" />', [
            'user' => User::factory()->make(['guide_number' => 233]),
        ]);

        $ogAvatar->assertSee('#233')
            ->assertSee('ring-slate-300', false)
            ->assertSee('from-slate-500', false);

        $plainAvatar = $this->blade('<x-guide-avatar :user="$user" size="sm" />', [
            'user' => User::factory()->make(['guide_number' => 501]),
        ]);

        $plainAvatar->assertDontSee('#501')
            ->assertDontSee('ring-slate-300', false);
    }

    public function test_primary_accolade_uses_highest_priority_active_non_expired_award(): void
    {
        $user = User::factory()->create(['guide_number' => 501]);
        $manual = GuideAccolade::factory()->create([
            'code' => 'beta_tester',
            'label' => 'Beta Tester',
            'ring_class' => 'ring-2 ring-blue-400',
            'priority' => 150,
            'is_active' => true,
        ]);
        $expired = GuideAccolade::factory()->create([
            'code' => 'expired_legend',
            'label' => 'Expired Legend',
            'ring_class' => 'ring-2 ring-rose-400',
            'priority' => 200,
            'is_active' => true,
        ]);
        $inactive = GuideAccolade::factory()->create([
            'code' => 'inactive_legend',
            'label' => 'Inactive Legend',
            'ring_class' => 'ring-2 ring-purple-400',
            'priority' => 300,
            'is_active' => false,
        ]);

        $user->guideAccolades()->attach($manual, ['source' => 'manual', 'awarded_at' => now()]);
        $user->guideAccolades()->attach($expired, ['source' => 'manual', 'awarded_at' => now(), 'expires_at' => now()->subMinute()]);
        $user->guideAccolades()->attach($inactive, ['source' => 'manual', 'awarded_at' => now()]);

        $this->assertSame('Beta Tester', $user->fresh()->primaryGuideAccolade()?->label);
        $this->assertSame('ring-2 ring-blue-400', $user->fresh()->guideAvatarRingClass());
    }

    public function test_accolade_tooltip_uses_template_and_public_display_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Google Full Name',
            'public_display_name' => 'Public Mei',
            'guide_number' => 34,
        ]);

        app(GuideAccoladeService::class)->syncEarlyGuideAccolades($user->fresh());

        $this->assertSame(['Founding Guide (#34)'], $user->fresh()->guideAccoladeTooltipLines());
        $this->assertSame('Founding Guide number 34', $user->fresh()->guideAccoladeAriaLine());
        $this->assertSame('Public Mei', $user->fresh()->publicName());
    }
}
