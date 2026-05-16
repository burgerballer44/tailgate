<?php

use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;

describe('isPending', function () {
    test('returns true for pending members', function () {
        $member = Member::factory()->create(['status' => MemberStatus::PENDING->value]);

        expect($member->isPending())->toBeTrue();
        expect($member->isApproved())->toBeFalse();
    });
});

describe('isApproved', function () {
    test('returns true for approved members', function () {
        $member = Member::factory()->create(['status' => MemberStatus::APPROVED->value]);

        expect($member->isApproved())->toBeTrue();
        expect($member->isPending())->toBeFalse();
    });
});

describe('isOwner', function () {
    test('returns true when member is the group owner', function () {
        $group = Group::factory()->create();
        $member = $group->members()->where('user_id', $group->owner_id)->first();

        expect($member->isOwner())->toBeTrue();
    });

    test('returns false when member is not the group owner', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        expect($member->isOwner())->toBeFalse();
    });
});

describe('canBeRemovedBy', function () {
    test('returns true for approved non-owner members when removed by the group owner', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $user = $group->owner;

        expect($member->canBeRemovedBy($user))->toBeTrue();
    });

    test('returns true for approved non-owner members when removed by a group admin', function () {
        $group = Group::factory()->create();
        $adminUser = User::factory()->create();

        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $adminUser->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        expect($member->canBeRemovedBy($adminUser))->toBeTrue();
    });

    test('returns false for approved non-owner members when removed by a regular group member', function () {
        $group = Group::factory()->create();
        $regularUser = User::factory()->create();

        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $regularUser->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        expect($member->canBeRemovedBy($regularUser))->toBeFalse();
    });

    test('returns false for pending members', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $user = $group->owner;

        expect($member->canBeRemovedBy($user))->toBeFalse();
    });

    test('returns false for owner members', function () {
        $group = Group::factory()->create();
        $member = $group->members()->where('user_id', $group->owner_id)->first();

        $user = $group->owner;

        expect($member->canBeRemovedBy($user))->toBeFalse();
    });

    test('returns false for users outside the group', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $user = User::factory()->create();

        expect($member->canBeRemovedBy($user))->toBeFalse();
    });
});
