@props([
    'group' => null,
    'users' => collect(),
    'groupPolicies' => collect(), 'action' => '', 'method' => 'POST'])

@php
    $enabledPolicyKeys = old('enabled_prediction_policies', $group?->enabled_prediction_policies ?? []);
@endphp

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Group details"
            description="Enter the group name and assign an owner."
        >
            <div>
                <x-inputs.input-label for="name" class="font-semibold" :value="__('Name')" />
                <x-inputs.text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $group?->name)"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="owner_id"
                    label="Owner"
                    :required="true"
                    :value="old('owner_id', $group?->owner?->id)"
                    :options="['' => ''] + $users->mapWithKeys(fn($user) => [$user->id => $user->name])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('owner_id')" />
            </div>

            @if ($group !== null && $groupPolicies->isNotEmpty())
                <div class="mt-4">
                    <label for="enabled_prediction_policies" class="block font-semibold">Enabled prediction policies</label>
                    <p class="mt-1 text-sm text-gray-600">Select group-level prediction policies to enforce for this group.</p>

                    <div class="mt-2 space-y-3">
                        @foreach ($groupPolicies as $policy)
                            <div>
                                <x-form.checkbox
                                    id="prediction_policy_{{ $policy->key() }}"
                                    name="enabled_prediction_policies[]"
                                    :label="$policy->label()"
                                    :value="$policy->key()"
                                    :checked="in_array($policy->key(), $enabledPolicyKeys, true)"
                                    labelClass="text-sm"
                                />
                                <p class="ms-6 text-xs text-gray-500">{{ $policy->description() }}</p>
                            </div>
                        @endforeach
                    </div>

                    <x-inputs.input-error class="mt-2" :messages="$errors->get('enabled_prediction_policies')" />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('enabled_prediction_policies.*')" />
                </div>
            @endif
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
