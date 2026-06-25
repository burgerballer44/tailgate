<?php

use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedGroupPoliciesData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Sport;
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
    test('updates only enabled prediction policies', function () {
        $group = Group::factory()->create([
            'name' => 'Original Name',
            'owner_id' => User::factory()->create()->id,
            'member_limit' => 21,
            'player_limit' => 4,
            'enabled_prediction_policies' => [],
        ]);

        $updatedGroup = $this->service->updatePolicies($group, ValidatedGroupPoliciesData::fromArray([
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]));

        $group->refresh();

        expect($updatedGroup->enabled_prediction_policies)->toBe(['group-unique-prediction']);
        expect($group->enabled_prediction_policies)->toBe(['group-unique-prediction']);
        expect($group->name)->toBe('Original Name');
        expect($group->member_limit)->toBe(21);
        expect($group->player_limit)->toBe(4);
    });

    test('clears enabled prediction policies when payload is empty', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        $this->service->updatePolicies($group, ValidatedGroupPoliciesData::fromArray([
            'enabled_prediction_policies' => [],
        ]));

        $group->refresh();
        expect($group->enabled_prediction_policies)->toBe([]);
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

        // follow data
        $data = [
            'team_id' => $team->id,
        ];

        // follow team
        $follow = $this->service->followTeam($group, ValidatedFollowData::fromArray($data));

        // verify follow created
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'sport' => null,
        ]);
        expect($follow)->toBeInstanceOf(Follow::class);
    });

    test('creates sport-scoped follow relationship', function () {
        $group = Group::factory()->create();
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $follow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
        ]));

        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
        ]);
        expect($follow->sport)->toBe(Sport::FOOTBALL);
    });

    test('creates multiple follows when under follow limit', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $firstTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $secondTeam = Team::factory()->withSports([Sport::BASKETBALL])->create();

        $firstFollow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $firstTeam->id,
            'sport' => Sport::FOOTBALL->value,
        ]));

        $secondFollow = $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $secondTeam->id,
            'sport' => Sport::BASKETBALL->value,
        ]));

        expect($group->follows()->count())->toBe(2);
        expect($firstFollow->id)->not->toBe($secondFollow->id);
    });

    test('throws error when follow limit is reached', function () {
        $group = Group::factory()->create(['follow_limit' => 1]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();

        $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $firstTeam->id,
        ]));

        expect(fn () => $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $secondTeam->id,
        ])))->toThrow('This group has reached its follow limit.');
    });

    test('throws error when team and sport scope are already followed', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
        ]));

        expect(fn () => $this->service->followTeam($group, ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
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
