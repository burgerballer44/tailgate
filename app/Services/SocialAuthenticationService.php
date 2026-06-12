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
     * This method will attempt to find or create a local user account based on the
     * provided social authentication details. It handles linking social accounts to
     * existing users and creating new users when necessary.
     *
     * @param  string  $provider  The name of the authentication provider (e.g., 'google').
     * @param  ProviderUser  $providerUser  The user details returned by the authentication provider.
     * @return User The resolved local user account.
     *
     * @throws SocialAuthenticationException If the provider user details are invalid or incomplete.
     */
    public function resolveUserFromProvider(string $provider, ProviderUser $providerUser): User
    {
        $providerUserId = (string) $providerUser->getId();

        if ($providerUserId === '') {
            throw new SocialAuthenticationException('The authentication provider did not return a valid user id.');
        }

        return DB::transaction(function () use ($provider, $providerUser, $providerUserId): User {

            // attempt to find an existing linked social account for this provider user ID
            $linkedAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            // if a linked social account exists, update its metadata and return the associated user
            if ($linkedAccount !== null) {
                $this->updateSocialAccountMetadata(account: $linkedAccount, providerUser: $providerUser);

                return $linkedAccount->user;
            }

            // no linked account exists, attempt to find or create a local user based on the provider email
            $email = $providerUser->getEmail();

            if ($email === null) {
                throw new SocialAuthenticationException('The authentication provider did not return an email address.');
            }

            // attempt to find an existing user with the same email address
            $user = User::query()->where('email', $email)->first();

            // create a new user if no existing user is found
            if ($user === null) {
                $user = User::query()->create([
                    'name' => $this->resolveName(providerUser: $providerUser, email: $email),
                    'email' => $email,
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
                // if an existing user is found but their email is not verified, mark it as verified
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
     * This method updates the email, avatar URL, raw profile data, and last login timestamp
     * of the given social account based on the information provided by the authentication provider.
     *
     * @param  SocialAccount  $account  The social account to update.
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
     * This method attempts to determine a suitable display name for the user based on the
     * information provided by the authentication provider. It uses the provider's name if
     * available, or derives a name from the email address if not.
     *
     * @param  ProviderUser  $providerUser  The user details from the authentication provider.
     * @param  string  $email  The email address of the user.
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
