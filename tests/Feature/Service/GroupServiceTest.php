<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\GroupService;
use App\Services\MemberService;
use App\Services\PlayerService;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedFollowData;

beforeEach(function () {
    $this->service = new GroupService(new MemberService(), new PlayerService());
});

describe('create a group', function () {
    test('with valid data', function () {
        // create group data
        $user = User::factory()->create();
        $data = [
            'name' => 'Test Group',
            'owner_id' => $user->id,
            'member_limit' => 25,
            'player_limit' => 8,
        ];

        // ensure group does not exist
        $this->assertDatabaseMissing('groups', ['name' => $data['name']]);

        // try to create the group
        $group = $this->service->create(ValidatedGroupData::fromArray($data));

        // verify group exists in database with default limits (model overrides provided values)
        $this->assertDatabaseHas('groups', [
            'name' => $data['name'],
            'owner_id' => $data['owner_id'],
            'member_limit' => Group::INITIAL_MEMBER_LIMIT,
            'player_limit' => Group::INITIAL_PLAYER_LIMIT,
        ]);

        expect($group)->toBeInstanceOf(Group::class);
        expect($group->name)->toBe($data['name']);
        expect($group->owner_id)->toBe($data['owner_id']);
        expect($group->member_limit)->toBe(Group::INITIAL_MEMBER_LIMIT);
        expect($group->player_limit)->toBe(Group::INITIAL_PLAYER_LIMIT);
        expect(Str::isUlid((string)$group->ulid))->toBeTrue();
    });

    test('with minimal data', function () {
        // create group data with only required fields
        $user = User::factory()->create();
        $data = [
            'name' => 'Minimal Group',
            'owner_id' => $user->id,
        ];

        // try to create the group
        $group = $this->service->create(ValidatedGroupData::fromArray($data));

        // verify group exists with default limits
        expect($group->name)->toBe($data['name']);
        expect($group->owner_id)->toBe($data['owner_id']);
        expect($group->member_limit)->toBe(Group::INITIAL_MEMBER_LIMIT);
        expect($group->player_limit)->toBe(Group::INITIAL_PLAYER_LIMIT);
    });
});

describe('update a group', function () {
    test('with valid data', function () {
        // create existing group
        $group = Group::factory()->create([
            'name' => 'Old Name',
            'member_limit' => 20,
            'player_limit' => 5,
        ]);

        // data to update to
        $newUser = User::factory()->create();
        $data = ValidatedGroupData::fromArray([
            'name' => 'New Name',
            'owner_id' => $newUser->id,
            'member_limit' => 30,
            'player_limit' => 10,
        ]);

        // ensure updated group does not exist
        $this->assertDatabaseMissing('groups', [
            'name' => $data->name,
        ]);

        // try to update the group
        $updatedGroup = $this->service->update($group, $data);

        // verify updated group exists in database
        $this->assertDatabaseHas('groups', [
            'name' => $data->name,
            'owner_id' => $data->owner_id,
            'member_limit' => $data->member_limit,
            'player_limit' => $data->player_limit,
        ]);

        // verify returned group is the same instance
        expect($updatedGroup)->toBe($group);

        // verify updated data
        expect($group->name)->toBe($data->name);
        expect($group->owner_id)->toBe($data->owner_id);
        expect($group->member_limit)->toBe($data->member_limit);
        expect($group->player_limit)->toBe($data->player_limit);
    });

    test('updates with same values', function () {
        // create existing group
        $group = Group::factory()->create([
            'name' => 'Original Name',
            'member_limit' => 25,
            'player_limit' => 8,
        ]);

        // data to update to with same values
        $data = ValidatedGroupData::fromArray([
            'name' => 'Original Name',
            'owner_id' => $group->owner_id,
            'member_limit' => 25,
            'player_limit' => 8,
        ]);

        // try to update the group
        $updatedGroup = $this->service->update($group, $data);

        // verify returned group is the same instance
        expect($updatedGroup)->toBe($group);

        // verify data is unchanged
        expect($updatedGroup->toArray())->toBe($group->toArray());
    });
});

describe('delete a group', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // verify group exists in database
        $this->assertDatabaseHas('groups', ['id' => $group->id]);

        // try to delete the group
        $this->service->delete($group);

        // verify group is deleted from database
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    });
});

describe('add a member to a group', function () {
    test('with valid data', function () {
        // create a group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // data for new member
        $data = ValidatedMemberData::fromArray([
            'user_id' => $user->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        // ensure member does not exist
        $this->assertDatabaseMissing('members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        // try to add the member to the group
        $member = $this->service->addMember($group, $data);

        // verify member exists in database
        $this->assertDatabaseHas('members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $data->role->value,
        ]);

        expect($member)->toBeInstanceOf(Member::class);
        expect($member->group_id)->toBe($group->id);
        expect($member->user_id)->toBe($user->id);
    });
});

describe('remove a member from a group', function () {
    test('works', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // verify member exists
        $this->assertDatabaseHas('members', ['id' => $member->id]);

        // try to remove the member
        $this->service->removeMember($group, $member);

        // verify member is deleted
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    });
});

describe('add a player to a member in a group', function () {
    test('with valid data', function () {
        // create a group, member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // data for new player
        $data = ValidatedPlayerData::fromArray([
            'player_name' => 'Test Player',
        ]);

        // ensure player does not exist
        $this->assertDatabaseMissing('players', [
            'member_id' => $member->id,
            'player_name' => $data->player_name,
        ]);

        // try to add the player to the member
        $player = $this->service->addPlayer($group, $member, $data);

        // verify player exists in database
        $this->assertDatabaseHas('players', [
            'member_id' => $member->id,
            'player_name' => $data->player_name,
        ]);

        expect($player)->toBeInstanceOf(Player::class);
        expect($player->member_id)->toBe($member->id);
        expect($player->player_name)->toBe($data->player_name);
    });
});

describe('remove a player from a member in a group', function () {
    test('works', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // verify player exists
        $this->assertDatabaseHas('players', ['id' => $player->id]);

        // try to remove the player
        $this->service->removePlayer($group, $member, $player);

        // verify player is deleted
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });
});

describe('follow a team for a group', function () {
    test('with valid data', function () {
        // create a group, team, and season
        $group = Group::factory()->create();
        $team = Team::factory()->create();
        $season = Season::factory()->create();

        // data for follow
        $data = ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);

        // ensure follow does not exist
        $this->assertDatabaseMissing('follows', [
            'group_id' => $group->id,
        ]);

        // try to follow the team
        $follow = $this->service->followTeam($group, $data);

        // verify follow exists in database
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);

        expect($follow)->toBeInstanceOf(Follow::class);
        expect($follow->group_id)->toBe($group->id);
        expect($follow->team_id)->toBe($team->id);
        expect($follow->season_id)->toBe($season->id);
    });

    test('throws exception when group already follows a team', function () {
        // create a group that already follows a team
        $group = Group::factory()->create();
        Follow::factory()->create(['group_id' => $group->id]);

        // create new team and season
        $team = Team::factory()->create();
        $season = Season::factory()->create();

        $data = ValidatedFollowData::fromArray([
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);

        // expect exception
        expect(fn() => $this->service->followTeam($group, $data))
            ->toThrow(\Exception::class, 'This group is already following a team.');
    });
});

describe('remove follow from a group', function () {
    test('works', function () {
        // create a group with a follow
        $group = Group::factory()->create();
        $follow = Follow::factory()->create(['group_id' => $group->id]);

        // verify follow exists
        $this->assertDatabaseHas('follows', ['id' => $follow->id]);

        // try to remove the follow
        $this->service->removeFollow($group);

        // verify follow is deleted
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
    });

    test('does nothing when group has no follow', function () {
        // create a group without a follow
        $group = Group::factory()->create();

        // verify no follow exists
        expect($group->follow)->toBeNull();

        // try to remove follow (should not throw)
        $this->service->removeFollow($group);

        // still no follow
        expect($group->fresh()->follow)->toBeNull();
    });
});

describe('query groups', function () {
    test('returns query builder', function () {
        // try to query groups
        $query = $this->service->query([]);

        // verify returns query builder
        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
    });

    test('filters by owner_id', function () {
        // create users and groups
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Group::factory()->create(['owner_id' => $user1->id]);
        Group::factory()->create(['owner_id' => $user1->id]);
        Group::factory()->create(['owner_id' => $user2->id]);

        // query for user1's groups
        $query = $this->service->query(['owner_id' => $user1->id]);
        $results = $query->get();

        // verify only user1's groups are returned
        expect($results->count())->toBe(2);
        $results->each(function ($group) use ($user1) {
            expect($group->owner_id)->toBe($user1->id);
        });
    });
});

describe('find group by invite code', function () {
    test('returns group when invite code exists', function () {
        // create a group
        $group = Group::factory()->create();

        // try to find the group by invite code
        $foundGroup = $this->service->findByInviteCode($group->invite_code);

        // verify the correct group is returned
        expect($foundGroup)->toBeInstanceOf(Group::class);
        expect($foundGroup->id)->toBe($group->id);
        expect($foundGroup->invite_code)->toBe($group->invite_code);
    });

    test('returns null when invite code does not exist', function () {
        // try to find a group with a non-existent invite code
        $foundGroup = $this->service->findByInviteCode('nonexistentcode');

        // verify null is returned
        expect($foundGroup)->toBeNull();
    });
});

describe('is user already member', function () {
    test('returns true when user is a member', function () {
        // create a group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // add user as member
        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        // check if user is already a member
        $isMember = GroupService::isUserAlreadyMember($group, $user->id);

        // verify returns true
        expect($isMember)->toBeTrue();
    });

    test('returns false when user is not a member', function () {
        // create a group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // check if user is already a member (should not be)
        $isMember = GroupService::isUserAlreadyMember($group, $user->id);

        // verify returns false
        expect($isMember)->toBeFalse();
    });
});