<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

describe('isAdminOrOwner', function () {
    test('returns true for group owner', function () {
        $group = Group::factory()->create();

        expect($group->isAdminOrOwner($group->owner))->toBeTrue();
    });

    test('returns true for admin member', function () {
        $group = Group::factory()->create();
        $adminUser = User::factory()->create();
        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $adminUser->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        expect($group->isAdminOrOwner($adminUser))->toBeTrue();
    });

    test('returns false for regular member', function () {
        $group = Group::factory()->create();
        $memberUser = User::factory()->create();
        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'role' => GroupRole::GROUP_MEMBER->value, // Assuming GROUP_MEMBER exists
        ]);

        expect($group->isAdminOrOwner($memberUser))->toBeFalse();
    });

    test('returns false for non-member', function () {
        $group = Group::factory()->create();
        $nonMemberUser = User::factory()->create();

        expect($group->isAdminOrOwner($nonMemberUser))->toBeFalse();
    });

    test('uses loaded members collection when available', function () {
        $group = Group::factory()->create();
        $adminUser = User::factory()->create();

        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $adminUser->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        $group->load('members');

        DB::flushQueryLog();
        DB::enableQueryLog();

        expect($group->isAdminOrOwner($adminUser))->toBeTrue();
        expect(DB::getQueryLog())->toBe([]);

        DB::disableQueryLog();
    });
});

describe('isFollowingTeam', function () {
    test('returns true when group has a follow', function () {
        $follow = Follow::factory()->create();

        expect($follow->group->isFollowingTeam())->toBeTrue();
    });

    test('returns false when group has no follow', function () {
        $group = Group::factory()->create();

        expect($group->isFollowingTeam())->toBeFalse();
    });
});
