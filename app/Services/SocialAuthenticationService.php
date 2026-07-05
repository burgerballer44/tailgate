<?php

namespace App\Services;

use App\Exceptions\SocialAuthenticationException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;

/**
 * Resolves provider identities into local accounts during social sign-in.
 * Handles account linking, metadata synchronization, and first-time user creation within a single flow.
 */
class SocialAuthenticationService
{
    /**
     * Resolve a local user account from the given provider user details.
     *
     * This resolves an existing linked account first, then falls back to matching
     * by email and creating a new user when no local account exists.
     *
     * @param  string  $provider  The external authentication provider key, such as `google`.
     * @param  ProviderUser  $providerUser  The identity payload returned by the provider.
     * @return User The resolved local user account, either existing or newly created.
     *
     * @throws SocialAuthenticationException When the provider does not return a usable user ID or email.
     */
    public function resolveUserFromProvider(string $provider, ProviderUser $providerUser): User
    {
        $providerUserId = (string) $providerUser->getId();

        if ($providerUserId === '') {
            throw new SocialAuthenticationException('The authentication provider did not return a valid user id.');
        }

        return DB::transaction(function () use ($provider, $providerUser, $providerUserId): User {

            // Prefer the linked social account so repeated logins keep the same local user.
            $linkedAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            // When a link already exists, refresh the external metadata and reuse the same user.
            if ($linkedAccount !== null) {
                $this->updateSocialAccountMetadata(account: $linkedAccount, providerUser: $providerUser);

                return $linkedAccount->user;
            }

            // Fall back to email because some providers only guarantee a unique account per email address.
            $email = $providerUser->getEmail();

            if ($email === null) {
                throw new SocialAuthenticationException('The authentication provider did not return an email address.');
            }

            // Reuse the email-matched local user when one already exists.
            $user = User::query()->where('email', $email)->first();

            // Create a local account when the provider email has not been seen before.
            if ($user === null) {
                $user = User::query()->create([
                    'name' => $this->resolveName(providerUser: $providerUser, email: $email),
                    'email' => $email,
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
                // If the email already belongs to a local account, trust the provider assertion.
            } elseif (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            // link the provider user to the resolved local user account
            $account = $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ]);

            $this->updateSocialAccountMetadata(account: $account, providerUser: $providerUser);

            return $user;
        });
    }

    /**
     * Update the metadata of a linked social account based on the latest provider user details.
     *
     * Keeps local account metadata aligned with the latest provider payload.
     *
     * @param  SocialAccount  $account  The linked social account to refresh.
     * @param  ProviderUser  $providerUser  The latest user details from the authentication provider.
     */
    protected function updateSocialAccountMetadata(SocialAccount $account, ProviderUser $providerUser): void
    {
        $rawProfile = null;

        if (is_callable([$providerUser, 'getRaw'])) {
            $rawProfile = call_user_func([$providerUser, 'getRaw']);
        }

        $account->provider_email = $providerUser->getEmail();
        $account->avatar_url = $providerUser->getAvatar();
        $account->raw_profile = $rawProfile;
        $account->last_login_at = now();
        $account->save();
    }

    /**
     * Resolve a display name for the user based on the provider user details and email.
     *
     * Uses the provider's name when available, otherwise derives a title-cased name
     * from the local part of the email address.
     *
     * @param  ProviderUser  $providerUser  The user details returned by the authentication provider.
     * @param  string  $email  The provider email used as a fallback name source.
     * @return string The resolved display name for the user.
     */
    protected function resolveName(ProviderUser $providerUser, string $email): string
    {
        // if the provider returns a non-empty name, use it
        $name = $providerUser->getName();

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        // otherwise, derive a name from the email address by taking the part before the '@',
        // replacing common separators with spaces, and converting to title case
        return str((string) str($email)->before('@'))->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
