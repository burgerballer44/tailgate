<?php

use App\Models\Group;
use App\Models\Enums\GroupRole;
use App\Models\Member;
use App\Models\Enums\MemberStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Symfony\Component\Uid\Ulid;

describe('route binding and identifiers', function () {
    test('uses ulid as route key name', function () {
        expect((new Member)->getRouteKeyName())->toBe('ulid');
    });

    test('generates ulid on create', function () {
        $member = Member::factory()->create();

        expect($member->ulid)->not->toBeNull();
        expect($member->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $member->ulid)->toHaveLength(26);
    });
});

describe('casts', function () {
    test('casts left_at to a datetime instance', function () {
        $member = Member::factory()->create([
            'left_at' => '2026-01-01 12:30:00',
        ])->refresh();

        expect($member->left_at)->toBeInstanceOf(Carbon::class);
    });
});

describe('relationships', function () {
    test('players returns has many relationship', function () {
        $relation = (new Member)->players();

        expect($relation)->toBeInstanceOf(HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(App\Models\Player::class);
    });

    test('user returns belongs to relationship', function () {
        $relation = (new Member)->user();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(User::class);
    });

    test('group returns belongs to relationship', function () {
        $relation = (new Member)->group();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Group::class);
    });
});

describe('filter scope', function () {
    test('filters by user_id, group_id, and status', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $otherUser = User::factory()->create();

        $matching = Member::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $wrongStatus = Member::factory()->create([
            'user_id' => $otherUser->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $wrongUser = Member::factory()->create([
            'user_id' => User::factory()->create()->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $results = Member::query()->filter([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ])->get();

        expect($results->pluck('id')->all())->toContain($matching->id);
        expect($results->pluck('id')->all())->not->toContain($wrongStatus->id);
        expect($results->pluck('id')->all())->not->toContain($wrongUser->id);
    });
});

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

describe('isAdmin', function () {
    test('returns true for group admin role', function () {
        $member = Member::factory()->create([
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        expect($member->isAdmin())->toBeTrue();
    });

    test('returns false for regular group member role', function () {
        $member = Member::factory()->create([
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        expect($member->isAdmin())->toBeFalse();
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
