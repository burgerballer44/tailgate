<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\User;

beforeEach(function () {
    $this->user = signInAdminUser();
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
});