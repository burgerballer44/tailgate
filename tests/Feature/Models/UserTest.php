<?php

use App\Models\Enums\HtmlEntity;
use App\Models\Enums\MemberStatus;
use App\Models\Enums\UserRole;
use App\Models\Enums\UserStatus;
use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use App\Services\UserQueryService;
use Illuminate\Support\HtmlString;
use Symfony\Component\Uid\Ulid;

describe('activate', function () {
    test('a user can be activated', function () {
        $user = new User(['status' => 'some-status']);
        expect($user->status)->not->toBe(UserStatus::ACTIVE);

        $user->activate();

        expect($user->status)->toBe(UserStatus::ACTIVE);
    });
});

describe('hasPassword', function () {
    test('returns true when password is present', function () {
        $user = new User(['password' => 'hashed-value']);

        expect($user->hasPassword())->toBeTrue();
    });

    test('returns false when password is null', function () {
        $user = new User(['password' => null]);

        expect($user->hasPassword())->toBeFalse();
    });
});

describe('route binding and identifiers', function () {
    test('uses ulid as the route key name', function () {
        expect((new User)->getRouteKeyName())->toBe('ulid');
    });

    test('generates a ulid when creating a user', function () {
        $user = User::factory()->create();

        expect($user->ulid)->not->toBeNull();
        expect($user->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $user->ulid)->toHaveLength(26);
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

describe('verified html entity accessor', function () {
    test('returns check icon when email is verified', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $entity = $user->verified_html_entity;

        expect($entity)->toBeInstanceOf(HtmlString::class);
        expect($entity->toHtml())->toBe(HtmlEntity::forBoolean(true)->entity());
    });

    test('returns x icon when email is not verified', function () {
        $user = User::factory()->unverified()->create();

        $entity = $user->verified_html_entity;

        expect($entity)->toBeInstanceOf(HtmlString::class);
        expect($entity->toHtml())->toBe(HtmlEntity::forBoolean(false)->entity());
    });
});

describe('filter scope', function () {
    test('filters by q against name and email', function () {
        $nameMatch = User::factory()->create([
            'name' => 'Taylor Searchable',
            'email' => 'other@example.com',
        ]);
        $emailMatch = User::factory()->create([
            'name' => 'Other Name',
            'email' => 'search-user@example.com',
        ]);
        $nonMatch = User::factory()->create([
            'name' => 'No Match',
            'email' => 'nomatch@example.com',
        ]);

        $users = User::query()->filter(['q' => 'search'])->get();

        expect($users->pluck('id')->all())->toContain($nameMatch->id);
        expect($users->pluck('id')->all())->toContain($emailMatch->id);
        expect($users->pluck('id')->all())->not->toContain($nonMatch->id);
    });

    test('filters by status and role', function () {
        $statusRoleMatch = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::DEVELOPER->value,
        ]);
        $statusMismatch = User::factory()->create([
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::DEVELOPER->value,
        ]);
        $roleMismatch = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        $users = User::query()->filter([
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::DEVELOPER->value,
        ])->get();

        expect($users->pluck('id')->all())->toContain($statusRoleMatch->id);
        expect($users->pluck('id')->all())->not->toContain($statusMismatch->id);
        expect($users->pluck('id')->all())->not->toContain($roleMismatch->id);
    });
});
