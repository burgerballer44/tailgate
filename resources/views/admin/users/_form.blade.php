<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-inputs.input-label for="name" :value="__('Name')" />
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
        <x-inputs.input-label for="email" :value="__('Email')" />
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

    <div class="mt-4">
        <x-inputs.input-label for="password" :value="__('Password')" />
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
        <x-inputs.input-label for="password_confirmation" :value="__('Confirm Password')" />
        <x-inputs.text-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="status" :value="__('Status')" />
        <select
            id="status"
            name="status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            @foreach (\App\Models\UserStatus::cases() as $statusEnum)
                <option
                    value="{{ $statusEnum->value }}"
                    {{ old('status', $user?->status) === $statusEnum->value ? 'selected' : '' }}
                >
                    {{ $statusEnum->value }}
                </option>
            @endforeach
        </select>
        <x-inputs.input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="role" :value="__('Role')" />
        <select
            id="role"
            name="role"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            @foreach (\App\Models\UserRole::cases() as $roleEnum)
                <option
                    value="{{ $roleEnum->value }}"
                    {{ old('role', $user?->role) === $roleEnum->value ? 'selected' : '' }}
                >
                    {{ $roleEnum->value }}
                </option>
            @endforeach
        </select>
        <x-inputs.input-error class="mt-2" :messages="$errors->get('role')" />
    </div>

    <div class="mt-4 flex items-center justify-end">
        <x-buttons.cancel-button :href="route('users.index')">
            {{ __('Cancel') }}
        </x-buttons.cancel-button>

        <x-buttons.primary-button class="ms-4">
            {{ __('Submit') }}
        </x-buttons.primary-button>
    </div>
</form>
