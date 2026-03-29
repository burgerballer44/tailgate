<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use App\Services\GroupQueryService;

beforeEach(function () {
    $this->service = new GroupQueryService();
});

describe('query groups', function () {
    test('returns query builder', function () {
        $result = $this->service->query([]);

        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
        expect($result->getModel())->toBeInstanceOf(Group::class);
    });

    test('filters groups by query parameters', function () {
        // create test groups
        $group1 = Group::factory()->create(['name' => 'Group One']);
        $group2 = Group::factory()->create(['name' => 'Group Two']);

        // query with filter
        $result = $this->service->query(['name' => 'One']);

        $groups = $result->get();
        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group1->id);
    });
});

describe('find by invite code', function () {
    test('returns group when found', function () {
        $group = Group::factory()->create(['invite_code' => 'ABC123']);

        $result = $this->service->findByInviteCode('ABC123');

        expect($result)->toBeInstanceOf(Group::class);
        expect($result->id)->toBe($group->id);
    });

    test('returns null when not found', function () {
        $result = $this->service->findByInviteCode('NONEXISTENT');

        expect($result)->toBeNull();
    });
});

describe('is user already member', function () {
    test('returns true when user is member', function () {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        Member::factory()->create(['group_id' => $group->id, 'user_id' => $user->id]);
        $userId = $user->id;

        $result = $this->service->isUserAlreadyMember($group, $userId);

        expect($result)->toBeTrue();
    });

    test('returns false when user is not member', function () {
        $group = Group::factory()->create();
        $user = \App\Models\User::factory()->create();

        $result = $this->service->isUserAlreadyMember($group, $user->id);

        expect($result)->toBeFalse();
    });
});

describe('is group member limit reached', function () {
    test('returns true when limit reached', function () {
        $group = Group::factory()->create(['member_limit' => 1]);
        Member::factory()->create(['group_id' => $group->id]);

        $result = $this->service->isGroupMemberLimitReached($group);

        expect($result)->toBeTrue();
    });

    test('returns false when limit not reached', function () {
        $group = Group::factory()->create(['member_limit' => 3]);
        Member::factory()->create(['group_id' => $group->id]);

        $result = $this->service->isGroupMemberLimitReached($group);

        expect($result)->toBeFalse();
    });
});