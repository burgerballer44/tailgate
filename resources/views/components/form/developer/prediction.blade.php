@props(['prediction' => null, 'player' => null, 'group' => null, 'member' => null, 'games' => collect(), 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Prediction"
            description="Select a game and enter your prediction for the home and away teams."
        >
            @if ($method === 'POST' && $games->isNotEmpty())
                <div>
                    <x-form.select
                        name="game_id"
                        label="Game"
                        :required="true"
                        :value="old('game_id', $prediction?->game_id)"
                        :options="['' => ''] + $games->mapWithKeys(fn($game) => [$game->id => $game->homeTeam->name . ' vs ' . $game->awayTeam->name . ' (' . $game->start_date_time . ($game->start_time_tbd ? ', TBD' : '') . ')'])->toArray()"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('game_id')" />
                </div>
            @endif

            <div class="mt-4">
                <x-inputs.input-label for="home_team_prediction" class="font-semibold" :value="__('Home Team Prediction')" />
                <x-inputs.text-input
                    id="home_team_prediction"
                    name="home_team_prediction"
                    type="number"
                    class="mt-1 block w-full"
                    :value="old('home_team_prediction', $prediction?->home_team_prediction)"
                    required
                    min="0"
                    autocomplete="home_team_prediction"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('home_team_prediction')" />
            </div>

            <div class="mt-4">
                <x-inputs.input-label for="away_team_prediction" class="font-semibold" :value="__('Away Team Prediction')" />
                <x-inputs.text-input
                    id="away_team_prediction"
                    name="away_team_prediction"
                    type="number"
                    class="mt-1 block w-full"
                    :value="old('away_team_prediction', $prediction?->away_team_prediction)"
                    required
                    min="0"
                    autocomplete="away_team_prediction"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('away_team_prediction')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.members.players.show', [$group, $member, $player]) }}">
                    {{ __('Cancel') }}
                </a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>