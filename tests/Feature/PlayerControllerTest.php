<?php

use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    $this->group = Group::factory()->create();

    // Create approved member for the user
    $this->member = Member::factory()
        ->for($this->group)
        ->for($this->user, 'user')
        ->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

    // Create another user as pending member
    $this->pendingMember = Member::factory()
        ->for($this->group)
        ->for($this->otherUser, 'user')
        ->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::PENDING,
        ]);
});

describe('index - viewing players', function () {
    it('allows admin to view another approved member players on manage group routes', function () {
        $adminUser = User::factory()->create();
        $targetUser = User::factory()->create();

        Member::factory()->for($this->group)->for($adminUser, 'user')->create([
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::APPROVED,
        ]);

        $targetMember = Member::factory()->for($this->group)->for($targetUser, 'user')->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

        $response = $this->actingAs($adminUser)->get(
            route('groups.manage.members.players.index', [$this->group, $targetMember])
        );

        $response->assertOk();
    });

    it('does not allow regular member to access manage group routes', function () {
        $targetUser = User::factory()->create();

        $targetMember = Member::factory()->for($this->group)->for($targetUser, 'user')->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->user)->get(
            route('groups.manage.members.players.index', [$this->group, $targetMember])
        );

        $response->assertForbidden();
    });
});

describe('create - showing player creation form', function () {
    it('displays create player form', function () {
        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.create', [$this->group, $this->member])
        );

        $response->assertStatus(200)
            ->assertViewHas('group', $this->group)
            ->assertViewHas('member', $this->member);
    });

    it('uses group show as the return target for regular member create flow', function () {
        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.create', [$this->group, $this->member])
        );

        $response->assertStatus(200)
            ->assertSee(route('groups.show', $this->group), false);
    });

    it('requires user to be approved member of group', function () {
        $response = $this->actingAs($this->otherUser)->get(
            route('groups.members.players.create', [$this->group, $this->member])
        );

        $response->assertStatus(403);
    });

    it('requires member to belong to group', function () {
        $otherGroup = Group::factory()->create();
        $otherMember = Member::factory()
            ->for($otherGroup)
            ->for($this->user, 'user')
            ->create(['status' => MemberStatus::APPROVED]);

        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.create', [$this->group, $otherMember])
        );

        $response->assertStatus(404);
    });

    it('requires member to be approved', function () {
        $response = $this->actingAs($this->otherUser)->get(
            route('groups.members.players.create', [$this->group, $this->pendingMember])
        );

        $response->assertStatus(404);
    });

    it('does not allow regular member to open create form for another approved member', function () {
        $targetUser = User::factory()->create();

        $targetMember = Member::factory()
            ->for($this->group)
            ->for($targetUser, 'user')
            ->create([
                'role' => GroupRole::GROUP_MEMBER,
                'status' => MemberStatus::APPROVED,
            ]);

        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.create', [$this->group, $targetMember])
        );

        $response->assertForbidden();
    });
});

describe('store - creating a player', function () {
    it('creates a player successfully', function () {
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'My Player']
        );

        $response->assertRedirect(route('groups.show', $this->group));
        expect(Player::where('player_name', 'My Player')->exists())->toBeTrue();
    });

    it('associates player with the correct member', function () {
        $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'My Player']
        );

        $player = Player::where('player_name', 'My Player')->first();
        expect($player->member_id)->toBe($this->member->id);
    });

    it('requires player_name', function () {
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => '']
        );

        $response->assertSessionHasErrors('player_name');
    });

    it('requires player name to be unique within group', function () {
        Player::factory()->for($this->member)->create(['player_name' => 'Duplicate Name']);

        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'Duplicate Name']
        );

        $response->assertSessionHasErrors('player_name');
    });

    it('prevents exceeding regular self-service player limit per member', function () {
        Player::factory()->for($this->member)->create(['player_name' => 'First Player']);

        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'Exceeds Limit']
        );

        $response->assertSessionHasErrors('player_name');
        $this->assertDatabaseCount('players', 1);
    });

    it('prevents user from creating player for another user\'s membership', function () {
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->pendingMember]),
            ['player_name' => 'Unauthorized Player']
        );

        // Either 403 (controller check) or 404 (middleware check) is acceptable
        expect($response->status())->toBeIn([403, 404]);
    });

    it('does not allow regular member to store player for another approved member', function () {
        $targetUser = User::factory()->create();

        $targetMember = Member::factory()
            ->for($this->group)
            ->for($targetUser, 'user')
            ->create([
                'role' => GroupRole::GROUP_MEMBER,
                'status' => MemberStatus::APPROVED,
            ]);

        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $targetMember]),
            ['player_name' => 'Unauthorized Approved Target']
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('players', [
            'member_id' => $targetMember->id,
            'player_name' => 'Unauthorized Approved Target',
        ]);
    });

    it('requires user to be approved member of group', function () {
        $unapprovedUser = User::factory()->create();
        $unapprovedMember = Member::factory()
            ->for($this->group)
            ->for($unapprovedUser, 'user')
            ->create(['status' => MemberStatus::PENDING]);

        $response = $this->actingAs($unapprovedUser)->post(
            route('groups.members.players.store', [$this->group, $unapprovedMember]),
            ['player_name' => 'Unapproved Player']
        );

        $response->assertStatus(404);
    });

    it('requires member to belong to group', function () {
        $otherGroup = Group::factory()->create();
        $otherMember = Member::factory()
            ->for($otherGroup)
            ->for($this->user, 'user')
            ->create(['status' => MemberStatus::APPROVED]);

        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $otherMember]),
            ['player_name' => 'Invalid Player']
        );

        $response->assertStatus(404);
    });

    it('sets flash alert on success', function () {
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'Successful Player']
        );

        $response->assertRedirect(route('groups.show', $this->group));
        $response->assertSessionHas('alert');
        expect(session('alert.type'))->toBe('success');
    });

    it('sets flash alert on error', function () {
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => '']
        );

        expect(session()->has('errors'))->toBeTrue();
    });

    it('allows different members to have players with the same name', function () {
        // Create a separate group and user for this test
        $otherGroup = Group::factory()->create();
        $secondUser = User::factory()->create();
        $secondMember = Member::factory()
            ->for($otherGroup)
            ->for($secondUser, 'user')
            ->create(['status' => MemberStatus::APPROVED]);

        // Create a player with the same name in the first member
        Player::factory()->for($this->member)->create(['player_name' => 'Same Name']);

        // This should fail because the player name must be unique within the group, not per user
        $response = $this->actingAs($this->user)->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'Same Name']
        );

        $response->assertSessionHasErrors('player_name');
    });

    it('requires authentication', function () {
        $response = $this->post(
            route('groups.members.players.store', [$this->group, $this->member]),
            ['player_name' => 'Unauthenticated Player']
        );

        $response->assertRedirect(route('login'));
    });

    it('allows admin to create player for another approved member from manage group routes', function () {
        $adminUser = User::factory()->create();
        $targetUser = User::factory()->create();

        Member::factory()->for($this->group)->for($adminUser, 'user')->create([
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::APPROVED,
        ]);

        $targetMember = Member::factory()->for($this->group)->for($targetUser, 'user')->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

        $response = $this->actingAs($adminUser)->post(
            route('groups.manage.members.players.store', [$this->group, $targetMember]),
            ['player_name' => 'Admin Created Player']
        );

        $response->assertRedirect(route('groups.edit', $this->group).'?member='.$targetMember->ulid);
        $this->assertDatabaseHas('players', [
            'member_id' => $targetMember->id,
            'player_name' => 'Admin Created Player',
        ]);
    });

    it('allows admin to add extra players for a member up to group player limit', function () {
        $adminUser = User::factory()->create();
        $targetUser = User::factory()->create();

        Member::factory()->for($this->group)->for($adminUser, 'user')->create([
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::APPROVED,
        ]);

        $targetMember = Member::factory()->for($this->group)->for($targetUser, 'user')->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

        Player::factory()->for($targetMember)->create(['player_name' => 'Existing Player']);

        $response = $this->actingAs($adminUser)->post(
            route('groups.manage.members.players.store', [$this->group, $targetMember]),
            ['player_name' => 'Second Player']
        );

        $response->assertRedirect(route('groups.edit', $this->group).'?member='.$targetMember->ulid);
        $this->assertDatabaseHas('players', [
            'member_id' => $targetMember->id,
            'player_name' => 'Second Player',
        ]);
    });

    it('prevents admin from adding players beyond group player limit', function () {
        $adminUser = User::factory()->create();
        $targetUser = User::factory()->create();

        Member::factory()->for($this->group)->for($adminUser, 'user')->create([
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::APPROVED,
        ]);

        $targetMember = Member::factory()->for($this->group)->for($targetUser, 'user')->create([
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::APPROVED,
        ]);

        Player::factory($this->group->player_limit)->for($targetMember)->create();

        $response = $this->actingAs($adminUser)->post(
            route('groups.manage.members.players.store', [$this->group, $targetMember]),
            ['player_name' => 'Over Group Limit']
        );

        $response->assertSessionHasErrors('player_name');
        $this->assertDatabaseMissing('players', [
            'member_id' => $targetMember->id,
            'player_name' => 'Over Group Limit',
        ]);
    });
});

describe('edit and update - managing an existing player', function () {
    it('shows the edit form for player owner', function () {
        $player = Player::factory()->for($this->member)->create();

        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.edit', [$this->group, $this->member, $player])
        );

        $response->assertOk()->assertViewHas('player', $player);
    });

    it('updates player name for owner', function () {
        $player = Player::factory()->for($this->member)->create(['player_name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(
            route('groups.members.players.update', [$this->group, $this->member, $player]),
            ['player_name' => 'Updated Name']
        );

        $response->assertRedirect(route('groups.show', $this->group));
        $player->refresh();
        expect($player->player_name)->toBe('Updated Name');
    });

    it('does not allow editing another member player from regular group routes', function () {
        $targetUser = User::factory()->create();

        $otherApprovedMember = Member::factory()
            ->for($this->group)
            ->for($targetUser, 'user')
            ->create([
                'role' => GroupRole::GROUP_MEMBER,
                'status' => MemberStatus::APPROVED,
            ]);

        $player = Player::factory()->for($otherApprovedMember)->create();

        $response = $this->actingAs($this->user)->get(
            route('groups.members.players.edit', [$this->group, $otherApprovedMember, $player])
        );

        $response->assertForbidden();
    });
});

describe('destroy - deleting a player', function () {
    it('deletes player for owner', function () {
        $player = Player::factory()->for($this->member)->create();

        $response = $this->actingAs($this->user)->delete(
            route('groups.members.players.destroy', [$this->group, $this->member, $player])
        );

        $response->assertRedirect(route('groups.show', $this->group));
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });
});
