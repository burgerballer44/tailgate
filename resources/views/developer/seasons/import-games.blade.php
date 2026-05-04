<x-layouts.app
    mainHeading="Import games for {!! $season->name !!}"
    mainDescription="Choose a game data source and import schedule data into this season."
    :mainActions="[
        ['text' => 'Back to Season', 'route' => 'developer.seasons.show', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('developer.seasons.index')],
            ['text' => $season->name, 'url' => route('developer.seasons.show', $season)],
            ['text' => 'Import Games', 'active' => true],
        ]"
    />

    <x-forms.multi-section-form action="{{ route('developer.seasons.import-games.store', $season) }}">
        <x-slot name="sections">
            <x-forms.form-section
                title="Import source"
                description="Choose where game data comes from."
            >
                <x-form.select
                    name="source"
                    label="Import source"
                    :required="true"
                    :value="old('source', 'cfbd')"
                    placeholder="Select a source"
                    :options="collect($sources)->mapWithKeys(fn ($source) => [$source['value'] => $source['label']])->all()"
                />

                <x-inputs.input-error class="mt-2" :messages="$errors->get('source')" />

                <div class="mt-2 space-y-1 text-sm text-gray-600">
                    @foreach ($sources as $source)
                        <p><span class="font-semibold">{{ $source['label'] }}</span>: {{ $source['description'] }}</p>
                    @endforeach
                </div>
            </x-forms.form-section>

            <x-forms.form-section
                title="Required Filters"
                description="Set required filters for the import request."
            >
                <div class="mt-4">
                    <div>
                        <x-inputs.input-label for="year" class="font-semibold" :value="__('Season year')" />
                        <x-inputs.text-input
                            id="year"
                            name="year"
                            type="number"
                            class="mt-1 block w-full"
                            :value="old('year')"
                            min="1900"
                            max="2100"
                            required
                        />
                        <x-inputs.input-error class="mt-2" :messages="$errors->get('year')" />
                    </div>
                </div>
            </x-forms.form-section>

            <x-forms.form-section
                title="Optional Filters"
                description="Set optional season and week filters for the import request."
            >
                <div class="mt-4">
                    <div>
                        <x-form.select
                            name="season_type"
                            label="Season type"
                            :value="old('season_type')"
                            placeholder="Select a season type"
                            :options="$seasonTypes"
                        />
                        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_type')" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-inputs.input-label for="week" class="font-semibold" :value="__('Week filter')" />
                    <x-inputs.text-input
                        id="week"
                        name="week"
                        type="number"
                        class="mt-1 block w-full"
                        :value="old('week')"
                        min="1"
                        max="30"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('week')" />

                </div>
            </x-forms.form-section>
        </x-slot>

        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('developer.seasons.show', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button type="submit">
                {{ __('Import games') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-forms.multi-section-form>
</x-layouts.app>