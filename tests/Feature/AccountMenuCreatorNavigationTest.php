<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountMenuCreatorNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_ordinary_guides_do_not_receive_creator_menu_items(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="account-menu"', false)
            ->assertDontSee('Manage Requests')
            ->assertDontSee('Creator Settings');

        $guide = User::factory()->create();

        $this->actingAs($guide)->get(route('about'))
            ->assertOk()
            ->assertDontSee('Manage Requests')
            ->assertDontSee('Creator Settings');
    }

    public function test_active_creator_owner_sees_ordered_links_in_desktop_and_mobile_menus(): void
    {
        [$creator, $owner] = $this->activeCreatorWithOwner();

        $response = $this->actingAs($owner)->get(route('about'))->assertOk();
        $html = $response->getContent();
        $desktopMenu = $this->desktopAccountMenu($html);

        $response
            ->assertSee('href="'.route('creators.recommendations.index', $creator).'"', false)
            ->assertSee('href="'.route('creators.settings.edit', $creator).'"', false);

        $this->assertSame(2, substr_count($html, 'Manage Requests'));
        $this->assertSame(2, substr_count($html, 'Creator Settings'));
        $this->assertTrue(strpos($desktopMenu, 'My Hub') < strpos($desktopMenu, 'My Activity'));
        $this->assertTrue(strpos($desktopMenu, 'My Activity') < strpos($desktopMenu, 'Manage Requests'));
        $this->assertTrue(strpos($desktopMenu, 'Manage Requests') < strpos($desktopMenu, 'Creator Settings'));
        $this->assertTrue(strpos($desktopMenu, 'Creator Settings') < strpos($desktopMenu, 'Profile'));
        $this->assertTrue(strpos($desktopMenu, 'Profile') < strpos($desktopMenu, 'Theme'));
        $this->assertTrue(strpos($desktopMenu, 'Theme') < strpos($desktopMenu, 'Log out'));
        $this->assertStringNotContainsString(route('profile.edit').'">Creator Settings', $html);
    }

    public function test_inactive_soft_deleted_and_non_owner_relations_do_not_expose_creator_links(): void
    {
        $user = User::factory()->create();
        $inactive = Creator::factory()->create(['status' => 'inactive', 'deactivated_at' => now()]);
        $deleted = Creator::factory()->create();
        $unowned = Creator::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $inactive->id, 'user_id' => $user->id, 'role' => 'owner']);
        CreatorOwner::query()->create(['creator_id' => $deleted->id, 'user_id' => $user->id, 'role' => 'owner']);
        $deleted->delete();

        $this->actingAs($user)->get(route('about'))
            ->assertOk()
            ->assertDontSee('Manage Requests')
            ->assertDontSee('Creator Settings')
            ->assertDontSee($unowned->slug);
    }

    public function test_super_admin_status_does_not_imply_creator_ownership_but_coexists_with_it(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        config(['super_admin.emails' => [$admin->email]]);

        $this->actingAs($admin)->get(route('about'))
            ->assertOk()
            ->assertSee('Super Admin')
            ->assertDontSee('Manage Requests')
            ->assertDontSee('Creator Settings');

        [$creator] = $this->activeCreatorWithOwner($admin);
        $response = $this->actingAs($admin)->get(route('about'))->assertOk();
        $menu = $this->desktopAccountMenu($response->getContent());

        $response
            ->assertSee('href="'.route('creators.recommendations.index', $creator).'"', false)
            ->assertSee('href="'.route('creators.settings.edit', $creator).'"', false)
            ->assertSee('Super Admin');
        $this->assertTrue(strpos($menu, 'Creator Settings') < strpos($menu, 'Super Admin'));
        $this->assertTrue(strpos($menu, 'Super Admin') < strpos($menu, 'Profile'));
    }

    public function test_multiple_ownership_uses_selection_until_a_current_creator_context_exists(): void
    {
        $owner = User::factory()->create();
        [$first] = $this->activeCreatorWithOwner($owner);
        [$second] = $this->activeCreatorWithOwner($owner);

        $selection = $this->actingAs($owner)->get(route('about'))->assertOk();
        $this->assertSame(4, substr_count($selection->getContent(), 'href="'.route('creators.index').'"'));

        $contextual = $this->actingAs($owner)
            ->get(route('creators.settings.edit', $second))
            ->assertOk()
            ->assertSee('href="'.route('creators.recommendations.index', $second).'"', false)
            ->assertSee('href="'.route('creators.settings.edit', $second).'"', false)
            ->assertDontSee('href="'.route('creators.recommendations.index', $first).'"', false);

        $this->assertNotNull($contextual);
    }

    public function test_creator_menu_context_queries_ownership_once_and_never_loads_requests(): void
    {
        [$creator, $owner] = $this->activeCreatorWithOwner();
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($owner)->get(route('about'))->assertOk();

        $this->assertCount(1, collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'creator_owners')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'recommendations')));
        $this->assertNotNull($creator);
    }

    public function test_guest_navigation_does_not_query_creator_ownership(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get(route('about'))->assertOk();

        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'creator_owners')));
    }

    public function test_destination_routes_keep_owner_authorization_and_guessed_creators_are_forbidden(): void
    {
        [$creator, $owner] = $this->activeCreatorWithOwner();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('creators.recommendations.index', $creator))->assertForbidden();
        $this->actingAs($stranger)->get(route('creators.settings.edit', $creator))->assertForbidden();
        $this->actingAs($owner)->get(route('creators.recommendations.index', $creator))->assertOk();
        $this->actingAs($owner)->get(route('creators.settings.edit', $creator))->assertOk();
    }

    /** @return array{Creator, User} */
    private function activeCreatorWithOwner(?User $owner = null): array
    {
        $owner ??= User::factory()->create();
        $creator = Creator::factory()->create(['status' => 'active', 'deactivated_at' => null]);
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        return [$creator, $owner];
    }

    private function desktopAccountMenu(string $html): string
    {
        $start = strpos($html, 'id="account-menu"');
        $end = strpos($html, '</form>', $start);

        return substr($html, $start, $end - $start);
    }
}
