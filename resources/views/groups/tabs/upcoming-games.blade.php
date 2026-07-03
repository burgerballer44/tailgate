@php
    $storePredictionRouteTemplate = route('groups.predictions.store', ['group' => $group, 'player' => '__PLAYER__']);
    $updatePredictionRouteTemplate = route('groups.predictions.update', ['group' => $group, 'player' => '__PLAYER__', 'prediction' => '__PREDICTION__']);

    $playerOptions = $memberPlayers
        ->map(fn ($player) => [
            'id' => $player->id,
            'ulid' => $player->ulid,
            'name' => $player->player_name,
        ])
        ->values()
        ->toArray();

    $gamesForModal = $upcomingGames
        ->mapWithKeys(function ($game) {
            $gameDateTime = date_create_immutable((string) $game->start_date_time);
            $sport = \App\Models\Sport::tryFrom((string) $game->season?->sport);
            $sportLabel = $sport?->value ?? ((string) $game->season?->sport ?: 'Sport unavailable');
            $sportIcon = $sport?->htmlEntity()->character();

            $startLabel = $gameDateTime instanceof \DateTimeImmutable
                ? ($game->start_time_tbd
                    ? $gameDateTime->format('M j, Y').' (TBD)'
                    : $gameDateTime->format('M j, Y g:i A'))
                : 'Start time unavailable';

            $homeLogo = collect($game->homeTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));
            $awayLogo = collect($game->awayTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));

            return [
                $game->id => [
                    'id' => $game->id,
                    'homeTeam' => $game->homeTeam->display_name,
                    'awayTeam' => $game->awayTeam->display_name,
                    'homeLogo' => $homeLogo,
                    'awayLogo' => $awayLogo,
                    'startLabel' => $startLabel,
                    'sportLabel' => $sportLabel,
                    'sportIcon' => $sportIcon,
                ],
            ];
        })
        ->toArray();

    $predictionFormHasErrors = $errors->has('prediction')
        || $errors->has('player_id')
        || $errors->has('game_id')
        || $errors->has('home_team_prediction')
        || $errors->has('away_team_prediction')
        || $errors->has('prediction_id');
@endphp

<x-groups.section-card title="Upcoming games" description="Games available for your group's followed teams.">
    @if ($upcomingGames->isEmpty())
        <x-empty-state
            title="No upcoming games"
            description="Upcoming games for your followed teams will appear here."
            :buttonText="null"
            :buttonRoute="null"
        />
    @elseif ($memberPlayers->isEmpty())
        <x-empty-state
            title="Create a player first"
            description="You need a player before you can submit predictions."
            buttonText="Create player"
            buttonRoute="groups.members.players.create"
            :buttonParams="['group' => $group, 'member' => $currentMember]"
        />
    @else
        <div
            x-data="{
                games: @js($gamesForModal),
                players: @js($playerOptions),
                predictions: @js($predictionLookup),
                storeTemplate: @js($storePredictionRouteTemplate),
                updateTemplate: @js($updatePredictionRouteTemplate),
                activeGame: null,
                activeGameId: @js(old('game_id')),
                selectedPlayerId: @js(old('player_id')),
                predictionId: @js(old('prediction_id')),
                homeTeamPrediction: @js(old('home_team_prediction')),
                awayTeamPrediction: @js(old('away_team_prediction')),
                formErrors: {},
                predictionError: @js($errors->first('prediction')),
                isEditing: false,
                formAction: '',
                isSubmitting: false,

                init() {
                    if (! this.selectedPlayerId && this.players.length > 0) {
                        this.selectedPlayerId = String(this.players[0].id)
                    }

                    if (this.activeGameId) {
                        this.openGame(String(this.activeGameId), true)
                    }
                },

                openGame(gameId, preserveFormValues = false) {
                    const game = this.games[gameId]

                    if (! game) {
                        return
                    }

                    this.activeGame = game
                    this.activeGameId = String(game.id)
                    this.clearErrors()

                    if (! this.selectedPlayerId && this.players.length > 0) {
                        this.selectedPlayerId = String(this.players[0].id)
                    }

                    this.syncPredictionState(preserveFormValues)
                    this.$dispatch('open-modal', 'prediction-modal')
                },

                syncPredictionState(preserveFormValues = false) {
                    if (! this.activeGameId || ! this.selectedPlayerId) {
                        return
                    }

                    const predictionKey = `${this.activeGameId}:${this.selectedPlayerId}`
                    const existingPrediction = this.predictions[predictionKey] ?? null

                    this.isEditing = !!existingPrediction

                    if (existingPrediction) {
                        this.predictionId = existingPrediction.id
                        this.formAction = this.updateTemplate
                            .replace('__PLAYER__', this.playerUlidById(this.selectedPlayerId))
                            .replace('__PREDICTION__', existingPrediction.ulid)
                    } else {
                        this.predictionId = null
                        this.formAction = this.storeTemplate
                            .replace('__PLAYER__', this.playerUlidById(this.selectedPlayerId))
                    }

                    if (! preserveFormValues) {
                        this.homeTeamPrediction = existingPrediction ? String(existingPrediction.home_team_prediction) : ''
                        this.awayTeamPrediction = existingPrediction ? String(existingPrediction.away_team_prediction) : ''
                    }
                },

                playerUlidById(playerId) {
                    const player = this.players.find((candidate) => String(candidate.id) === String(playerId))

                    return player ? player.ulid : ''
                },

                clearErrors() {
                    this.formErrors = {}
                    this.predictionError = ''
                },

                fieldError(name) {
                    return this.formErrors[name]?.[0] ?? ''
                },

                async submitPrediction() {
                    this.clearErrors()
                    this.isSubmitting = true

                    const formPayload = new URLSearchParams()
                    formPayload.append('game_id', this.activeGameId ?? '')
                    formPayload.append('player_id', this.selectedPlayerId ?? '')
                    formPayload.append('home_team_prediction', this.homeTeamPrediction ?? '')
                    formPayload.append('away_team_prediction', this.awayTeamPrediction ?? '')

                    if (this.isEditing) {
                        formPayload.append('prediction_id', String(this.predictionId ?? ''))
                    }

                    try {
                        const csrfToken = this.$refs.predictionForm.querySelector('input[name=\'_token\']').value
                        const response = await fetch(this.formAction, {
                            method: this.isEditing ? 'PATCH' : 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formPayload.toString(),
                            credentials: 'same-origin',
                        })

                        const payload = await response.json().catch(() => ({}))

                        if (! response.ok) {
                            this.formErrors = payload.errors ?? {}
                            this.predictionError = this.formErrors.prediction?.[0] ?? payload.message ?? 'Could not save prediction.'
                            return
                        }

                        if (payload.prediction) {
                            const predictionKey = `${payload.prediction.game_id}:${payload.prediction.player_id}`
                            this.predictions[predictionKey] = {
                                id: payload.prediction.id,
                                ulid: payload.prediction.ulid,
                                home_team_prediction: payload.prediction.home_team_prediction,
                                away_team_prediction: payload.prediction.away_team_prediction,
                            }
                        }

                        this.syncPredictionState(false)
                        this.$dispatch('close-modal', 'prediction-modal')
                        window.location.reload()
                    } finally {
                        this.isSubmitting = false
                    }
                },
            }"
            class="space-y-3"
        >
            @foreach ($upcomingGames as $game)
                @php
                    $gameDateTime = date_create_immutable((string) $game->start_date_time);
                    $sport = \App\Models\Sport::tryFrom((string) $game->season?->sport);
                    $sportLabel = $sport?->value ?? ((string) $game->season?->sport ?: 'Sport unavailable');
                    $sportIcon = $sport?->htmlEntity()->character();
                    $isBeforeLock = true;

                    if ($gameDateTime instanceof \DateTimeImmutable) {
                        if ($game->start_time_tbd) {
                            $isBeforeLock = $gameDateTime->format('Y-m-d') >= now()->toDateString();
                        } else {
                            $isBeforeLock = $gameDateTime >= new \DateTimeImmutable('now');
                        }
                    }

                    $isSeasonActive = (bool) $game->season?->active;
                    $isOpen = $isSeasonActive && $isBeforeLock;

                    $statusLabel = $isOpen ? 'Open' : 'Closed';
                    $statusClass = $isOpen
                        ? 'bg-green-100 text-green-700 ring-green-200'
                        : 'bg-gray-100 text-gray-700 ring-gray-200';

                    if (! $isSeasonActive) {
                        $statusReason = 'Season inactive';
                    } elseif (! $isBeforeLock) {
                        $statusReason = 'Prediction locked';
                    } else {
                        $statusReason = 'Open for prediction';
                    }

                    $startLabel = $gameDateTime instanceof \DateTimeImmutable
                        ? ($game->start_time_tbd
                            ? $gameDateTime->format('M j, Y').' (TBD)'
                            : $gameDateTime->format('M j, Y g:i A'))
                        : 'Start time unavailable';

                    $homeLogo = collect($game->homeTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));
                    $awayLogo = collect($game->awayTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));
                @endphp

                <button
                    type="button"
                    class="w-full cursor-pointer rounded-lg border border-gray-200 px-4 py-3 text-left transition hover:border-navy/40 hover:bg-gray-50"
                    @click="openGame('{{ $game->id }}')"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $game->awayTeam->display_name }} at {{ $game->homeTeam->display_name }}
                            </p>

                            <p class="mt-1 text-xs font-medium text-gray-600">
                                @if ($sportIcon)
                                    <span aria-hidden="true">{{ $sportIcon }}</span>
                                @endif
                                <span>{{ $sportLabel }}</span>
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                @if ($awayLogo)
                                    <img src="{{ $awayLogo }}" alt="{{ $game->awayTeam->display_name }} logo" class="h-7 w-7 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                                @endif
                                @if ($homeLogo)
                                    <img src="{{ $homeLogo }}" alt="{{ $game->homeTeam->display_name }} logo" class="h-7 w-7 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                                @endif
                            </div>

                            <p class="mt-2 text-xs text-gray-500">{{ $startLabel }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $statusReason }}</span>
                        </div>
                    </div>
                </button>
            @endforeach

            <x-modal name="prediction-modal" :show="$predictionFormHasErrors" maxWidth="lg" focusable>
                <form x-ref="predictionForm" method="POST" :action="formAction" @submit.prevent="submitPrediction" class="space-y-4 p-6">
                    @csrf

                    <input type="hidden" name="game_id" :value="activeGameId">
                    <input type="hidden" name="player_id" :value="selectedPlayerId">
                    <input type="hidden" name="prediction_id" :value="predictionId">

                    <div class="border-b border-gray-100 pb-4">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="isEditing ? 'Edit prediction' : 'Submit prediction'"></h3>
                        <p class="mt-1 text-sm text-gray-600">
                            <span x-text="activeGame ? activeGame.awayTeam : ''"></span>
                            <span> at </span>
                            <span x-text="activeGame ? activeGame.homeTeam : ''"></span>
                        </p>
                        <p class="mt-1 text-xs font-medium text-gray-600">
                            <span x-show="activeGame && activeGame.sportIcon" aria-hidden="true" x-text="activeGame ? activeGame.sportIcon : ''"></span>
                            <span x-text="activeGame ? activeGame.sportLabel : ''"></span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500" x-text="activeGame ? activeGame.startLabel : ''"></p>

                        <div class="mt-3 flex items-center gap-2">
                            <template x-if="activeGame && activeGame.awayLogo">
                                <img :src="activeGame.awayLogo" alt="Away logo" class="h-9 w-9 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                            </template>
                            <template x-if="activeGame && activeGame.homeLogo">
                                <img :src="activeGame.homeLogo" alt="Home logo" class="h-9 w-9 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                            </template>
                        </div>
                    </div>

                    <template x-if="predictionError">
                        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="predictionError"></div>
                    </template>

                    <div>
                        <x-form.select
                            name="player_selector"
                            label="Player"
                            :required="true"
                            value=""
                            :options="collect($playerOptions)->mapWithKeys(fn ($player) => [(string) $player['id'] => $player['name']])->toArray()"
                            x-model="selectedPlayerId"
                            x-on:change="syncPredictionState(false)"
                        />
                        <template x-if="fieldError('player_id')">
                            <p class="mt-2 text-sm text-red-600" x-text="fieldError('player_id')"></p>
                        </template>
                        <template x-if="fieldError('game_id')">
                            <p class="mt-2 text-sm text-red-600" x-text="fieldError('game_id')"></p>
                        </template>
                    </div>

                    <div>
                        <label for="home_team_prediction" class="block text-sm font-semibold text-gray-900">
                            <span class="flex items-center gap-2">
                                <template x-if="activeGame && activeGame.homeLogo">
                                    <img :src="activeGame.homeLogo" alt="Home team logo" class="h-8 w-8 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                                </template>
                                <span x-text="activeGame ? `${activeGame.homeTeam} prediction` : 'Home team prediction'"></span>
                            </span>
                        </label>
                        <x-inputs.text-input
                            id="home_team_prediction"
                            name="home_team_prediction"
                            type="number"
                            class="mt-2 block w-full"
                            min="0"
                            required
                            x-model="homeTeamPrediction"
                        />
                        <template x-if="fieldError('home_team_prediction')">
                            <p class="mt-2 text-sm text-red-600" x-text="fieldError('home_team_prediction')"></p>
                        </template>
                    </div>

                    <div>
                        <label for="away_team_prediction" class="block text-sm font-semibold text-gray-900">
                            <span class="flex items-center gap-2">
                                <template x-if="activeGame && activeGame.awayLogo">
                                    <img :src="activeGame.awayLogo" alt="Away team logo" class="h-8 w-8 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                                </template>
                                <span x-text="activeGame ? `${activeGame.awayTeam} prediction` : 'Away team prediction'"></span>
                            </span>
                        </label>
                        <x-inputs.text-input
                            id="away_team_prediction"
                            name="away_team_prediction"
                            type="number"
                            class="mt-2 block w-full"
                            min="0"
                            required
                            x-model="awayTeamPrediction"
                        />
                        <template x-if="fieldError('away_team_prediction')">
                            <p class="mt-2 text-sm text-red-600" x-text="fieldError('away_team_prediction')"></p>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="$dispatch('close-modal', 'prediction-modal')"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md bg-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                        >
                            <span x-text="isSubmitting ? 'Saving...' : (isEditing ? 'Update prediction' : 'Submit prediction')"></span>
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    @endif
</x-groups.section-card>
