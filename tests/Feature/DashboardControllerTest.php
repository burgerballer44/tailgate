<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('works for authenticated user', function () {
        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('dashboard');

        // assert data is passed to view
        $response->assertViewHas('groups');
        $response->assertViewHas('user', $this->user);
    });

    test('shows user groups', function () {
        // create groups and add user as member
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group1->id,
        ]);

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group2->id,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are in view data
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(2);
        expect($groups->pluck('id'))->toContain($group1->id, $group2->id);
    });

    test('shows empty groups for user with no memberships', function () {
        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are empty
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(0);
    });

    test('does not show groups user is not member of', function () {
        // create a group not belonging to user
        $group = Group::factory()->create();

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert groups are empty
        $groups = $response->viewData('groups');
        expect($groups)->toHaveCount(0);
    });

    test('shows membership pending approval for pending memberships', function () {
        // create a group with pending membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert "Membership Pending Approval" is shown
        $response->assertSee('Membership Pending Approval');
    });

    test('does not show links for pending memberships', function () {
        // create a group with pending membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert no link to the group
        $response->assertDontSee('<a href="'.route('groups.show', $group).'">', false);
    });

    test('shows links for approved memberships', function () {
        // create a group with approved membership
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED,
        ]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert link to the group is present
        $response->assertSee('<a href="'.route('groups.show', $group).'">', false);
    });

    test('shows links for owned groups', function () {
        // create a group owned by the user
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        // visit the dashboard
        $response = $this->get(route('dashboard'));

        // assert successful response
        $response->assertOk();

        // assert link to the group is present
        $response->assertSee('<a href="'.route('groups.show', $group).'">', false);
    });
});
