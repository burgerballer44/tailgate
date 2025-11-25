<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

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

    {{-- password is commented out since password changes are handled separately --}}
    {{--
        <div class="mt-4">
        <x-inputs.input-label for="password" class="font-semibold" :value="__('Password')" />
        <x-inputs.text-input
        id="password"
        name="password"
        type="password"
        class="mt-1 block w-full"
        autocomplete="new-password"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('password')" />
        </div>
        
        <div class="mt-4">
        <x-inputs.input-label for="password_confirmation" class="font-semibold" :value="__('Confirm Password')" />
        <x-inputs.text-input
        id="password_confirmation"
        name="password_confirmation"
        type="password"
        class="mt-1 block w-full"
        autocomplete="new-password"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>
    --}}

    <div class="mt-4">
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

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('users.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
