@php
    // get the sports associated with the team for checking the checkboxes
    $teamSports = $team?->sports
        ->pluck('sport')
        ->pluck('value')
        ->toArray();

    $formatJsonField = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    };

    $logosValue = $formatJsonField(old('logos', $team?->logos));
    $socialMediaValue = $formatJsonField(old('social_media', $team?->social_media));
@endphp

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Team identity"
            description="Enter the core identifying information for this team, including its name, conference, and abbreviation."
        >
            <div>
                <x-inputs.input-label for="organization" class="font-semibold" :value="__('Organization')" />
                <x-inputs.text-input
                    id="organization"
                    name="organization"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('organization', $team?->organization)"
                    required
                    autofocus
                    autocomplete="organization"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('organization')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="designation" class="font-semibold" :value="__('Designation')" />
                <x-inputs.text-input
                    id="designation"
                    name="designation"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('designation', $team?->designation)"
                    required
                    autocomplete="designation"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('designation')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="conference" class="font-semibold" :value="__('Conference')" />
                <x-inputs.text-input
                    id="conference"
                    name="conference"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('conference', $team?->conference)"
                    required
                    autocomplete="conference"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('conference')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="abbreviation" class="font-semibold" :value="__('Abbreviation')" />
                <x-inputs.text-input
                    id="abbreviation"
                    name="abbreviation"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('abbreviation', $team?->abbreviation)"
                    autocomplete="abbreviation"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('abbreviation')" />
            </div>
        </x-forms.form-section>

        <x-forms.form-section
            title="Appearance"
            description="Set the team's brand colors, logo URLs, and social media links."
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-inputs.input-label for="color" class="font-semibold" :value="__('Color (hex)')" />
                    <x-inputs.text-input
                        id="color"
                        name="color"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('color', $team?->color)"
                        placeholder="#8c2232"
                        autocomplete="off"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('color')" />
                </div>
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="logos" class="font-semibold" :value="__('Logos (JSON array of URLs)')" />
                <textarea
                    id="logos"
                    name="logos"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder='["https://example.com/logo.png"]'
                >{{ $logosValue }}</textarea>
                <x-inputs.input-error class="mt-2" :messages="$errors->get('logos')" />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('logos.*')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="social_media" class="font-semibold" :value="__('Social media (JSON array of label/url objects)')" />
                <textarea
                    id="social_media"
                    name="social_media"
                    rows="5"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder='[{"label":"X","url":"https://x.com/example"}]'
                >{{ $socialMediaValue }}</textarea>
                <x-inputs.input-error class="mt-2" :messages="$errors->get('social_media')" />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('social_media.*')" />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('social_media.*.label')" />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('social_media.*.url')" />
            </div>
        </x-forms.form-section>

        <x-forms.form-section
            title="Classification"
            description="Select the team type and the sports this team competes in."
        >
            <div>
                <x-inputs.input-label for="type" class="font-semibold" :value="__('Type')" />
                <select
                    id="type"
                    name="type"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Select Type</option>
                    @foreach ($types as $typeOption)
                        <option value="{{ $typeOption }}" {{ old('type', $team?->type) === $typeOption ? 'selected' : '' }}>
                            {{ $typeOption }}
                        </option>
                    @endforeach
                </select>
                <x-inputs.input-error class="mt-2" :messages="$errors->get('type')" />
            </div>

            <div class="mt-4">
                <label for="sports" class="block font-semibold">Sports</label>
                <div class="mt-2 space-y-2">
                    @foreach ($sports as $sport)
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="sport_{{ $sport }}"
                                name="sports[]"
                                value="{{ $sport }}"
                                {{ in_array($sport, old('sports', $teamSports ?? [])) ? 'checked' : '' }}
                                class="text-navy-600 focus:ring-navy-500 rounded border-gray-300 shadow-sm"
                            />
                            <label for="sport_{{ $sport }}" class="ml-2 text-sm">{{ ucfirst($sport) }}</label>
                        </div>
                    @endforeach
                </div>
                <x-inputs.input-error class="mt-2" :messages="$errors->get('sports')" />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('sports.*')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.teams.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
