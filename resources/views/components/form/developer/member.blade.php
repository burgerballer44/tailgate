@props(['member' => null, 'group' => null, 'users' => collect(), 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Member details"
            description="Configure the user, role, and status for this group member."
        >
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

            <div class="mt-4">
                <x-form.select
                    name="status"
                    label="Status"
                    :required="true"
                    :value="old('status', $member?->status ?? \App\Models\MemberStatus::APPROVED->value)"
                    :options="['' => ''] + collect(\App\Models\MemberStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->value])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('status')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.members.index', $group) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
