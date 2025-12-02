@props(['member' => null, 'group' => null, 'users' => collect(), 'action' => '', 'method' => 'POST'])

<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($method === 'POST')
        <div>
            <x-form.select
                name="user_id"
                label="User"
                :required="true"
                :value="old('user_id', $member?->user_id)"
                :options="['' => ''] + $users->mapWithKeys(fn($user) => [$user->id => $user->name])->toArray()"
            />
            <x-inputs.input-error class="mt-2" :messages="$errors->get('user_id')" />
        </div>
    @endif

    <div class="{{ $method === 'POST' ? 'mt-4' : '' }}">
        <x-form.select
            name="role"
            label="Role"
            :required="true"
            :value="old('role', $member?->role)"
            :options="['' => ''] + collect(\App\Models\GroupRole::cases())->mapWithKeys(fn($role) => [$role->value => $role->value])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('role')" />
    </div>

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('groups.members.index', $group) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
