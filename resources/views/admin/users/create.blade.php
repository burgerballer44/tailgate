<x-layouts.app>

<x-slot:heading>
    Create User
</x-slot>

<form method="POST" action="{{ route('users.store') }}">
    @csrf
    <x-form.input name="name" label="Name" placeholder="Enter your name" required />
    <x-form.input name="email" type="email" label="Email" placeholder="Enter your email" required />
    <x-form.input name="password" type="password" label="Password" placeholder="Enter your password" required />
    <x-form.input name="password_confirmation" type="password" label="Confirm Password" placeholder="Confirm your password" required />

    <div class="mt-4">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach (\App\Models\UserStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->value }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4">
        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
        <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach (\App\Models\UserRole::cases() as $role)
                <option value="{{ $role->value }}">{{ $role->value }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-4">
        <x-button type="submit">Create User</x-button>
    </div>
</form>

</x-layouts.app>