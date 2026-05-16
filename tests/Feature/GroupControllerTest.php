<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Season;
use App\Models\Team;

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
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::PENDING->value,
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

        // join once
        $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        // try to join again
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('You are already a member of this group.');
    });

    test('fails if member limit reached', function () {
        $group = Group::factory()->create();

        // set member limit to 1 (owner is already a member)
        $group->update(['member_limit' => 1]);

        // try to join
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('Group member limit reached.');
    });
});

describe('show', function () {
    test('shows group details for member', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $response = $this->get(route('groups.show', $group));

        $response->assertOk();
        $response->assertViewIs('groups.show');
        $response->assertViewHas('group', $group);
    });

    test('denies access to non-members', function () {
        $group = Group::factory()->create();

        $response = $this->get(route('groups.show', $group));

        $response->assertForbidden();
    });

    test('denies access to pending members', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::PENDING->value,
        ]);
        $group = $member->group;

        $response = $this->get(route('groups.show', $group));

        $response->assertForbidden();
    });
});

describe('edit', function () {
    test('shows edit form for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertViewIs('groups.edit');
        $response->assertViewHas('group', $group);
    });

    test('shows edit form for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertViewIs('groups.edit');
    });

    test('denies access to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertForbidden();
    });

    test('denies access to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertForbidden();
    });
});

describe('update', function () {
    test('updates group for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'name' => 'Old Name']);

        $response = $this->patch(route('groups.update', $group), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->name)->toBe('New Name');
        expect($group->owner_id)->toBe($this->user->id); // owner should not change
    });

    test('updates group for admin', function () {
        $group = Group::factory()->create(['name' => 'Old Name']);
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->patch(route('groups.update', $group), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->name)->toBe('New Name');
    });

    test('flashes success message on update', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $this->patch(route('groups.update', $group), ['name' => 'Updated Name'])->assertRedirect();

        expect(session('alert')['message'])->toBe('Group updated successfully!');
    });

    test('denies update to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->patch(route('groups.update', $group), ['name' => 'New Name']);

        $response->assertForbidden();
    });
});

describe('approveMember', function () {
    test('approves pending member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::APPROVED->value);
    });

    test('approves pending member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::APPROVED->value);
    });

    test('denies approval to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('denies approval to non-members', function () {
        $group = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('flashes success message on approval', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->post(route('groups.approve-member', [$group, $pendingMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Member approved successfully!');
    });

    test('returns 404 when trying to approve non-pending member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $approvedMember]));

        $response->assertNotFound();
    });

    test('returns 404 when member does not belong to group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertNotFound();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::PENDING->value);
    });
});

describe('rejectMember', function () {
    test('rejects pending member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $pendingMember->id]);
    });

    test('rejects pending member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $pendingMember->id]);
    });

    test('denies rejection to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('denies rejection to non-members', function () {
        $group = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('flashes success message on rejection', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->post(route('groups.reject-member', [$group, $pendingMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Join request rejected.');
    });

    test('denies rejection to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('returns 404 when trying to reject non-pending member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $approvedMember]));

        $response->assertNotFound();
    });

    test('returns 404 when rejecting member from a different group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertNotFound();
        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);
    });
});

describe('removeMember', function () {
    test('removes approved member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $approvedMember->id]);
    });

    test('removes approved member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $approvedMember->id]);
    });

    test('denies removal to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertForbidden();
    });

    test('denies removal of owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $ownerMember = $group->members()->where('user_id', $group->owner_id)->first();

        $response = $this->delete(route('groups.remove-member', [$group, $ownerMember]));

        $response->assertForbidden();
    });

    test('allows removal of admin if not owner', function () {
        $group = Group::factory()->create();
        $adminMember = Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $adminMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $adminMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $adminMember->id]);
    });

    test('flashes success message on removal', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->delete(route('groups.remove-member', [$group, $approvedMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Member removed from group.');
    });

    test('returns 404 when trying to remove non-approved member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $pendingMember]));

        $response->assertNotFound();
    });

    test('returns 404 when removing member from a different group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $approvedMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertNotFound();
        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);
    });
});

describe('createFollowTeam', function () {
    test('shows follow team form for admin', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $team = Team::factory()->create();
        $season = Season::factory()->create();

        $response = $this->get(route('groups.follow-team.create', $group));

        $response->assertOk();
        $response->assertViewIs('groups.follow-team');
        $response->assertViewHas(['group', 'teams', 'seasons']);
    });

    test('denies access to non-admin', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        $response = $this->get(route('groups.follow-team.create', $group));

        $response->assertForbidden();
    });
});

describe('followTeam', function () {
    test('follows team successfully', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $team = Team::factory()->create();
        $season = Season::factory()->active()->create();

        $this->assertDatabaseMissing('follows', ['group_id' => $group->id]);

        $response = $this->post(route('groups.follow-team', $group), [
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);
        expect(session('alert')['message'])->toBe('Team followed successfully!');
    });
});

describe('removeFollow', function () {
    test('removes follow successfully', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $team = Team::factory()->create();
        $season = Season::factory()->create();
        $follow = Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $team->id,
            'season_id' => $season->id,
        ]);

        $this->assertDatabaseHas('follows', ['id' => $follow->id]);

        $response = $this->delete(route('groups.follow.destroy', [$group, $follow]));

        $response->assertRedirect(route('groups.show', $group));
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
        expect(session('alert')['message'])->toBe('Follow removed successfully!');
    });

    test('returns 404 when follow does not belong to group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $groupFollow = Follow::factory()->create(['group_id' => $group->id]);

        $otherGroup = Group::factory()->create();
        $otherFollow = Follow::factory()->create(['group_id' => $otherGroup->id]);

        $response = $this->delete(route('groups.follow.destroy', [$group, $otherFollow]));

        $response->assertNotFound();
        $this->assertDatabaseHas('follows', ['id' => $groupFollow->id]);
        $this->assertDatabaseHas('follows', ['id' => $otherFollow->id]);
    });
});
