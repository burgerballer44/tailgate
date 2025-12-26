<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInAdminUser();
});

describe('index', function () {
    test('works', function () {
        // create additional groups
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        // visit the index page
        $response = $this->get(route('admin.groups.index'));

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

    test('groups can be filtered by owner', function () {
        // create 2 users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // create 2 groups owned by user1
        Group::factory()->create(['owner_id' => $user1->id]);
        Group::factory()->create(['owner_id' => $user1->id]);

        // create 1 group owned by user2
        Group::factory()->create(['owner_id' => $user2->id]);

        // get the groups owned by user1 only
        $response = $this->get(route('admin.groups.index') . '?owner_id=' . $user1->id);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.index');

        // assert groups are filtered
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        expect($groups->count())->toBe(2);
    });

    test('groups returns empty when owner filter matches nothing', function () {
        // create a group owned by the signed-in user
        Group::factory()->create(['owner_id' => $this->user->id]);

        // create a user not owning any groups
        $user = User::factory()->create();

        // search for groups owned by this user
        $response = $this->get(route('admin.groups.index') . '?owner_id=' . $user->id);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.index');

        // assert groups are filtered
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        expect($groups->count())->toBe(0);
    });

    test('groups can be filtered by q for name', function () {
        // thing to find
        $q = 'FindMe';

        // create a group
        $group = Group::factory()->create(['name' => $q]);
        $differentGroupToNotFind = Group::factory()->create(['name' => 'somethingelse']);

        // get the group
        $response = $this->get(route('admin.groups.index') . '?q=' . $q);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.index');

        // assert groups are filtered
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        expect($groups->count())->toBe(1);
    });

    test('groups can be filtered by q for invite_code', function () {
        // thing to find
        $q = 'FindMe';

        // create a group
        $group = Group::factory()->create();
        $group->invite_code = $q;
        $group->save();

        $differentGroupToNotFind = Group::factory()->create();
        $differentGroupToNotFind->invite_code = 'somethingelse';
        $differentGroupToNotFind->save();

        // get the group
        $response = $this->get(route('admin.groups.index') . '?q=' . $q);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.groups.index');

        // assert groups are filtered
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        expect($groups->count())->toBe(1);
    });
});

describe('creating a group', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('admin.groups.create'));

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
        $response = $this->post(route('admin.groups.store'), $groupData);

        // should redirect to index
        $response->assertRedirect(route('admin.groups.index'));

        // there should be 1 group in the db
        $this->assertDatabaseCount('groups', 1);

        // verify group was created
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'owner_id' => $user->id,
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
        $this->post(route('admin.groups.store'), $groupData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group created successfully!');
    });
});

describe('viewing a group', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the show page
        $response = $this->get(route('admin.groups.show', $group));

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
        $response = $this->get(route('admin.groups.edit', $group));

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
            'owner_id' => $group->owner->id,
        ];

        // patch the group data
        $response = $this->patch(route('admin.groups.update', $group), $updateData);

        // should redirect to index
        $response->assertRedirect(route('admin.groups.index'));

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
            'owner_id' => $group->owner->id,
        ];

        // patch the group data
        $this->patch(route('admin.groups.update', $group), $updateData)->assertRedirect();

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
        $response = $this->delete(route('admin.groups.destroy', $group));

        // should redirect to index
        $response->assertRedirect(route('admin.groups.index'));

        // there should be 0 groups in the db
        $this->assertDatabaseCount('groups', 0);

        // verify group was deleted
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    });

    test('flashes success message on delete', function () {
        // create a group
        $group = Group::factory()->create();

        // delete the group
        $this->delete(route('admin.groups.destroy', $group))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group deleted successfully!');
    });
});