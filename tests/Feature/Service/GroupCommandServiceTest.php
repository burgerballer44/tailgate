<?php

use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedGroupSeasonFollowsData;
use App\DTO\ValidatedGroupPredictionScoringPolicyData;
use App\DTO\ValidatedGroupPoliciesData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Member;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\GroupCommandService;

beforeEach(function () {
    $this->service = new GroupCommandService(
        app(MemberCommandInterface::class),
        app(PlayerCommandInterface::class)
    );
});

describe('create group', function () {
    test('with valid data', function () {
        // create user
        $user = User::factory()->create();

        // group data
        $data = [
            'name' => 'Test Group',
            'owner_id' => $user->id,
            'member_limit' => 10,
            'player_limit' => 5,
        ];

        // ensure group does not exist
        $this->assertDatabaseMissing('groups', ['name' => $data['name']]);

        // create group
        $group = $this->service->create(ValidatedGroupData::fromArray($data));

        // verify group exists
        $this->assertDatabaseHas('groups', ['name' => $data['name']]);
        expect($group)->toBeInstanceOf(Group::class);
        expect($group->name)->toBe($data['name']);
        expect($group->owner_id)->toBe($user->id);
        expect($group->member_limit)->toBe(10);
        expect($group->player_limit)->toBe(5);
    });
});

describe('update group', function () {
    test('with valid data', function () {
        // create existing group
        $group = Group::factory()->create([
            'name' => 'Old Name',
            'member_limit' => 5,
        ]);

        // update data
        $data = [
            'name' => 'New Name',
            'owner_id' => $group->owner_id,
            'member_limit' => 20,
        ];

        // update group
        $updatedGroup = $this->service->update($group, ValidatedGroupData::fromArray($data));

        // verify updated
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'New Name',
            'member_limit' => 20,
        ]);
        expect($updatedGroup->name)->toBe('New Name');
    });
});

describe('update group policies', function () {
    test('updates only enabled prediction policies for the selected followed season', function () {
        $group = Group::factory()->create([
            'name' => 'Original Name',
            'owner_id' => User::factory()->create()->id,
            'member_limit' => 21,
            'player_limit' => 4,
        ]);

        $season = Season::factory()->active()->create();
        $seasonFollow = GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'enabled_prediction_policies' => [],
        ]);

        $updatedGroup = $this->service->updatePolicies($group, ValidatedGroupPoliciesData::fromArray([
            'season_id' => $season->id,
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]));

        $seasonFollow->refresh();

        expect($updatedGroup->seasonFollows)->toHaveCount(1);
        expect($seasonFollow->enabled_prediction_policies)->toBe(['group-unique-prediction']);
        expect($group->name)->toBe('Original Name');
        expect($group->member_limit)->toBe(21);
        expect($group->player_limit)->toBe(4);
    });

    test('clears enabled prediction policies when payload is empty', function () {
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $seasonFollow = GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        $this->service->updatePolicies($group, ValidatedGroupPoliciesData::fromArray([
            'season_id' => $season->id,
            'enabled_prediction_policies' => [],
        ]));

        $seasonFollow->refresh();
        expect($seasonFollow->enabled_prediction_policies)->toBe([]);
    });
});

describe('update prediction scoring policy', function () {
    test('updates only prediction scoring policy key for the selected followed season', function () {
        $group = Group::factory()->create([
            'name' => 'Original Name',
            'member_limit' => 21,
            'player_limit' => 4,
        ]);

        $season = Season::factory()->active()->create();
        $seasonFollow = GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'prediction-difference-from-score',
        ]);

        $this->service->updatePredictionScoringPolicy($group, ValidatedGroupPredictionScoringPolicyData::fromArray([
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'placement-points',
        ]));

        $seasonFollow->refresh();

        expect($seasonFollow->prediction_scoring_policy)->toBe('placement-points');
        expect($group->name)->toBe('Original Name');
        expect($group->member_limit)->toBe(21);
        expect($group->player_limit)->toBe(4);
    });
});

describe('sync season follows', function () {
    test('creates season follows for selected seasons and removes unselected seasons', function () {
        $group = Group::factory()->create();
        $firstSeason = Season::factory()->active()->create();
        $secondSeason = Season::factory()->active()->create();
        $thirdSeason = Season::factory()->active()->create();

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $thirdSeason->id,
        ]);

        $this->service->syncSeasonFollows($group, ValidatedGroupSeasonFollowsData::fromArray([
            'season_ids' => [$firstSeason->id, $secondSeason->id],
        ]));

        expect($group->fresh()->seasonFollows()->pluck('season_id')->all())->toBe([$firstSeason->id, $secondSeason->id]);
    });

    test('keeps the selected seasons in place when syncing again', function () {
        $group = Group::factory()->create();
        $firstSeason = Season::factory()->active()->create();
        $secondSeason = Season::factory()->active()->create();

        $this->service->syncSeasonFollows($group, ValidatedGroupSeasonFollowsData::fromArray([
            'season_ids' => [$firstSeason->id, $secondSeason->id],
        ]));

        $this->service->syncSeasonFollows($group, ValidatedGroupSeasonFollowsData::fromArray([
            'season_ids' => [$firstSeason->id, $secondSeason->id],
        ]));

        $seasonFollowIds = $group->fresh()->seasonFollows->pluck('season_id')->all();

        expect($seasonFollowIds)->toBe([$firstSeason->id, $secondSeason->id]);
        expect($seasonFollowIds)->toHaveCount(2);
    });
});

describe('delete group', function () {
    test('removes group from database', function () {
        // create group
        $group = Group::factory()->create();

        // delete group
        $this->service->delete($group);

        // verify removed
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    });
});

describe('add member', function () {
    test('adds member to group', function () {
        // create group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // member data
        $data = [
            'user_id' => $user->id,
        ];

        // add member
        $member = $this->service->addMember($group, ValidatedMemberData::fromArray($data));

        // verify member added
        $this->assertDatabaseHas('members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        expect($member)->toBeInstanceOf(Member::class);
    });
});

describe('remove member', function () {
    test('removes member from group', function () {
        // create member
        $member = Member::factory()->create();

        // remove member
        $this->service->removeMember($member->group, $member);

        // verify removed
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    });
});

describe('add player', function () {
    test('adds player to member', function () {
        // create member
        $member = Member::factory()->create();

        // player data
        $data = [
            'player_name' => 'Test Player',
        ];

        // add player
        $player = $this->service->addPlayer($member->group, $member, ValidatedPlayerData::fromArray($data));

        // verify player added
        $this->assertDatabaseHas('players', [
            'member_id' => $member->id,
            'player_name' => 'Test Player',
        ]);
        expect($player)->toBeInstanceOf(Player::class);
    });
});

describe('remove player', function () {
    test('removes player from member', function () {
        // create player
        $player = Player::factory()->create();

        // remove player
        $this->service->removePlayer($player->member->group, $player->member, $player);

        // verify removed
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });
});

describe('follow team', function () {
    test('creates follow relationship', function () {
        // create group and team
        $group = Group::factory()->create();
        $team = Team::factory()->create();
        $season = Season::factory()->active()->create();

        // follow data
        $data = [
            'team_id' => $team->id,
            'season_ids' => [$season->id],
        ];

        // follow team
        $follow = $this->service->followTeam($group, ValidatedFollowData::fromArray($data));

        // verify follow created
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('group_season_follows', [
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        expect($follow)->toBeInstanceOf(Follow::class);
    });

    test('adds all selected seasons when following a team', function () {
        $group = Group::factory()->create();
        $team = Team::factory()->create();
        $firstSeason = Season::factory()->active()->create();
        $secondSeason = Season::factory()->active()->create();

        $follow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'season_ids' => [$firstSeason->id, $secondSeason->id],
        ]));

        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('group_season_follows', [
            'group_id' => $group->id,
            'season_id' => $firstSeason->id,
        ]);

        $this->assertDatabaseHas('group_season_follows', [
            'group_id' => $group->id,
            'season_id' => $secondSeason->id,
        ]);

        expect($follow)->toBeInstanceOf(Follow::class);
    });

    test('creates multiple follows when under follow limit', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();
        $firstSeason = Season::factory()->active()->create();
        $secondSeason = Season::factory()->active()->create();

        $firstFollow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $firstTeam->id,
            'season_ids' => [$firstSeason->id],
        ]));

        $secondFollow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $secondTeam->id,
            'season_ids' => [$secondSeason->id],
        ]));

        expect($group->follows()->count())->toBe(2);
        expect($firstFollow->id)->not->toBe($secondFollow->id);
    });

    test('throws error when follow limit is reached', function () {
        $group = Group::factory()->create(['follow_limit' => 1]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();
        $season = Season::factory()->active()->create();

        $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $firstTeam->id,
            'season_ids' => [$season->id],
        ]));

        expect(fn () => $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $secondTeam->id,
            'season_ids' => [$season->id],
        ])))->toThrow('This group has reached its follow limit.');
    });

    test('throws error when team is already followed', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $team = Team::factory()->create();
        $season = Season::factory()->active()->create();

        $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'season_ids' => [$season->id],
        ]));

        expect(fn () => $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'season_ids' => [$season->id],
        ])))->toThrow('This group is already following this team.');
    });
});

describe('remove follow', function () {
    test('removes only the targeted follow relationship', function () {
        // create follow
        $group = Group::factory()->create(['follow_limit' => 2]);
        $follow = Follow::factory()->create(['group_id' => $group->id]);
        $otherFollow = Follow::factory()->create(['group_id' => $group->id]);

        // remove follow
        $this->service->removeFollow($group, $follow);

        // verify removed
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
        $this->assertDatabaseHas('follows', ['id' => $otherFollow->id]);
    });
});
