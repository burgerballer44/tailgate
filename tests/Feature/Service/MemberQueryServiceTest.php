<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\MemberQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = new MemberQueryService;
    Member::truncate();
});

describe('query members', function () {
    test('returns query builder', function () {
        $result = $this->service->query([]);

        expect($result)->toBeInstanceOf(Builder::class);
        expect($result->getModel())->toBeInstanceOf(Member::class);
    });

    test('getApprovedMembersForGroup returns only approved members ordered by user id with user and player counts', function () {
        $ownerUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $ownerUser->id]);
        $approvedUserOne = User::factory()->create();
        $approvedUserTwo = User::factory()->create();
        $pendingUser = User::factory()->create();

        $ownerMember = $group->members()->where('user_id', $ownerUser->id)->firstOrFail();

        $memberTwo = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $approvedUserTwo->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $memberOne = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $approvedUserOne->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $pendingUser->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $ownerMember->players()->create(['player_name' => 'Owner Player']);

        $memberOne->players()->createMany([
            ['player_name' => 'One Alpha'],
            ['player_name' => 'One Beta'],
        ]);

        $memberTwo->players()->create(['player_name' => 'Two Alpha']);

        $result = $this->service->getApprovedMembersForGroup($group);

        expect($result)->toHaveCount(3);
        expect($result->pluck('id')->all())->toBe([$ownerMember->id, $memberOne->id, $memberTwo->id]);
        expect($result->first()->relationLoaded('user'))->toBeTrue();
        expect($result->first()->players_count)->toBe(1);
        expect($result->get(1)->players_count)->toBe(2);
        expect($result->last()->players_count)->toBe(1);
    });

    test('findApprovedMemberForGroupAndUser returns approved member for group and user', function () {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Member::factory()->create([
            'group_id' => Group::factory()->create()->id,
            'user_id' => $user->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $result = $this->service->findApprovedMemberForGroupAndUser($group, $user);

        expect($result->id)->toBe($approvedMember->id);
    });

    test('findApprovedMemberForGroupAndUser throws when no approved member exists', function () {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->service->findApprovedMemberForGroupAndUser($group, $user);
    })->throws(ModelNotFoundException::class);

    test('getApprovedMembershipsForUserWithQuickPredictionRelations returns only approved memberships with required relations', function () {
        $user = User::factory()->create();

        $approvedMember = Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $otherUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $otherUser->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $result = $this->service->getApprovedMembershipsForUserWithQuickPredictionRelations($user);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($approvedMember->id);
        expect($result->first()->relationLoaded('players'))->toBeTrue();
        expect($result->first()->relationLoaded('group'))->toBeTrue();
        expect($result->first()->group?->relationLoaded('follows'))->toBeTrue();
    });
});
