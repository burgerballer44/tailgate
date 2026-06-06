<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Season information"
            description="Enter the basic details for this season, including the name, sport, and type."
        >
            <div>
                <x-inputs.input-label for="name" class="font-semibold" :value="__('Name')" />
                <x-inputs.text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $season?->name)"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="sport"
                    label="Sport"
                    :required="true"
                    :value="old('sport', $season?->sport)"
                    :options="['' => ''] + $sports->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('sport')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="season_type"
                    label="Season Type"
                    :required="true"
                    :value="old('season_type', $season?->season_type)"
                    :options="['' => ''] + $seasonTypes->mapWithKeys(fn($type) => [$type => ucfirst($type)])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('season_type')" />
            </div>
        </x-forms.form-section>

        <x-forms.form-section
            title="Season status"
            description="Mark whether this season should currently be treated as active."
        >
            <div class="mt-4">
                <x-form.checkbox
                    id="active"
                    name="active"
                    label="Active"
                    :checked="(bool) old('active', $season?->active)"
                    :includeHidden="true"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('active')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.seasons.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
