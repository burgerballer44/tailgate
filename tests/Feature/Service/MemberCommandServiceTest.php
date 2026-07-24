<?php

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\Enums\GroupRole;
use App\Models\Member;
use App\Models\Enums\MemberStatus;
use App\Models\User;
use App\Services\MemberCommandService;

beforeEach(function () {
    $this->service = new MemberCommandService;
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

        // verify player was not auto-created
        $this->assertDatabaseMissing('players', ['member_id' => $member->id]);
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

describe('reject member', function () {
    test('marks pending membership as rejected', function () {
        $member = Member::factory()->create([
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->service->reject($member);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => MemberStatus::REJECTED->value,
        ]);
        expect($member->fresh()?->left_at)->not->toBeNull();
    });
});

describe('remove member', function () {
    test('marks approved member as removed and preserves record', function () {
        $member = Member::factory()->create([
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->service->remove($member);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => MemberStatus::REMOVED->value,
        ]);
        expect($member->fresh()?->left_at)->not->toBeNull();
    });
});

describe('leave member', function () {
    test('marks approved member as left and preserves record', function () {
        $member = Member::factory()->create([
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->service->leave($member);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => MemberStatus::LEFT->value,
        ]);
        expect($member->fresh()?->left_at)->not->toBeNull();
    });
});
