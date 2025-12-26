<?php

use App\Models\Group;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('create', function () {
    test('shows create form', function () {
        $response = $this->get(route('groups.create'));

        $response->assertOk();
        $response->assertViewIs('groups.create');
    });
});

describe('store', function () {
    test('creates a group', function () {
        $groupData = [
            'name' => 'Test Group',
        ];

        $this->assertDatabaseCount('groups', 0);

        $response = $this->post(route('groups.store'), $groupData);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('groups', 1);
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'owner_id' => $this->user->id,
        ]);
    });

    test('flashes success message with invite code', function () {
        $groupData = [
            'name' => 'Test Group',
        ];

        $this->post(route('groups.store'), $groupData)->assertRedirect();

        expect(session('alert')['message'])->toContain('Group created successfully! Invite code:');
    });
});

describe('join', function () {
    test('shows join form', function () {
        $response = $this->get(route('groups.join'));

        $response->assertOk();
        $response->assertViewIs('groups.join');
    });
});

describe('requestJoin', function () {
    test('joins group with valid invite code', function () {
        $group = Group::factory()->create();

        $this->assertDatabaseCount('members', 1); // owner member

        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('members', 2);
        $this->assertDatabaseHas('members', [
            'user_id' => $this->user->id,
            'group_id' => $group->id,
        ]);
    });

    test('flashes success message on join', function () {
        $group = Group::factory()->create();

        $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ])->assertRedirect();

        expect(session('alert')['message'])->toBe('Successfully joined the group!');
    });

    test('fails with invalid invite code', function () {
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => 'invalid',
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('Invalid invite code.');
    });

    test('fails with missing invite code', function () {
        $response = $this->post(route('groups.request-join'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors('invite_code');
    });

    test('fails if already a member', function () {
        $group = Group::factory()->create();

        // Join once
        $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        // Try to join again
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('You are already a member of this group.');
    });
});