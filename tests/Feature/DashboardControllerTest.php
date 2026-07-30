<?php

use App\DTO\QuickPredictionPayload;
use App\Models\Enums\MemberStatus;
use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use App\Services\Contracts\QuickPredictionServiceInterface;
use App\Services\QuickPredictionService;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('redirects unauthenticated users to login', function () {
        auth()->logout();

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });

    test('works for authenticated user', function () {
        // create a group and add user as member to trigger quick predictions section
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('dashboard');

        // assert data is passed to view
        $response->assertViewHas('groups');
        $response->assertViewHas('quickPredictionWindowLabel', QuickPredictionService::predictionWindowLabel());
        $response->assertViewHas('user', $this->user);
        $response->assertSee('Open quick predictions');
        $response->assertSee('quick_prediction_player');
        $response->assertSee('quick_prediction_home_score');
        $response->assertSee('quick_prediction_away_score');
    });

    test('shows user groups', function () {
        // create groups and add user as member
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group1->id,
        ]);

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group2->id,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are in view data
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(2);
        expect($groups->pluck('id'))->toContain($group1->id, $group2->id);
    });

    test('shows empty groups for user with no memberships', function () {
        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are empty
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(0);
    });

    test('does not show groups user is not member of', function () {
        // create a group not belonging to user
        $group = Group::factory()->create();

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are empty
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(0);
    });

    test('shows membership pending approval for pending memberships', function () {
        // create a group with pending membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert "Membership Pending Approval" is shown
        $response->assertSee('Membership Pending Approval');
    });

    test('does not show links for pending memberships', function () {
        // create a group with pending membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert no link to the group
        $response->assertDontSee('<a href="'.route('groups.show', $group).'">', false);
    });

    test('shows links for approved memberships', function () {
        // create a group with approved membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert link to the group is present
        $response->assertSee('<a href="'.route('groups.show', $group).'">', false);
    });

    test('shows links for owned groups', function () {
        // create a group owned by the user
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert link to the group is present
        $response->assertSee('<a href="'.route('groups.show', $group).'">', false);
    });

});

describe('quickPredictions', function () {
    test('redirects unauthenticated users to login', function () {
        auth()->logout();

        $response = $this->get(route('dashboard.quick-predictions'));

        $response->assertRedirect(route('login'));
    });

    test('returns an empty payload when the user has no approved memberships', function () {
        $response = $this->getJson(route('dashboard.quick-predictions'));

        $response->assertOk();
        $response->assertJsonPath('games', []);
        $response->assertJsonPath('summary.total_games', 0);
        $response->assertJsonPath('summary.open_prediction_count', 0);
    });

    test('quick-predictions endpoint delegates payload building to bound quick prediction service implementation', function () {
        $payload = [
            'summary' => [
                'open_prediction_count' => 7,
                'total_games' => 2,
                'total_groups' => 3,
            ],
            'games' => [
                [
                    'context_key' => 'stub-group|123',
                    'group' => ['ulid' => 'stub-group', 'name' => 'Stub Group', 'member_ulid' => 'stub-member'],
                    'team' => ['name' => 'Stub Team', 'sport' => 'football'],
                    'game' => ['id' => 123, 'ulid' => 'stub-game'],
                    'players' => [],
                    'store_route_template' => '/groups/stub/predictions/__PLAYER__',
                    'update_route_template' => '/groups/stub/predictions/__PLAYER__/__PREDICTION__',
                    'group_upcoming_games_route' => '/groups/stub?tab=upcoming-games',
                ],
            ],
        ];

        app()->bind(QuickPredictionServiceInterface::class, fn () => new class($payload) implements QuickPredictionServiceInterface
        {
            /**
             * @param  array<string, mixed>  $payload
             */
            public function __construct(private array $payload) {}

            public static function predictionWindowLabel(): string
            {
                return 'next 2 weeks';
            }

            public function getQuickPredictionsPayloadForUser(User $user): QuickPredictionPayload
            {
                return new QuickPredictionPayload(
                    openPredictionCount: $this->payload['summary']['open_prediction_count'],
                    totalGames: $this->payload['summary']['total_games'],
                    totalGroups: $this->payload['summary']['total_groups'],
                    games: $this->payload['games'],
                );
            }
        });

        $response = $this->getJson(route('dashboard.quick-predictions'));

        $response->assertOk();
        $response->assertExactJson($payload);
    });
});
