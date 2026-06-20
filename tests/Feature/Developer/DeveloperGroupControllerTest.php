<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('works', function () {
        // create additional groups
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        // visit the index page
        $response = $this->get(route('developer.groups.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.index');

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
        $response = $this->get(route('developer.groups.index').'?owner_id='.$user1->id);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.index');

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
        $response = $this->get(route('developer.groups.index').'?owner_id='.$user->id);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.index');

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
        $response = $this->get(route('developer.groups.index').'?q='.$q);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.index');

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
        $response = $this->get(route('developer.groups.index').'?q='.$q);

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.index');

        // assert groups are filtered
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        expect($groups->count())->toBe(1);
    });
});

describe('creating a group', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('developer.groups.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.create');

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
        $response = $this->post(route('developer.groups.store'), $groupData);

        // should redirect to index
        $response->assertRedirect(route('developer.groups.index'));

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
        $this->post(route('developer.groups.store'), $groupData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group created successfully!');
    });
});

describe('viewing a group', function () {
    test('works', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the show page
        $response = $this->get(route('developer.groups.show', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.show');

        // assert group is passed to view
        $response->assertViewHas('group', $group);
    });

    test('details tab shows enabled group rules', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [
                'group-unique-prediction',
                'minimum-lead-time-before-lock',
            ],
        ]);

        $response = $this->get(route('developer.groups.show', $group));

        $response->assertOk();
        $response->assertSee('Rules');
        $response->assertSee('Unique group prediction');
        $response->assertSee('When enabled for a group, only one prediction for a game may exist within that group.');
        $response->assertSee('Minimum lead time before lock');
        $response->assertSee('Predictions must be submitted at least 30 minutes before the scheduled game start time.');
    });

    test('players tab action links resolve nested route params', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        $response = $this->get(route('developer.groups.show', ['group' => $group, 'tab' => 'players']));

        $response->assertOk();
        $response->assertSee(
            route('developer.groups.members.players.show', [$group, $member, $player]),
            false
        );
    });
});

describe('updating group', function () {
    test('shows edit form', function () {
        // create a group
        $group = Group::factory()->create();

        // visit the edit page
        $response = $this->get(route('developer.groups.edit', $group));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.groups.edit');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('users');
        $response->assertViewHas('groupPolicies');

        $groupPolicies = $response->viewData('groupPolicies');
        $groupPolicyKeys = $groupPolicies->map(fn ($policy) => $policy->key())->all();

        expect($groupPolicies)->toBeInstanceOf(Collection::class);
        expect($groupPolicyKeys)->toContain('group-unique-prediction');
        expect($groupPolicyKeys)->toContain('minimum-lead-time-before-lock');
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
            'enabled_prediction_policies' => [
                'group-unique-prediction',
                'minimum-lead-time-before-lock',
            ],
        ];

        // patch the group data
        $response = $this->patch(route('developer.groups.update', $group), $updateData);

        // should redirect to show
        $response->assertRedirect(route('developer.groups.show', $group));

        // verify group was updated
        $group->refresh();
        expect($group->name)->toBe($updateData['name']);
        expect($group->enabled_prediction_policies)->toBe($updateData['enabled_prediction_policies']);
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
        $this->patch(route('developer.groups.update', $group), $updateData)->assertRedirect();

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
        $response = $this->delete(route('developer.groups.destroy', $group));

        // should redirect to index
        $response->assertRedirect(route('developer.groups.index'));

        // there should be 0 groups in the db
        $this->assertDatabaseCount('groups', 0);

        // verify group was deleted
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    });

    test('flashes success message on delete', function () {
        // create a group
        $group = Group::factory()->create();

        // delete the group
        $this->delete(route('developer.groups.destroy', $group))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Group deleted successfully!');
    });
});

describe('follow team', function () {
    test('shows follow team form', function () {
        $group = Group::factory()->create();
        $team = Team::factory()->create([
            'organization' => 'North Carolina',
            'designation' => 'Tar Heels',
            'abbreviation' => 'UNC',
        ]);

        $response = $this->get(route('developer.groups.follow-team.create', $group));

        $response->assertOk();
        $response->assertViewIs('developer.groups.follow-team');
        $response->assertViewHas(['group', 'teams']);
        $response->assertSee($team->display_name);
    });

    test('follows multiple teams up to follow limit', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $this->post(route('developer.groups.follow-team', $group), [
            'team_id' => $firstTeam->id,
        ])->assertRedirect(route('developer.groups.show', $group));

        $this->post(route('developer.groups.follow-team', $group), [
            'team_id' => $secondTeam->id,
            'sport' => Sport::FOOTBALL->value,
        ])->assertRedirect(route('developer.groups.show', $group));

        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $firstTeam->id,
            'sport' => null,
        ]);

        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $secondTeam->id,
            'sport' => Sport::FOOTBALL->value,
        ]);
    });

    test('rejects follow when follow limit is reached', function () {
        $group = Group::factory()->create(['follow_limit' => 1]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();

        $this->post(route('developer.groups.follow-team', $group), [
            'team_id' => $firstTeam->id,
        ])->assertRedirect(route('developer.groups.show', $group));

        $response = $this->from(route('developer.groups.follow-team.create', $group))
            ->post(route('developer.groups.follow-team', $group), [
                'team_id' => $secondTeam->id,
            ]);

        $response->assertRedirect(route('developer.groups.follow-team.create', $group));
        expect(session('alert')['message'])->toBe('This group has reached its follow limit.');
    });
});

describe('remove follow', function () {
    test('removes only the selected follow', function () {
        $group = Group::factory()->create(['follow_limit' => 2]);
        $follow = Follow::factory()->create(['group_id' => $group->id]);
        $otherFollow = Follow::factory()->create(['group_id' => $group->id]);

        $response = $this->delete(route('developer.groups.follow.destroy', [$group, $follow]));

        $response->assertRedirect(route('developer.groups.show', $group));
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
        $this->assertDatabaseHas('follows', ['id' => $otherFollow->id]);
    });
});
