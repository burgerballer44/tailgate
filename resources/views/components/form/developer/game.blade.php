<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        {{-- hidden season id is included in sections so it is inside the form --}}
        <x-form.hidden name="season_id" :value="$season->id" />

        <x-forms.form-section
            title="Teams"
            description="Select the home and away teams for this game."
        >
            <div>
                <x-form.select
                    name="home_team_id"
                    label="Home Team"
                    :required="true"
                    :value="old('home_team_id', $game?->home_team_id)"
                    placeholder="Select a team"
                    :options="$teams"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('home_team_id')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="away_team_id"
                    label="Away Team"
                    :required="true"
                    :value="old('away_team_id', $game?->away_team_id)"
                    placeholder="Select a team"
                    :options="$teams"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('away_team_id')" />
            </div>
        </x-forms.form-section>

        <x-forms.form-section
            title="Game details"
            description="Set the schedule and score information for this game."
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-inputs.input-label for="home_team_score" class="font-semibold" :value="__('Home Team Score')" />
                    <x-inputs.text-input
                        id="home_team_score"
                        name="home_team_score"
                        type="number"
                        class="mt-1 block w-full"
                        :value="old('home_team_score', $game?->home_team_score)"
                        min="0"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('home_team_score')" />
                </div>

                <div>
                    <x-inputs.input-label for="away_team_score" class="font-semibold" :value="__('Away Team Score')" />
                    <x-inputs.text-input
                        id="away_team_score"
                        name="away_team_score"
                        type="number"
                        class="mt-1 block w-full"
                        :value="old('away_team_score', $game?->away_team_score)"
                        min="0"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('away_team_score')" />
                </div>
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="start_date_time" class="font-semibold" :value="__('Start Date Time')" />
                <x-inputs.text-input
                    id="start_date_time"
                    name="start_date_time"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('start_date_time', $game?->start_date_time)"
                    placeholder="e.g., 2024-10-01 19:00:00, 2024-10-01, or leave blank"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('start_date_time')" />
            </div>

            <div class="mt-4">
                <x-form.checkbox
                    id="start_time_tbd"
                    name="start_time_tbd"
                    label="Start Time TBD"
                    value="1"
                    :checked="(bool) old('start_time_tbd', $game?->start_time_tbd)"
                    :includeHidden="true"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('start_time_tbd')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.seasons.games.index', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
