<?php

use App\Exceptions\SocialAuthenticationException;
use App\Models\User;
use App\Services\SocialAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

function socialProviderUser(
    string $id,
    ?string $email,
    ?string $name = 'Social Name',
): ProviderUser {
    $providerUser = Mockery::mock(ProviderUser::class);
    $providerUser->allows('getId')->andReturn($id);
    $providerUser->allows('getEmail')->andReturn($email);
    $providerUser->allows('getName')->andReturn($name);
    $providerUser->allows('getAvatar')->andReturn('https://example.com/avatar.png');
    $providerUser->allows('getRaw')->andReturn([
        'sub' => $id,
        'email' => $email,
    ]);

    return $providerUser;
}

describe('resolveUserFromProvider', function () {
    test('returns existing user when provider account is already linked', function () {
        $user = User::factory()->create([
            'email' => 'already-linked@example.com',
        ]);

        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => 'linked-123',
        ]);

        $service = new SocialAuthenticationService;

        $resolvedUser = $service->resolveUserFromProvider(
            provider: 'google',
            providerUser: socialProviderUser(id: 'linked-123', email: 'already-linked@example.com'),
        );

        expect($resolvedUser->id)->toBe($user->id);
    });

    test('links social account to existing user by email', function () {
        $user = User::factory()->unverified()->create([
            'email' => 'existing@example.com',
        ]);

        $service = new SocialAuthenticationService;

        $resolvedUser = $service->resolveUserFromProvider(
            provider: 'google',
            providerUser: socialProviderUser(id: 'email-link-123', email: 'existing@example.com', name: 'Existing User'),
        );

        expect($resolvedUser->id)->toBe($user->id)
            ->and($resolvedUser->fresh()->email_verified_at)->not->toBeNull();

        assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'email-link-123',
            'user_id' => $user->id,
        ]);
    });

    test('creates a new user for first social login', function () {
        $service = new SocialAuthenticationService;

        $resolvedUser = $service->resolveUserFromProvider(
            provider: 'google',
            providerUser: socialProviderUser(id: 'new-123', email: 'brand-new@example.com', name: null),
        );

        expect($resolvedUser->email)->toBe('brand-new@example.com')
            ->and($resolvedUser->name)->toBe('Brand New')
            ->and($resolvedUser->password)->toBeNull();

        assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'new-123',
            'user_id' => $resolvedUser->id,
        ]);
    });

    test('throws when provider does not return a user id', function () {
        $service = new SocialAuthenticationService;

        $service->resolveUserFromProvider(
            provider: 'google',
            providerUser: socialProviderUser(id: '', email: 'missing-id@example.com'),
        );
    })->throws(SocialAuthenticationException::class, 'did not return a valid user id');

    test('throws when provider does not return an email for first-time login', function () {
        $service = new SocialAuthenticationService;

        $service->resolveUserFromProvider(
            provider: 'google',
            providerUser: socialProviderUser(id: 'missing-email-123', email: null),
        );
    })->throws(SocialAuthenticationException::class, 'did not return an email address');
});
