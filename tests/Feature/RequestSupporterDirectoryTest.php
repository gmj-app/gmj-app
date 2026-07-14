<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestSupporterDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_and_six_supporter_preview_are_separate(): void
    {
        [$creator, $requester, $recommendation] = $this->requestWithSupporters(12);

        $response = $this->get(route('requests.card-details', $recommendation));
        $html = $response->getContent();
        $requesterStart = strpos($html, 'data-requester-identity');
        $supporterStart = strpos($html, 'data-supporter-preview');
        $requesterMarkup = substr($html, $requesterStart, $supporterStart - $requesterStart);

        $response->assertOk()
            ->assertSee('data-supporter-preview-count="6"', false)
            ->assertSee('data-supporter-total="12"', false)
            ->assertSee('aria-label="View 6 more supporters"', false)
            ->assertSee('size-12 text-sm sm:size-14 sm:text-base', false)
            ->assertSee('x-on:keydown.escape.window="if (open) closeDirectory()"', false)
            ->assertSee('Loading supporters&hellip;', false)
            ->assertSee('Supporters could not be loaded.')
            ->assertSee('Try again.');
        $this->assertSame(6, substr_count($html, 'data-supporter-identity'));
        $this->assertStringContainsString($requester->publicName(), $requesterMarkup);
        $this->assertStringNotContainsString('+6', $requesterMarkup);
        $this->assertStringNotContainsString('data-supporter-identity', $requesterMarkup);
        $this->assertNotNull($creator);
    }

    public function test_preview_overflow_boundaries_and_requester_vote_semantics(): void
    {
        [, $requester, $six] = $this->requestWithSupporters(6);
        UserPick::factory()->create([
            'creator_id' => $six->creator_id,
            'recommendation_id' => $six->id,
            'user_id' => $requester->id,
        ]);

        $this->get(route('requests.card-details', $six))
            ->assertOk()
            ->assertSee('data-supporter-preview-count="6"', false)
            ->assertSee('data-supporter-total="6"', false)
            ->assertDontSee('aria-label="View 1 more supporter"', false);

        [, , $seven] = $this->requestWithSupporters(7);
        $this->get(route('requests.card-details', $seven))
            ->assertOk()
            ->assertSee('data-supporter-preview-count="6"', false)
            ->assertSee('aria-label="View 1 more supporter"', false);
    }

    public function test_supporter_endpoint_is_public_safe_paginated_and_query_bounded(): void
    {
        [, , $recommendation] = $this->requestWithSupporters(50);
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $first = $this->getJson(route('requests.supporters', $recommendation));
        $first->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('next_page', 2)
            ->assertJsonPath('total', 50);
        $this->assertSame(24, substr_count($first->json('html'), 'data-supporter-identity'));
        $this->assertStringNotContainsString('@example.', $first->getContent());
        $this->assertLessThanOrEqual(8, $queryCount);

        $second = $this->getJson(route('requests.supporters', ['recommendation' => $recommendation, 'page' => 2]));
        $second->assertOk()->assertJsonPath('next_page', 3);
        $this->assertSame(24, substr_count($second->json('html'), 'data-supporter-identity'));

        $third = $this->getJson(route('requests.supporters', ['recommendation' => $recommendation, 'page' => 3]));
        $third->assertOk()->assertJsonPath('next_page', null);
        $this->assertSame(2, substr_count($third->json('html'), 'data-supporter-identity'));
    }

    public function test_supporter_endpoint_rejects_non_public_requests(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $private = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'pending']);

        $this->getJson(route('requests.supporters', $private))->assertNotFound();
    }

    /** @return array{Creator, User, Recommendation} */
    private function requestWithSupporters(int $count): array
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $requester = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $requester->id,
            'status' => 'approved',
        ]);

        User::factory()->count($count)->create()->each(fn (User $supporter) => UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $supporter->id,
        ]));

        return [$creator, $requester, $recommendation];
    }
}
