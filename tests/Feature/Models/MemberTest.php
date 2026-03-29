<?php

use App\Models\Group;
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
    test('returns true for approved non-owner members', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $user = User::factory()->create();

        expect($member->canBeRemovedBy($user))->toBeTrue();
    });

    test('returns false for pending members', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $user = User::factory()->create();

        expect($member->canBeRemovedBy($user))->toBeFalse();
    });

    test('returns false for owner members', function () {
        $group = Group::factory()->create();
        $member = $group->members()->where('user_id', $group->owner_id)->first();

        $user = User::factory()->create();

        expect($member->canBeRemovedBy($user))->toBeFalse();
    });
});
