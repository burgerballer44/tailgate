<?php

use App\Models\User;
use App\Models\UserStatus;
use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;

describe('activate', function () {
    test('a user can be activated', function () {
        $user = new User(['status' => 'some-status']);
        expect($user->status)->not->toBe(UserStatus::ACTIVE);

        $user->activate();

        expect($user->status)->toBe(UserStatus::ACTIVE);
    });
});

describe('getAccessibleGroups', function () {
    test('returns groups where user is owner', function () {
        $user = User::factory()->create();
        $ownedGroup = Group::factory()->create(['owner_id' => $user->id]);
        $otherGroup = Group::factory()->create();

        $groups = $user->getAccessibleGroups();

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($ownedGroup->id);
    });

    test('returns groups where user is approved member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED,
        ]);

        $groups = $user->getAccessibleGroups();

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
    });

    test('returns groups where user is pending member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        $groups = $user->getAccessibleGroups();

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
    });

    test('returns both owned and approved groups', function () {
        $user = User::factory()->create();
        $ownedGroup = Group::factory()->create(['owner_id' => $user->id]);
        $approvedGroup = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $approvedGroup->id,
            'status' => MemberStatus::APPROVED,
        ]);

        $groups = $user->getAccessibleGroups();

        expect($groups)->toHaveCount(2);
        expect($groups->pluck('id'))->toContain($ownedGroup->id, $approvedGroup->id);
    });
});

describe('isOwnerOf', function () {
    test('returns true when user is the owner of the group', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        expect($user->isOwnerOf($group))->toBeTrue();
    });

    test('returns false when user is not the owner of the group', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        expect($user->isOwnerOf($group))->toBeFalse();
    });
});

describe('getMembershipStatus', function () {
    test('returns the membership status when user is a member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED,
        ]);

        $groups = $user->getAccessibleGroups();
        $groupWithMembership = $groups->first();

        expect($user->getMembershipStatus($groupWithMembership))->toBe('Approved');
    });

    test('returns null when user is not a member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        expect($user->getMembershipStatus($group))->toBeNull();
    });
});

describe('canAccessGroup', function () {
    test('returns true when user is the owner', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        expect($user->canAccessGroup($group))->toBeTrue();
    });

    test('returns true when user is an approved member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED,
        ]);

        $groups = $user->getAccessibleGroups();
        $groupWithMembership = $groups->first();

        expect($user->canAccessGroup($groupWithMembership))->toBeTrue();
    });

    test('returns false when user is a pending member', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        $groups = $user->getAccessibleGroups();
        $groupWithMembership = $groups->first();

        expect($user->canAccessGroup($groupWithMembership))->toBeFalse();
    });

    test('returns false when user is not a member or owner', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        expect($user->canAccessGroup($group))->toBeFalse();
    });
});