<x-layouts.app
    mainHeading="Edit User"
    mainDescription="Update the details of the user."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'developer.users.index'],
        ['text' => 'View User', 'route' => 'developer.users.show', 'params' => ['user' => $user]],
    ]"
>
    <x-breadcrumb :breadcrumbs="[
        ['text' => 'Home', 'url' => route('dashboard')],
        ['text' => 'Users', 'url' => route('developer.users.index')],
        ['text' => $user->name, 'url' => route('developer.users.show', $user)],
        ['text' => 'Edit User', 'active' => true],
    ]" />
    <x-form.developer.user
        :action="route('developer.users.update', $user)"
        :method="'PUT'"
        :user="$user"
        :statuses="$statuses"
        :roles="$roles"
    >
        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('developer.users.show', $user) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-form.user>
</x-layouts.app>
