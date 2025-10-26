<x-layouts.app
    mainHeading="Users"
    mainDescription="A list of all the users including their name, title, email and role."
    :mainActions="[
        ['text' => 'Add User', 'route' => 'users.create'],
    ]"
>
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 bg-white p-6">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">User Details</h2>

                <div class="mt-4">
                    <p>
                        <strong>Name:</strong>
                        {{ $user->name }}
                    </p>
                    <p>
                        <strong>Email:</strong>
                        {{ $user->email }}
                    </p>
                    <p>
                        <strong>Created At:</strong>
                        {{ $user->created_at }}
                    </p>
                    <p>
                        <strong>Updated At:</strong>
                        {{ $user->updated_at }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="{{ route('users.index') }}" class="rounded-md bg-gray-500 px-4 py-2 text-white">Back</a>
                    <a
                        href="{{ route('users.edit', $user) }}"
                        class="ml-2 rounded-md bg-blue-500 px-4 py-2 text-white"
                    >
                        Edit
                    </a>
                    <form
                        action="{{ route('users.destroy', $user) }}"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this user?');"
                        class="inline"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ml-2 rounded-md bg-red-500 px-4 py-2 text-white">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
