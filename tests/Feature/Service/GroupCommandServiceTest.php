<?php

use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Follow;
use App\Models\Group;
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
        ]);
        expect($follow)->toBeInstanceOf(Follow::class);
    });
});

describe('remove follow', function () {
    test('removes follow relationship', function () {
        // create follow
        $follow = Follow::factory()->create();

        // remove follow
        $this->service->removeFollow($follow->group);

        // verify removed
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
    });
});
