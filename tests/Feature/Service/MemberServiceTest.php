<?php

use App\Models\Member;
use App\Models\Group;
use App\Models\User;
use App\Models\GroupRole;
use App\Services\MemberService;
use App\DTO\ValidatedMemberData;

beforeEach(function () {
    $this->service = new MemberService();
});

describe('create a member for group', function () {
    test('with valid data', function () {
        // create group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // member data
        $data = ValidatedMemberData::fromArray([
            'user_id' => $user->id,
            'role' => GroupRole::GROUP_MEMBER,
        ]);

        // ensure member does not exist
        $this->assertDatabaseMissing('members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        // try to create the member
        $member = $this->service->createForGroup($group, $data);

        // verify member exists in database
        $this->assertDatabaseHas('members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $data->role->value,
        ]);

        expect($member)->toBeInstanceOf(Member::class);
        expect($member->group_id)->toBe($group->id);
        expect($member->user_id)->toBe($user->id);
        expect($member->role)->toBe($data->role->value);
    });

    test('with default role when role not provided', function () {
        // create group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        // member data without role
        $data = ValidatedMemberData::fromArray([
            'user_id' => $user->id,
        ]);

        // try to create the member
        $member = $this->service->createForGroup($group, $data);

        // verify member has default role
        expect($member->role)->toBe(GroupRole::GROUP_MEMBER->value);
    });
});

describe('update a member', function () {
    test('with valid data', function () {
        // create existing member
        $member = Member::factory()->create([
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        // data to update to
        $data = ValidatedMemberData::fromArray([
            'role' => GroupRole::GROUP_ADMIN,
        ]);

        // try to update the member
        $updatedMember = $this->service->update($member, $data);

        // verify updated member in database
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'role' => $data->role->value,
        ]);

        // verify returned member is the same instance
        expect($updatedMember)->toBe($member);

        // verify updated data
        expect($member->role)->toBe($data->role->value);
    });

    test('does not update when role is null', function () {
        // create existing member
        $member = Member::factory()->create([
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        // data with null role
        $data = ValidatedMemberData::fromArray([]);

        // try to update the member
        $updatedMember = $this->service->update($member, $data);

        // verify role unchanged
        expect($updatedMember->role)->toBe(GroupRole::GROUP_MEMBER->value);
    });
});

describe('delete a member', function () {
    test('works when not the last admin', function () {
        // create group with two admins
        $group = Group::factory()->create();
        $admin1 = Member::factory()->create(['group_id' => $group->id, 'role' => GroupRole::GROUP_ADMIN->value]);
        $admin2 = Member::factory()->create(['group_id' => $group->id, 'role' => GroupRole::GROUP_ADMIN->value]);

        // verify member exists
        $this->assertDatabaseHas('members', ['id' => $admin1->id]);

        // try to delete the member
        $this->service->delete($admin1);

        // verify member is deleted
        $this->assertDatabaseMissing('members', ['id' => $admin1->id]);
    });

    test('throws exception when deleting the last admin', function () {
        // create a group (owner is auto-added as admin)
        $group = Group::factory()->create();

        // remove the owner member to have no admins
        $group->ownerMember->delete();

        // create one admin
        $admin = Member::factory()->create(['group_id' => $group->id, 'role' => GroupRole::GROUP_ADMIN->value]);

        // try to delete the last admin
        expect(fn() => $this->service->delete($admin))
            ->toThrow(\Exception::class, 'Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.');
    });
});

describe('query members', function () {
    test('returns query builder', function () {
        // try to query members
        $query = $this->service->query([]);

        // verify returns query builder
        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
    });
});