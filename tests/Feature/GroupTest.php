<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('index', function () {
    test('works', function () {
        // create additional groups
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        // visit the index page
        $response = $this->get(route('groups.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.index');

        // assert data is passed to view
        $response->assertViewHas('groups');
        $response->assertViewHas('users');

        // verify groups are in the view data
        $viewGroups = $response->viewData('groups');
        expect($viewGroups)->toHaveCount(2);

        // verify users are collection
        $users = $response->viewData('users');
        expect($users)->toBeInstanceOf(Collection::class);
    });
});

describe('creating a group', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('groups.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.create');

        // assert data is passed to view
        $response->assertViewHas('users');
    });

    test('works', function () {
        // group data
        $user = User::factory()->create();
        $groupData = [
            'name' => 'Test Group',
            'owner_id' => $user->id,
        ];

        // there should be 0 groups in the db
        $this->assertDatabaseCount('groups', 0);

        // post the group data
        $response = $this->post(route('groups.store'), $groupData);

        // should redirect to index
        $response->assertRedirect(route('groups.index'));

        // there should be 1 group in the db
        $this->assertDatabaseCount('groups', 1);

        // verify group was created
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'owner_id' => $groupData['owner_id'],
        ]);
    });

    test('flashes success message on store', function () {
        // group data
        $user = User::factory()->create();
        $groupData = [
            'name' => 'Test Group',
            'owner_id' => $user->id,
        ];

        // post the group data
        $this->post(route('groups.store'), $groupData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group created successfully!');
    });
});

describe('viewing a group', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the show page
        $response = $this->get(route('groups.show', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.show');

        // assert group is passed to view
        $response->assertViewHas('group', $group);
    });
});

describe('updating group', function () {
    test('shows edit form', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the edit page
        $response = $this->get(route('groups.edit', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.edit');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('users');
    });

    test('updates a group', function () {
        // create a group
        $group = Group::factory()->create([
            'name' => 'Original Name',
        ]);

        // update data
        $updateData = [
            'name' => 'Updated Name',
        ];

        // patch the group data
        $response = $this->patch(route('groups.update', $group), $updateData);

        // should redirect to index
        $response->assertRedirect(route('groups.index'));

        // verify group was updated
        $group->refresh();
        expect($group->name)->toBe($updateData['name']);
    });

    test('flashes success message on update', function () {
        // create a group
        $group = Group::factory()->create();

        // update data
        $updateData = [
            'name' => 'Updated Name',
        ];

        // patch the group data
        $this->patch(route('groups.update', $group), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group updated successfully!');
    });
});

describe('deleting a group', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // there should be 1 group in the db
        $this->assertDatabaseCount('groups', 1);

        // delete the group
        $response = $this->delete(route('groups.destroy', $group));

        // should redirect to index
        $response->assertRedirect(route('groups.index'));

        // there should be 0 groups in the db
        $this->assertDatabaseCount('groups', 0);

        // verify group was deleted
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    });

    test('flashes success message on delete', function () {
        // create a group
        $group = Group::factory()->create();

        // delete the group
        $this->delete(route('groups.destroy', $group))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group deleted successfully!');
    });
});