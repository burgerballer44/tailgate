<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\User;

beforeEach(function () {
    $this->user = signInAdminUser();
});

describe('index', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the index page
        $response = $this->get(route('admin.groups.members.index', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.members.index');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('members');
    });
});

describe('creating a member', function () {
    test('shows create form', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the create page
        $response = $this->get(route('admin.groups.members.create', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.members.create');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
    });

    test('works', function () {
        // create a group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $memberData = [
            'user_id' => $user->id,
            'role' => 'Group-Member',
        ];

        // there should be 1 member in the db (owner)
        $this->assertDatabaseCount('members', 1);

        // post the member data
        $response = $this->post(route('admin.groups.members.store', $group), $memberData);

        // should redirect to index
        $response->assertRedirect(route('admin.groups.members.index', $group));

        // there should be 2 members in the db
        $this->assertDatabaseCount('members', 2);

        // verify member was created
        $this->assertDatabaseHas('members', [
            'user_id' => $memberData['user_id'],
            'group_id' => $group->id,
            'role' => $memberData['role'],
        ]);
    });

    test('flashes success message on store', function () {
        // create a group and user
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $memberData = [
            'user_id' => $user->id,
            'role' => 'Group-Member',
        ];

        // post the member data
        $this->post(route('admin.groups.members.store', $group), $memberData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Member added successfully!');
    });
});

describe('viewing a member', function () {
    test('works', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // visit the show page
        $response = $this->get(route('admin.groups.members.show', [$group, $member]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.members.show');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
    });
});

describe('updating member', function () {
    test('shows edit form', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // visit the edit page
        $response = $this->get(route('admin.groups.members.edit', [$group, $member]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.members.edit');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
    });

    test('updates a member', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id, 'role' => 'Group-Member']);

        // update data
        $updateData = [
            'role' => 'Group-Admin',
        ];

        // patch the member data
        $response = $this->patch(route('admin.groups.members.update', [$group, $member]), $updateData);

        // should redirect to index
        $response->assertRedirect(route('admin.groups.members.index', $group));

        // verify member was updated
        $member->refresh();
        expect($member->role)->toBe($updateData['role']);
    });

    test('flashes success message on update', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // update data
        $updateData = [
            'role' => 'Group-Admin',
        ];

        // patch the member data
        $this->patch(route('admin.groups.members.update', [$group, $member]), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Member updated successfully!');
    });
});

describe('deleting a member', function () {
    test('works', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // there should be 2 members in the db
        $this->assertDatabaseCount('members', 2);

        // delete the member
        $response = $this->delete(route('admin.groups.members.destroy', [$group, $member]));

        // should redirect to index
        $response->assertRedirect(route('admin.groups.members.index', $group));

        // there should be 1 member in the db
        $this->assertDatabaseCount('members', 1);

        // verify member was deleted
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    });

    test('flashes success message on delete', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // delete the member
        $this->delete(route('admin.groups.members.destroy', [$group, $member]))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Member removed successfully!');
    });
});