<x-layouts.app
    mainHeading="User: {{ $user->name }} ({{ $user->email }})"
    mainDescription="Details for user including name, email, status, and role."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'developer.users.index'],
        ['text' => 'Edit User', 'route' => 'developer.users.edit', 'params' => ['user' => $user]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Users', 'url' => route('developer.users.index')],
            ['text' => $user->name, 'active' => true],
        ]"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-model-viewer
            message="Personal information"
            details="Core identity and contact information for this user."
            tone="info"
            :fields="[
                [
                    'label' => 'Name',
                    'value' => $user->name,
                ],
                [
                    'label' => 'Email',
                    'value' => $user->email,
                ],
                [
                    'label' => 'ULID',
                    'value' => $user->ulid,
                ],
                [
                    'label' => 'ID',
                    'value' => $user->id,
                ],
            ]"
        />

        <x-model-viewer
            message="Access and account state"
            details="Role, status, and authentication-related fields."
            tone="success"
            :fields="[
                [
                    'label' => 'Role',
                    'value' => $user->role,
                ],
                [
                    'label' => 'Status',
                    'value' => $user->status,
                ],
                [
                    'label' => 'Has Password',
                    'value' => filled($user->password),
                ],
                [
                    'label' => 'Remember Token Present',
                    'value' => filled($user->remember_token),
                ],
            ]"
        />

        <x-model-viewer
            message="Activity history"
            details="Verification and sign-in activity for this account."
            tone="warning"
            :fields="[
                [
                    'label' => 'Email Verified At',
                    'value' => $user->email_verified_at?->format('F j, Y, g:i a') ?? 'Not verified',
                ],
                [
                    'label' => 'Last Login At',
                    'value' => $user->last_login_at?->format('F j, Y, g:i a') ?? 'Never',
                ],
                [
                    'label' => 'Membership Count',
                    'value' => $user->members_count,
                ],
                [
                    'label' => 'Social Account Count',
                    'value' => $user->social_accounts_count,
                ],
            ]"
        />

        <x-model-viewer
            message="Metadata"
            details="Lifecycle details that help with debugging and audits."
            tone="neutral"
            :fields="[
                [
                    'label' => 'Created At',
                    'value' => $user->created_at?->format('F j, Y, g:i a') ?? 'N/A',
                ],
                [
                    'label' => 'Updated At',
                    'value' => $user->updated_at?->format('F j, Y, g:i a') ?? 'N/A',
                ],
            ]"
        />
    </div>
</x-layouts.app>
