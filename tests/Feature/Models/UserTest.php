<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\UserQueryService;

describe('activate', function () {
    test('a user can be activated', function () {
        $user = new User(['status' => 'some-status']);
        expect($user->status)->not->toBe(UserStatus::ACTIVE);

        $user->activate();

        expect($user->status)->toBe(UserStatus::ACTIVE);
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

        $queryService = new UserQueryService;
        $groups = $queryService->getAccessibleGroups($user);
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

        $queryService = new UserQueryService;
        $groups = $queryService->getAccessibleGroups($user);
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

        $queryService = new UserQueryService;
        $groups = $queryService->getAccessibleGroups($user);
        $groupWithMembership = $groups->first();

        expect($user->canAccessGroup($groupWithMembership))->toBeFalse();
    });

    test('returns false when user is not a member or owner', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        expect($user->canAccessGroup($group))->toBeFalse();
    });
});
