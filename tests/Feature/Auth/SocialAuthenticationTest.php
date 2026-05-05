<?php

use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function fakeProviderUser(string $id, ?string $email, ?string $name = 'Google User'): ProviderUser
{
    $providerUser = \Mockery::mock(ProviderUser::class);
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

test('google redirect route redirects to provider', function () {
    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    get(route('auth.google'))
        ->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
});

test('callback logs in an existing linked social account user', function () {
    $user = User::factory()->create();

    SocialAccount::query()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => 'google-123',
    ]);

    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn(fakeProviderUser(id: 'google-123', email: $user->email));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    get('/auth/callback')->assertRedirect(route('dashboard'));

    assertAuthenticated();
    assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-123',
        'user_id' => $user->id,
    ]);
});

test('callback links provider account to an existing email user', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'person@example.com',
    ]);

    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn(fakeProviderUser(id: 'google-456', email: 'person@example.com', name: 'Person Example'));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    get('/auth/callback')->assertRedirect(route('dashboard'));

    assertAuthenticated();

    expect($user->fresh()->email_verified_at)->not->toBeNull();

    assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-456',
        'user_id' => $user->id,
    ]);
});

test('callback creates a new user and linked social account', function () {
    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn(fakeProviderUser(
        id: 'google-789',
        email: 'new-person@example.com',
        name: 'New Person',
    ));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    get('/auth/callback')->assertRedirect(route('dashboard'));

    $newUser = User::query()->where('email', 'new-person@example.com')->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->name)->toBe('New Person')
        ->and($newUser->password)->toBeNull()
        ->and($newUser->email_verified_at)->not->toBeNull()
        ->and($newUser->status)->toBe(UserStatus::ACTIVE->value)
        ->and($newUser->role)->toBe(UserRole::REGULAR->value)
        ->and($newUser->remember_token)->toBeNull()
        ->and($newUser->ulid)->not->toBe('');

    assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-789',
        'user_id' => $newUser->id,
    ]);
});

test('callback redirects back to login with an error when provider email is missing', function () {
    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn(fakeProviderUser(id: 'google-no-email', email: null));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    get('/auth/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    assertGuest();
});
