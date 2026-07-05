<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\HtmlEntity;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

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

    test('returns true when group has multiple follows', function () {
        $group = Group::factory()->create(['follow_limit' => 3]);
        Follow::factory()->create(['group_id' => $group->id]);
        Follow::factory()->create(['group_id' => $group->id]);

        expect($group->fresh()->isFollowingTeam())->toBeTrue();
    });

    test('returns false when group has no follow', function () {
        $group = Group::factory()->create();

        expect($group->isFollowingTeam())->toBeFalse();
    });
});

describe('model defaults and accessors', function () {
    test('defaults follow_limit when not provided', function () {
        $group = Group::factory()->create([
            'follow_limit' => null,
        ]);

        expect($group->fresh()->follow_limit)->toBe(Group::INITIAL_FOLLOW_LIMIT);
    });

    test('follow_collection returns follows with teams', function () {
        $group = Group::factory()->create(['follow_limit' => 3]);
        $firstFollow = Follow::factory()->create(['group_id' => $group->id]);
        $secondFollow = Follow::factory()->create(['group_id' => $group->id]);

        $collection = $group->follow_collection;

        expect($collection)->toHaveCount(2);
        expect($collection->pluck('id')->all())
            ->toContain($firstFollow->id, $secondFollow->id);
        expect($collection->every(fn (Follow $follow) => $follow->relationLoaded('team')))->toBeTrue();
    });

    test('follow_collection uses loaded follows relation without extra queries', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        Follow::factory()->create(['group_id' => $group->id]);
        $group->load('follows.team');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $collection = $group->follow_collection;

        expect($collection)->toHaveCount(1);
        expect(DB::getQueryLog())->toBe([]);

        DB::disableQueryLog();
    });

    test('follow_html_entity returns red x when no follows exist', function () {
        $group = Group::factory()->create();

        expect($group->follow_html_entity->toHtml())->toBe(HtmlEntity::RED_X->entity());
    });

    test('follow_html_entity returns check mark when follows exist', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        Follow::factory()->create(['group_id' => $group->id]);

        expect($group->follow_html_entity->toHtml())->toBe(HtmlEntity::CHECK_MARK->entity());
    });

    test('stores enabled prediction policies as an array', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        expect($group->enabled_prediction_policies)->toBe(['group-unique-prediction']);
        expect($group->isPredictionPolicyEnabled('group-unique-prediction'))->toBeTrue();
        expect($group->isPredictionPolicyEnabled('season-active'))->toBeFalse();
    });

    test('enabled_prediction_policies_display returns labels for enabled policy keys', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [
                'group-unique-prediction',
                'minimum-lead-time-before-lock',
            ],
        ]);

        $display = $group->enabled_prediction_policies_display;

        expect($display)->toBeInstanceOf(HtmlString::class);
        expect($display->toHtml())->toContain('Unique group prediction');
        expect($display->toHtml())->toContain('Minimum lead time before lock');
    });

    test('enabled_prediction_policies_display returns none enabled when no policies are selected', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [],
        ]);

        expect($group->enabled_prediction_policies_display)->toBe('None enabled');
    });
});
