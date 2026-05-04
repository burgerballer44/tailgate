<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Personal information"
            description="Enter the user's name and email address."
        >
            <div>
                <x-inputs.input-label for="name" class="font-semibold" :value="__('Name')" />
                <x-inputs.text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $user?->name)"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="email" class="font-semibold" :value="__('Email')" />
                <x-inputs.text-input
                    id="email"
                    name="email"
                    type="email"
                    class="mt-1 block w-full"
                    :value="old('email', $user?->email)"
                    required
                    autocomplete="username"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </x-forms.form-section>

        <x-forms.form-section
            title="Account settings"
            description="Configure the user's account status and role."
        >
            <div>
                <x-form.select
                    name="status"
                    label="Status"
                    :required="true"
                    :value="old('status', $user?->status)"
                    :options="['' => ''] + $statuses->mapWithKeys(fn($status) => [$status => ucfirst($status)])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('status')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="role"
                    label="Role"
                    :required="true"
                    :value="old('role', $user?->role)"
                    :options="['' => ''] + $roles->mapWithKeys(fn($role) => [$role => ucfirst($role)])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('role')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.users.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
