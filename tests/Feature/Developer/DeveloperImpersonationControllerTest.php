<?php

use App\Models\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->developer = signInDeveloperUser();
});

describe('start', function () {
    test('developer can impersonate another user', function () {
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $response = $this->post(route('developer.impersonation.start', ['user' => $targetUser]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($targetUser);
        expect(session('impersonator_user_id'))->toBe($this->developer->id);
        expect(session('impersonated_user_id'))->toBe($targetUser->id);
    });

    test('developer cannot start impersonation when one is already active', function () {
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        session([
            'impersonator_user_id' => $this->developer->id,
            'impersonated_user_id' => $targetUser->id,
        ]);

        $response = $this->post(route('developer.impersonation.start', ['user' => $targetUser]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->developer);
        expect(session('alert')['message'])->toBe('You are already viewing the application as another user. Return to your own view first.');
    });

    test('regular user cannot start impersonation', function () {
        $regularUser = signInRegularUser();
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $response = $this->post(route('developer.impersonation.start', ['user' => $targetUser]));

        $response->assertForbidden();
        $this->assertAuthenticatedAs($regularUser);
    });
});

describe('stop', function () {
    test('restores the developer session', function () {
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $this->actingAs($targetUser);

        session([
            'impersonator_user_id' => $this->developer->id,
            'impersonator_user_name' => $this->developer->name,
            'impersonated_user_id' => $targetUser->id,
            'impersonated_user_name' => $targetUser->name,
        ]);

        $response = $this->post(route('developer.impersonation.stop'));

        $response->assertRedirect(route('developer.users.index'));
        $this->assertAuthenticatedAs($this->developer);
        expect(session()->has('impersonator_user_id'))->toBeFalse();
        expect(session()->has('impersonated_user_id'))->toBeFalse();
    });

    test('restores the developer session when impersonated user is unverified', function () {
        $targetUser = User::factory()->unverified()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $this->actingAs($targetUser);

        session([
            'impersonator_user_id' => $this->developer->id,
            'impersonator_user_name' => $this->developer->name,
            'impersonated_user_id' => $targetUser->id,
            'impersonated_user_name' => $targetUser->name,
        ]);

        $response = $this->post(route('developer.impersonation.stop'));

        $response->assertRedirect(route('developer.users.index'));
        $this->assertAuthenticatedAs($this->developer);
    });
});

describe('ui', function () {
    test('shows impersonation banner on dashboard while impersonating', function () {
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $this->actingAs($targetUser);

        session([
            'impersonator_user_id' => $this->developer->id,
            'impersonator_user_name' => $this->developer->name,
            'impersonated_user_id' => $targetUser->id,
            'impersonated_user_name' => $targetUser->name,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Developer view-as mode');
        $response->assertSee('Viewing the app as '.$targetUser->name);
        $response->assertSee('Return to my developer account');
    });

    test('shows switch-back menu item in navigation while impersonating', function () {
        $targetUser = User::factory()->create([
            'role' => UserRole::REGULAR->value,
        ]);

        $this->actingAs($targetUser);

        session([
            'impersonator_user_id' => $this->developer->id,
            'impersonator_user_name' => $this->developer->name,
            'impersonated_user_id' => $targetUser->id,
            'impersonated_user_name' => $targetUser->name,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Return to developer account');
    });
});
