<?php

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\MemberCommandService;

beforeEach(function () {
    $this->service = new MemberCommandService(
        app(PlayerCommandInterface::class)
    );
});

describe('create member for group', function () {
    test('with valid data', function () {
        // create group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // member data
        $data = [
            'user_id' => $user->id,
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ];

        // ensure member does not exist
        $this->assertDatabaseMissing('members', ['user_id' => $user->id, 'group_id' => $group->id]);

        // create member
        $member = $this->service->createForGroup($group, ValidatedMemberData::fromArray($data));

        // verify member exists
        $this->assertDatabaseHas('members', ['user_id' => $user->id, 'group_id' => $group->id]);
        expect($member)->toBeInstanceOf(Member::class);
        expect($member->user_id)->toBe($user->id);
        expect($member->group_id)->toBe($group->id);

        // verify player was created
        $this->assertDatabaseHas('players', ['member_id' => $member->id, 'player_name' => $user->name]);
    });
});

describe('update member', function () {
    test('with valid data', function () {
        // create existing member
        $member = Member::factory()->create([
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::PENDING->value,
        ]);

        // update data
        $data = [
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::APPROVED,
        ];

        // update member
        $updatedMember = $this->service->update($member, ValidatedMemberData::fromArray($data));

        // verify updated
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        expect($updatedMember->role)->toBe(GroupRole::GROUP_ADMIN->value);
    });
});

describe('delete member', function () {
    test('removes member from database', function () {
        // create member
        $member = Member::factory()->create();

        // delete member
        $this->service->delete($member);

        // verify removed
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    });
});
