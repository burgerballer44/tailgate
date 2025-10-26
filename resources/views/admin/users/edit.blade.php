<x-layouts.app mainHeading="Edit User" mainDescription="Update the details of the user.">
    @include(
        'admin.users._form',
        [
            'action' => route('users.update', $user),
            'method' => 'PUT',
            'user' => $user,
        ]
    )
</x-layouts.app>
