<x-layouts.app
    mainHeading="Import teams"
    mainDescription="Choose a team data source and import team data into the application."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'developer.teams.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('developer.teams.index')],
            ['text' => 'Import Teams', 'active' => true],
        ]"
    />

    <x-forms.multi-section-form action="{{ route('developer.teams.import-teams.store') }}">
        <x-slot name="sections">
            <x-forms.form-section
                title="Import source"
                description="Choose where team data comes from."
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
                description="Set required query filters for the team import request."
            >
                <div class="mt-4 grid gap-4 md:grid-cols-2">
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
                            placeholder="2026"
                            required
                        />
                        <x-inputs.input-error class="mt-2" :messages="$errors->get('year')" />
                    </div>
                </div>
            </x-forms.form-section>
            <x-forms.form-section
                title="Optional Filters"
                description="Set optional query filters for the team import request."
            >
                <div>
                    <x-inputs.input-label for="conference" class="font-semibold" :value="__('Conference filter')" />
                    <x-inputs.text-input
                        id="conference"
                        name="conference"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('conference')"
                        placeholder="ACC"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('conference')" />
                </div>

            </x-forms.form-section>
        </x-slot>

        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('developer.teams.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button type="submit">
                {{ __('Import teams') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-forms.multi-section-form>
</x-layouts.app>