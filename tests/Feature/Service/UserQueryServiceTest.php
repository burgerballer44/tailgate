<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use App\Services\UserQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new UserQueryService;
});

describe('query users', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(Builder::class);
        expect($query->getModel())->toBeInstanceOf(User::class);
    });
});

describe('get accessible groups', function () {
    test('returns groups owned by user', function () {
        // create user
        $user = User::factory()->create();

        // create groups owned by user
        $ownedGroup1 = Group::factory()->create(['owner_id' => $user->id]);
        $ownedGroup2 = Group::factory()->create(['owner_id' => $user->id]);

        // create group owned by someone else
        $otherGroup = Group::factory()->create();

        // get accessible groups
        $groups = $this->service->getAccessibleGroups($user);

        // should return only owned groups
        expect($groups)->toHaveCount(2);
        expect($groups->pluck('id'))->toContain($ownedGroup1->id, $ownedGroup2->id);
        expect($groups->pluck('id'))->not->toContain($otherGroup->id);
    });

    test('returns groups where user is member', function () {
        // create user
        $user = User::factory()->create();

        // create group and add user as member
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        // create another group without user as member
        $otherGroup = Group::factory()->create();

        // get accessible groups
        $groups = $this->service->getAccessibleGroups($user);

        // should return only the group where user is member
        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
    });

    test('returns both owned and member groups', function () {
        // create user
        $user = User::factory()->create();

        // create owned group
        $ownedGroup = Group::factory()->create(['owner_id' => $user->id]);

        // create member group
        $memberGroup = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $memberGroup->id,
        ]);

        // create unrelated group
        $unrelatedGroup = Group::factory()->create();

        // get accessible groups
        $groups = $this->service->getAccessibleGroups($user);

        // should return both groups
        expect($groups)->toHaveCount(2);
        expect($groups->pluck('id'))->toContain($ownedGroup->id, $memberGroup->id);
        expect($groups->pluck('id'))->not->toContain($unrelatedGroup->id);
    });

    test('includes owner and member relationships', function () {
        // create user
        $user = User::factory()->create();

        // create group and add user as member
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        // get accessible groups
        $groups = $this->service->getAccessibleGroups($user);

        $group = $groups->first();

        // should include owner relationship
        expect($group->owner)->toBeInstanceOf(User::class);
        expect($group->owner->id)->toBe($group->getOriginal('owner_id'));

        // should include members relationship filtered to current user
        expect($group->members)->toHaveCount(1);
        expect($group->members->first()->id)->toBe($member->id);
        expect($group->members->first()->user_id)->toBe($user->id);
    });
});
