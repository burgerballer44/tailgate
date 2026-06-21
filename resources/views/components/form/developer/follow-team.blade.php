@props(['group' => null, 'teams' => collect(), 'sportOptions' => [], 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Team selection"
            description="Choose the team this group will follow."
        >
            <div>
                <x-form.select
                    name="team_id"
                    label="Team"
                    :required="true"
                    :value="old('team_id')"
                    :options="['' => ''] + $teams->mapWithKeys(fn($team) => [$team->id => $team->display_name])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('team_id')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="sport"
                    label="Sport scope"
                    :required="false"
                    :value="old('sport')"
                    :options="['' => 'All sports'] + $sportOptions"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('sport')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.show', $group) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Follow Team') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
