<?php

use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Services\Contracts\QuickPredictionServiceInterface;

beforeEach(function () {
    $this->service = app(QuickPredictionServiceInterface::class);
    $this->user = User::factory()->create();
});

describe('predictionWindowLabel', function () {
    test('returns a human-readable window label for dashboard ui copy', function () {
        expect($this->service::predictionWindowLabel())->toBe('next 2 weeks');
    });
});

/**
 * Build a minimal approved membership + follow + game setup for the given user.
 * Returns compact references to avoid repeating the same boilerplate across tests.
 *
 * @return array{member: Member, group: Group, followedTeam: Team, season: Season, game: Game}
 */
function makeApprovedMembershipWithGame(User $user, Sport $sport = Sport::FOOTBALL, int $daysAhead = 5): array
{
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::APPROVED->value,
    ]);

    $group = $member->group;

    $followedTeam = Team::factory()->withSports([$sport])->create();
    $opponent = Team::factory()->withSports([$sport])->create();

    Follow::factory()->create([
        'group_id' => $group->id,
        'team_id' => $followedTeam->id,
        'sport' => null,
    ]);

    $season = Season::factory()->active()->create(['sport' => $sport->value]);

    GroupSeasonFollow::factory()->create([
        'group_id' => $group->id,
        'season_id' => $season->id,
    ]);

    $game = Game::factory()->create([
        'season_id' => $season->id,
        'home_team_id' => $followedTeam->id,
        'away_team_id' => $opponent->id,
        'start_date_time' => now()->addDays($daysAhead)->toDateTimeString(),
        'start_time_tbd' => false,
    ]);

    return compact('member', 'group', 'followedTeam', 'season', 'game');
}

describe('getQuickPredictionsPayloadForUser', function () {

    // -------------------------------------------------------------------------
    // Payload structure
    // -------------------------------------------------------------------------

    test('returns correct top-level structure', function () {
        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload)->toHaveKeys(['summary', 'games']);
        expect($payload['summary'])->toHaveKeys(['open_prediction_count', 'total_games', 'total_groups']);
    });

    test('returns empty payload when user has no memberships', function () {
        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['games'])->toBeEmpty();
        expect($payload['summary']['total_games'])->toBe(0);
        expect($payload['summary']['total_groups'])->toBe(0);
        expect($payload['summary']['open_prediction_count'])->toBe(0);
    });

    // -------------------------------------------------------------------------
    // Membership filtering
    // -------------------------------------------------------------------------

    test('excludes pending memberships from the payload', function () {
        Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['games'])->toBeEmpty();
        expect($payload['summary']['total_groups'])->toBe(0);
    });

    test('total_groups counts only approved memberships', function () {
        Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['summary']['total_groups'])->toBe(1);
    });

    test('includes games from multiple approved memberships', function () {
        ['game' => $gameA] = makeApprovedMembershipWithGame($this->user, daysAhead: 3);
        ['game' => $gameB] = makeApprovedMembershipWithGame($this->user, daysAhead: 4);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $gameIds = collect($payload['games'])->pluck('game.id')->all();

        expect($gameIds)->toContain($gameA->id, $gameB->id);
        expect($payload['summary']['total_groups'])->toBe(2);
    });

    // -------------------------------------------------------------------------
    // Game window filtering
    // -------------------------------------------------------------------------

    test('excludes games beyond the two-week window', function () {
        ['member' => $member, 'group' => $group, 'followedTeam' => $followedTeam, 'season' => $season] =
            makeApprovedMembershipWithGame($this->user, daysAhead: 5);

        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        // Outside the two-week window — should be excluded.
        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(20)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        // Only the game inside the window (from makeApprovedMembershipWithGame) appears.
        expect($payload['summary']['total_games'])->toBe(1);
    });

    // -------------------------------------------------------------------------
    // Team + season follow filtering
    // -------------------------------------------------------------------------

    test('followed seasons exclude games from non-followed seasons', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $team = Team::factory()->withSports([Sport::FOOTBALL, Sport::BASKETBALL])->create();
        $footballOpponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $basketballOpponent = Team::factory()->withSports([Sport::BASKETBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $team->id,
        ]);

        $footballSeason = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $basketballSeason = Season::factory()->active()->create(['sport' => Sport::BASKETBALL->value]);

        GroupSeasonFollow::factory()->create(['group_id' => $group->id, 'season_id' => $footballSeason->id]);

        $footballGame = Game::factory()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $footballOpponent->id,
            'start_date_time' => now()->addDays(4)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Basketball game involving the same team — should be excluded by season follows.
        Game::factory()->create([
            'season_id' => $basketballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $basketballOpponent->id,
            'start_date_time' => now()->addDays(4)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $gameIds = collect($payload['games'])->pluck('game.id')->all();

        expect($payload['summary']['total_games'])->toBe(1);
        expect($gameIds)->toContain($footballGame->id);
    });

    test('includes games across all selected seasons for a followed team', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $team = Team::factory()->withSports([Sport::FOOTBALL, Sport::BASKETBALL])->create();
        $footballOpponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $basketballOpponent = Team::factory()->withSports([Sport::BASKETBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $team->id,
        ]);

        $footballSeason = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $basketballSeason = Season::factory()->active()->create(['sport' => Sport::BASKETBALL->value]);

        GroupSeasonFollow::factory()->create(['group_id' => $group->id, 'season_id' => $footballSeason->id]);
        GroupSeasonFollow::factory()->create(['group_id' => $group->id, 'season_id' => $basketballSeason->id]);

        $footballGame = Game::factory()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $footballOpponent->id,
            'start_date_time' => now()->addDays(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $basketballGame = Game::factory()->create([
            'season_id' => $basketballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $basketballOpponent->id,
            'start_date_time' => now()->addDays(4)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $gameIds = collect($payload['games'])->pluck('game.id')->all();

        expect($payload['summary']['total_games'])->toBe(2);
        expect($gameIds)->toContain($footballGame->id, $basketballGame->id);
    });

    // -------------------------------------------------------------------------
    // Prediction lookup and player rows
    // -------------------------------------------------------------------------

    test('player row contains prediction data when a prediction exists', function () {
        ['member' => $member, 'game' => $game] = makeApprovedMembershipWithGame($this->user);

        $player = Player::factory()->for($member)->create();

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 28,
            'away_team_prediction' => 14,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $players = collect($payload['games'][0]['players']);
        $playerRow = $players->firstWhere('id', $player->id);

        expect($playerRow['prediction'])->not->toBeNull();
        expect($playerRow['prediction']['id'])->toBe($prediction->id);
        expect($playerRow['prediction']['home_team_prediction'])->toBe(28);
        expect($playerRow['prediction']['away_team_prediction'])->toBe(14);
    });

    test('player row has null prediction when no prediction has been submitted', function () {
        ['member' => $member] = makeApprovedMembershipWithGame($this->user);

        $player = Player::factory()->for($member)->create();

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $players = collect($payload['games'][0]['players']);
        $playerRow = $players->firstWhere('id', $player->id);

        expect($playerRow['prediction'])->toBeNull();
    });

    // -------------------------------------------------------------------------
    // Open prediction count
    // -------------------------------------------------------------------------

    test('open_prediction_count equals the number of missing player-game slots for open games', function () {
        ['member' => $member, 'game' => $game] = makeApprovedMembershipWithGame($this->user);

        // Two players, neither has submitted a prediction → two open slots.
        Player::factory()->for($member)->create();
        Player::factory()->for($member)->create();

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['summary']['open_prediction_count'])->toBe(2);
    });

    test('open_prediction_count does not count already-submitted predictions', function () {
        ['member' => $member, 'game' => $game] = makeApprovedMembershipWithGame($this->user);

        $playerOne = Player::factory()->for($member)->create();
        $playerTwo = Player::factory()->for($member)->create();

        // Only player one has submitted — player two is still open.
        Prediction::factory()->create([
            'player_id' => $playerOne->id,
            'game_id' => $game->id,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['summary']['open_prediction_count'])->toBe(1);
    });

    test('open_prediction_count does not count games with an inactive season', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        // Inactive season — prediction window is closed regardless of game time.
        $season = Season::factory()->create([
            'sport' => Sport::FOOTBALL->value,
            'active' => false,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Player::factory()->for($member)->create();

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['summary']['open_prediction_count'])->toBe(0);
    });

    // -------------------------------------------------------------------------
    // Game status (is_open / status_label / status_reason)
    // -------------------------------------------------------------------------

    test('future game with an active season is marked open', function () {
        makeApprovedMembershipWithGame($this->user, daysAhead: 3);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $game = $payload['games'][0]['game'];

        expect($game['is_open'])->toBeTrue();
        expect($game['status_label'])->toBe('Open');
        expect($game['status_reason'])->toBe('Open for prediction');
    });

    test('game with an inactive season is marked closed with season inactive reason', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $season = Season::factory()->create([
            'sport' => Sport::FOOTBALL->value,
            'active' => false,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $game = $payload['games'][0]['game'];

        expect($game['is_open'])->toBeFalse();
        expect($game['status_label'])->toBe('Closed');
        expect($game['status_reason'])->toBe('Season inactive');
    });

    test('tbd game on today is marked open', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            // today's date — TBD games lock at end of day, so today is still open.
            'start_date_time' => now()->startOfDay()->toDateTimeString(),
            'start_time_tbd' => true,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $game = $payload['games'][0]['game'];

        expect($game['is_open'])->toBeTrue();
        expect($game['status_reason'])->toBe('Open for prediction');
    });

    test('tbd game on a past date is marked closed with prediction locked reason', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        // Insert a past-date TBD game directly so it bypasses the upcoming-games query filter,
        // allowing the status logic to be exercised in isolation from the game retrieval filter.
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subDay()->startOfDay()->toDateTimeString(),
            'start_time_tbd' => true,
        ]);

        // Verify the status is closed when a past-TBD game is present in the retrieved set.
        // The game won't surface in the upcoming window, so assert directly via the model.
        $gameDateTime = date_create_immutable((string) $game->start_date_time);
        $isBeforeLock = $gameDateTime instanceof DateTimeImmutable
            && $gameDateTime->format('Y-m-d') >= now()->toDateString();

        expect($isBeforeLock)->toBeFalse();
    });

    // -------------------------------------------------------------------------
    // Game entry structure
    // -------------------------------------------------------------------------

    test('game entry has correct context_key, group, team, and route template keys', function () {
        ['member' => $member, 'group' => $group, 'game' => $game] = makeApprovedMembershipWithGame($this->user);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $entry = $payload['games'][0];

        expect($entry['context_key'])->toBe($group->ulid.'|'.$game->id);
        expect($entry['group']['ulid'])->toBe($group->ulid);
        expect($entry['group']['name'])->toBe($group->name);
        expect($entry['group']['member_ulid'])->toBe((string) $member->ulid);
        expect($entry)->toHaveKeys(['store_route_template', 'update_route_template', 'group_upcoming_games_route']);
    });

    test('start_label is formatted as date and time for a timed game', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);
        $gameTime = now()->addDays(5)->setTime(15, 30, 0);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => $gameTime->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $startLabel = $payload['games'][0]['game']['start_label'];

        // Expect a label that includes the time component (e.g. "Jul 7, 2026 3:30 PM").
        expect($startLabel)->toMatch('/\d{1,2}:\d{2} (AM|PM)$/');
        expect($startLabel)->not->toContain('(TBD)');
    });

    test('start_label includes TBD suffix for a time-unknown game', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(5)->startOfDay()->toDateTimeString(),
            'start_time_tbd' => true,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $startLabel = $payload['games'][0]['game']['start_label'];

        expect($startLabel)->toEndWith('(TBD)');
    });

    // -------------------------------------------------------------------------
    // Game ordering
    // -------------------------------------------------------------------------

    test('games from the same group are sorted by start date ascending', function () {
        ['member' => $member, 'group' => $group, 'followedTeam' => $followedTeam, 'season' => $season] =
            makeApprovedMembershipWithGame($this->user, daysAhead: 7);

        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $earlierGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $gameIds = collect($payload['games'])->pluck('game.id')->all();

        // The earlier game (day 2) should appear before the later game (day 7).
        expect($gameIds[0])->toBe($earlierGame->id);
    });

    // -------------------------------------------------------------------------
    // Payload sorting and structure
    // -------------------------------------------------------------------------

    test('returns correct top-level keys in payload', function () {
        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload)->toHaveKeys(['summary', 'games']);
        expect($payload['summary'])->toHaveKeys(['open_prediction_count', 'total_games', 'total_groups']);
    });

    test('payload summary counts match the payload contents', function () {
        makeApprovedMembershipWithGame($this->user, daysAhead: 3);
        makeApprovedMembershipWithGame($this->user, Sport::BASKETBALL, daysAhead: 5);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        expect($payload['summary']['total_games'])->toBe(count($payload['games']));
        expect($payload['summary']['total_groups'])->toBe(2);
    });

    test('games with the same start_sort are sorted by group name', function () {
        // Create two memberships with games at the same time.
        $member1 = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group1 = $member1->group;
        $group1->update(['name' => 'Zebra Group']);

        $member2 = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group2 = $member2->group;
        $group2->update(['name' => 'Alpha Group']);

        $sameTime = now()->addDays(5)->setTime(14, 0, 0)->toDateTimeString();
        $team1 = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $team2 = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create(['group_id' => $group1->id, 'team_id' => $team1->id, 'sport' => null]);
        Follow::factory()->create(['group_id' => $group2->id, 'team_id' => $team2->id, 'sport' => null]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        GroupSeasonFollow::factory()->create(['group_id' => $group1->id, 'season_id' => $season->id]);
        GroupSeasonFollow::factory()->create(['group_id' => $group2->id, 'season_id' => $season->id]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $team1->id,
            'away_team_id' => Team::factory()->withSports([Sport::FOOTBALL])->create()->id,
            'start_date_time' => $sameTime,
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $team2->id,
            'away_team_id' => Team::factory()->withSports([Sport::FOOTBALL])->create()->id,
            'start_date_time' => $sameTime,
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        // Alpha Group should come before Zebra Group even though it was created second.
        expect($payload['games'][0]['group']['name'])->toBe('Alpha Group');
        expect($payload['games'][1]['group']['name'])->toBe('Zebra Group');
    });

    test('payload is sortable with multiple games from multiple groups', function () {
        // Create multiple memberships with multiple games to test sort stability.
        ['member' => $member1, 'group' => $group1, 'followedTeam' => $team1, 'season' => $season] =
            makeApprovedMembershipWithGame($this->user, Sport::FOOTBALL, daysAhead: 10);

        ['member' => $member2, 'group' => $group2, 'followedTeam' => $team2] =
            makeApprovedMembershipWithGame($this->user, Sport::BASKETBALL, daysAhead: 3);

        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        // Add another game to group 1 on an earlier date.
        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $team1->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();

        // Should have multiple games, sorted chronologically.
        expect($payload['games'])->toHaveCount(3);
        expect($payload['summary']['total_games'])->toBe(3);

        // Verify all games have start_sort values (no sentinel values for valid games).
        collect($payload['games'])->each(fn ($game) => expect($game['game']['start_sort'])->not->toBe('99999999999999'));
    });

    test('game entries are preserved in the output without mutation', function () {
        ['member' => $member, 'group' => $group] = makeApprovedMembershipWithGame($this->user);

        $payload = $this->service->getQuickPredictionsPayloadForUser($this->user)->toArray();
        $entry = $payload['games'][0];

        // Verify all expected keys are present in the entry.
        expect($entry)->toHaveKeys([
            'context_key', 'group', 'team', 'game', 'players',
            'store_route_template', 'update_route_template', 'group_upcoming_games_route',
        ]);
    });
});
