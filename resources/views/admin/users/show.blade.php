<x-layouts.app
    mainHeading="User: {{ $user->name }} ({{ $user->email }})"
    mainDescription="Details for user including name, email, status, and role."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'users.index'],
        ['text' => 'Edit User', 'route' => 'users.edit', 'params' => ['user' => $user]],
    ]"
>
    <x-model-viewer
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
                'label' => 'Status',
                'value' => $user->status,
            ],
            [
                'label' => 'Role',
                'value' => $user->role,
            ],
            [
                'label' => 'Email Verified At',
                'value' => $user->email_verified_at ? $user->email_verified_at->format('F j, Y, g:i a') : 'Not Verified',
            ],
            [
                'label' => 'Created At',
                'value' => $user->created_at->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Updated At',
                'value' => $user->updated_at->format('F j, Y, g:i a'),
            ],
        ]"
    />
</x-layouts.app>
