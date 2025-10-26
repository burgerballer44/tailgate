<x-layouts.app mainHeading="Create User" mainDescription="Add a new user.">
    @include(
        'admin.users._form',
        [
            'action' => route('users.store'),
            'method' => 'POST',
            'user' => null,
        ]
    )
</x-layouts.app>
