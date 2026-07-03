<x-modal name="dashboard-quick-predictions-modal" maxWidth="2xl" focusable>
    <div class="space-y-4 p-6">
        <div class="flex items-start justify-between border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Quick predictions</h3>
                <p class="mt-1 text-sm text-gray-600">Submit scores with AJAX and move between games with consistent loading.</p>
            </div>
            <button
                type="button"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="$dispatch('close-modal', 'dashboard-quick-predictions-modal')"
            >
                Close
            </button>
        </div>

        <template x-if="isLoading">
            <div class="space-y-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-4">
                <div class="h-4 w-40 animate-pulse rounded bg-gray-200"></div>
                <div class="h-3 w-64 animate-pulse rounded bg-gray-200"></div>
                <div class="h-24 animate-pulse rounded bg-gray-200"></div>
            </div>
        </template>

        <template x-if="!isLoading && games.length === 0">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                No upcoming followed-team games were found in the {{ $quickPredictionWindowLabel }}.
            </div>
        </template>

        <template x-if="!isLoading && games.length > 0">
            <div class="space-y-4">
                <template x-if="groupBuckets.length > 1">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Groups</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="bucket in groupBuckets" :key="`group-${bucket.group.ulid}`">
                                <button
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                    :class="selectedGroupUlid === bucket.group.ulid ? 'border-navy bg-navy text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    @click="switchGroup(bucket.group.ulid)"
                                >
                                    <span x-text="bucket.group.name"></span>
                                    <span class="ml-1 text-[11px]" x-text="`(${groupOpenCounts[bucket.group.ulid] ?? 0} open)`"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold text-gray-900" x-text="currentGroupName()"></span>
                        <span class="mx-1">-</span>
                        <span>Game </span>
                        <span class="font-semibold text-gray-900" x-text="currentIndex + 1"></span>
                        <span> of </span>
                        <span class="font-semibold text-gray-900" x-text="currentGroupGames().length"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="currentIndex === 0"
                            @click="previousGame()"
                        >
                            Prev
                        </button>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="currentGroupGames().length === 0 || currentIndex >= (currentGroupGames().length - 1)"
                            @click="nextGame()"
                        >
                            Next
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Group and scope</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900" x-text="currentGame().group.name"></p>
                            <p class="mt-1 text-xs text-gray-500">
                                <span x-text="currentGame().team.name"></span>
                                <span>-</span>
                                <span x-show="currentGame().team.sport_icon" aria-hidden="true" x-text="currentGame().team.sport_icon"></span>
                                <span x-text="currentGame().team.sport"></span>
                            </p>
                        </div>

                        <a
                            :href="currentGame().group_upcoming_games_route"
                            class="inline-flex items-center rounded-md border border-navy px-3 py-2 text-xs font-semibold text-navy hover:bg-navy hover:text-white"
                        >
                            View full group game list
                        </a>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-sm font-semibold text-gray-900">
                            <span x-text="currentGame().game.away_team"></span>
                            <span> at </span>
                            <span x-text="currentGame().game.home_team"></span>
                        </p>
                        <p class="mt-1 text-xs font-medium text-gray-600">
                            <span x-show="currentGame().game.sport_icon" aria-hidden="true" x-text="currentGame().game.sport_icon"></span>
                            <span x-text="currentGame().game.sport_label"></span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500" x-text="currentGame().game.start_label"></p>

                        <div class="mt-2 flex items-center gap-2">
                            <template x-if="currentGame().game.away_logo">
                                <img :src="currentGame().game.away_logo" alt="Away team logo" class="h-8 w-8 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                            </template>
                            <template x-if="currentGame().game.home_logo">
                                <img :src="currentGame().game.home_logo" alt="Home team logo" class="h-8 w-8 rounded-full bg-white object-contain ring-1 ring-gray-200" loading="lazy" />
                            </template>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="currentGame().game.is_open ? 'bg-green-100 text-green-700 ring-green-200' : 'bg-gray-100 text-gray-700 ring-gray-200'"
                                x-text="currentGame().game.status_label"
                            ></span>
                            <span class="text-xs text-gray-500" x-text="currentGame().game.status_reason"></span>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Player status</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="player in currentGame().players" :key="`status-${currentGame().context_key}-${player.id}`">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="player.prediction ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'"
                                >
                                    <span x-text="player.name"></span>
                                    <span>: </span>
                                    <span x-text="player.prediction ? `Submitted (${player.prediction.away_team_prediction}-${player.prediction.home_team_prediction})` : 'Not submitted'"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <template x-if="!currentGame().game.is_open">
                        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                            This game is closed for prediction.
                        </div>
                    </template>

                    <template x-if="currentGame().game.is_open && currentGame().players.length === 0">
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            You're almost ready. Add a player to this group first, then come back to submit your prediction.
                        </div>
                    </template>

                    <template x-if="currentGame().game.is_open && currentGame().players.length > 0">
                        <form class="mt-4 space-y-3" @submit.prevent="submitCurrentGamePrediction()">
                            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                <div>Group: <span x-text="currentGame().group.name"></span></div>
                                <div>Submitting for player: <span class="font-semibold" x-text="selectedPlayerName()"></span></div>
                            </div>

                            <template x-if="currentContextError()">
                                <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="currentContextError()"></p>
                            </template>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900" for="quick_prediction_player">Player</label>
                                    <select
                                        id="quick_prediction_player"
                                        class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :value="selectedPlayerForCurrentGame()"
                                        @change="onCurrentGamePlayerChange($event.target.value)"
                                    >
                                        <template x-for="player in currentGame().players" :key="`select-${currentGame().context_key}-${player.id}`">
                                            <option :value="player.id" x-text="player.name"></option>
                                        </template>
                                    </select>
                                    <template x-if="fieldError('player_id')">
                                        <p class="mt-2 text-sm text-red-600" x-text="fieldError('player_id')"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-900" for="quick_prediction_home_score" x-text="currentGame().game.home_team"></label>
                                    <input
                                        id="quick_prediction_home_score"
                                        type="number"
                                        min="0"
                                        class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :value="scoreField(currentGame().context_key).home"
                                        @input="updateScore('home', $event.target.value)"
                                    />
                                    <template x-if="fieldError('home_team_prediction')">
                                        <p class="mt-2 text-sm text-red-600" x-text="fieldError('home_team_prediction')"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-900" for="quick_prediction_away_score" x-text="currentGame().game.away_team"></label>
                                    <input
                                        id="quick_prediction_away_score"
                                        type="number"
                                        min="0"
                                        class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :value="scoreField(currentGame().context_key).away"
                                        @input="updateScore('away', $event.target.value)"
                                    />
                                    <template x-if="fieldError('away_team_prediction')">
                                        <p class="mt-2 text-sm text-red-600" x-text="fieldError('away_team_prediction')"></p>
                                    </template>
                                </div>
                            </div>

                            <template x-if="fieldError('game_id')">
                                <p class="text-sm text-red-600" x-text="fieldError('game_id')"></p>
                            </template>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-navy px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy/90 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="isSaving"
                                >
                                    <span x-text="isSaving ? 'Saving...' : (isEditingCurrentGame() ? 'Update prediction' : 'Submit prediction')"></span>
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-modal>

<script>
    function quickPredictionsModalController({ dataUrl, csrfToken }) {
        return {
            dataUrl,
            csrfToken,
            minimumLoadingMs: 1000,
            isLoading: false,
            isSaving: false,
            isLoaded: false,
            loadError: '',
            summaryLoaded: false,
            summary: {
                open_prediction_count: 0,
                total_games: 0,
                total_groups: 0,
            },
            games: [],
            groupBuckets: [],
            groupOpenCounts: {},
            selectedGroupUlid: '',
            currentIndex: 0,
            selectedPlayers: {},
            scoreFields: {},
            contextErrors: {},

            openModal() {
                this.$dispatch('open-modal', 'dashboard-quick-predictions-modal')

                if (this.isLoaded || this.isLoading) {
                    return
                }

                this.loadQuickPredictions()
            },

            async loadQuickPredictions() {
                this.isLoading = true
                this.loadError = ''
                const startedAt = Date.now()

                try {
                    const response = await fetch(this.dataUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    })

                    const payload = await response.json().catch(() => ({}))

                    if (! response.ok) {
                        this.loadError = payload.message ?? 'Could not load quick predictions.'
                        return
                    }

                    this.summary = payload.summary ?? this.summary
                    this.summaryLoaded = true
                    this.games = payload.games ?? []

                    this.buildGroupBuckets()
                    this.currentIndex = 0

                    this.initializeGameState()
                    this.recalculateOpenPredictionCount()
                    this.isLoaded = true
                } finally {
                    const elapsed = Date.now() - startedAt

                    if (elapsed < this.minimumLoadingMs) {
                        await new Promise((resolve) => setTimeout(resolve, this.minimumLoadingMs - elapsed))
                    }

                    this.isLoading = false
                }
            },

            buildGroupBuckets() {
                const grouped = {}

                this.games.forEach((game) => {
                    const groupUlid = game.group?.ulid ?? ''

                    if (! grouped[groupUlid]) {
                        grouped[groupUlid] = {
                            group: game.group,
                            games: [],
                        }
                    }

                    grouped[groupUlid].games.push(game)
                })

                this.groupBuckets = Object.values(grouped)
                    .map((bucket) => ({
                        ...bucket,
                        games: [...bucket.games].sort((a, b) => {
                            const left = a.game?.start_sort ?? '99999999999999'
                            const right = b.game?.start_sort ?? '99999999999999'
                            return left.localeCompare(right)
                        }),
                    }))
                    .sort((a, b) => (a.group?.name ?? '').localeCompare(b.group?.name ?? ''))

                this.selectedGroupUlid = this.groupBuckets[0]?.group?.ulid ?? ''
            },

            initializeGameState() {
                this.games.forEach((game) => {
                    if (! game.players || game.players.length === 0) {
                        return
                    }

                    const firstPlayerId = String(game.players[0].id)
                    this.selectedPlayers[game.context_key] = firstPlayerId
                    this.syncFormToPlayer(game.context_key, firstPlayerId)
                })
            },

            switchGroup(groupUlid) {
                this.selectedGroupUlid = groupUlid
                this.currentIndex = 0
            },

            currentGroup() {
                return this.groupBuckets.find((bucket) => bucket.group?.ulid === this.selectedGroupUlid) ?? {
                    group: { ulid: '', name: '' },
                    games: [],
                }
            },

            currentGroupName() {
                return this.currentGroup().group?.name ?? 'Group'
            },

            currentGroupGames() {
                return this.currentGroup().games ?? []
            },

            currentGame() {
                const games = this.currentGroupGames()

                return games[this.currentIndex] ?? {
                    context_key: '',
                    group: { name: '', member_ulid: '' },
                    team: { name: '', sport: '' },
                    game: { is_open: false, status_label: '', status_reason: '', home_team: '', away_team: '', start_label: '' },
                    players: [],
                    store_route_template: '',
                    update_route_template: '',
                    group_upcoming_games_route: '#',
                }
            },

            previousGame() {
                const games = this.currentGroupGames()

                if (games.length === 0 || this.currentIndex === 0) {
                    return
                }

                this.currentIndex -= 1
            },

            nextGame() {
                const games = this.currentGroupGames()

                if (games.length === 0 || this.currentIndex >= games.length - 1) {
                    return
                }

                this.currentIndex += 1
            },

            selectedPlayerForCurrentGame() {
                const game = this.currentGame()
                return this.selectedPlayers[game.context_key] ?? ''
            },

            selectedPlayerName() {
                const game = this.currentGame()
                const selectedId = this.selectedPlayerForCurrentGame()
                const player = game.players.find((candidate) => String(candidate.id) === String(selectedId))

                return player ? player.name : 'No player selected'
            },

            onCurrentGamePlayerChange(playerId) {
                const game = this.currentGame()
                const normalizedId = String(playerId)

                this.selectedPlayers[game.context_key] = normalizedId
                this.syncFormToPlayer(game.context_key, normalizedId)
                this.contextErrors[game.context_key] = { fields: {}, message: '' }
            },

            syncFormToPlayer(contextKey, playerId) {
                const game = this.games.find((candidate) => candidate.context_key === contextKey)

                if (! game) {
                    return
                }

                const player = game.players.find((candidate) => String(candidate.id) === String(playerId))
                const prediction = player?.prediction ?? null

                this.scoreFields[contextKey] = {
                    home: prediction ? String(prediction.home_team_prediction) : '',
                    away: prediction ? String(prediction.away_team_prediction) : '',
                    predictionId: prediction ? String(prediction.id) : '',
                }
            },

            scoreField(contextKey) {
                if (! this.scoreFields[contextKey]) {
                    this.scoreFields[contextKey] = { home: '', away: '', predictionId: '' }
                }

                return this.scoreFields[contextKey]
            },

            updateScore(side, value) {
                const game = this.currentGame()
                const fields = this.scoreField(game.context_key)
                fields[side] = value
            },

            currentContextError() {
                const context = this.contextErrors[this.currentGame().context_key] ?? { message: '' }
                return context.message ?? ''
            },

            fieldError(field) {
                const context = this.contextErrors[this.currentGame().context_key] ?? { fields: {} }
                return context.fields?.[field]?.[0] ?? ''
            },

            isEditingCurrentGame() {
                const game = this.currentGame()
                const selectedPlayerId = this.selectedPlayerForCurrentGame()
                const player = game.players.find((candidate) => String(candidate.id) === String(selectedPlayerId))

                return !!player?.prediction
            },

            async submitCurrentGamePrediction() {
                const game = this.currentGame()
                const selectedPlayerId = this.selectedPlayerForCurrentGame()

                if (! game.context_key || ! selectedPlayerId) {
                    return
                }

                const player = game.players.find((candidate) => String(candidate.id) === String(selectedPlayerId))
                const existingPrediction = player?.prediction ?? null

                if (! player) {
                    return
                }

                const route = existingPrediction
                    ? game.update_route_template
                        .replace('__PLAYER__', player.ulid)
                        .replace('__PREDICTION__', existingPrediction.ulid)
                    : game.store_route_template
                        .replace('__PLAYER__', player.ulid)

                const fields = this.scoreField(game.context_key)
                const formPayload = new URLSearchParams()
                formPayload.append('redirect_to', 'dashboard')
                formPayload.append('dashboard_prediction_context', game.context_key)
                formPayload.append('game_id', String(game.game.id))
                formPayload.append('player_id', String(selectedPlayerId))
                formPayload.append('home_team_prediction', String(fields.home ?? ''))
                formPayload.append('away_team_prediction', String(fields.away ?? ''))

                if (existingPrediction) {
                    formPayload.append('prediction_id', String(existingPrediction.id))
                }

                this.isSaving = true
                this.contextErrors[game.context_key] = { fields: {}, message: '' }

                try {
                    const response = await fetch(route, {
                        method: existingPrediction ? 'PATCH' : 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formPayload.toString(),
                        credentials: 'same-origin',
                    })

                    const payload = await response.json().catch(() => ({}))

                    if (! response.ok) {
                        this.contextErrors[game.context_key] = {
                            fields: payload.errors ?? {},
                            message: payload.errors?.prediction?.[0] ?? payload.message ?? 'Could not save prediction.',
                        }
                        return
                    }

                    if (payload.prediction && player) {
                        player.prediction = {
                            id: payload.prediction.id,
                            ulid: payload.prediction.ulid,
                            home_team_prediction: payload.prediction.home_team_prediction,
                            away_team_prediction: payload.prediction.away_team_prediction,
                        }
                    }

                    this.syncFormToPlayer(game.context_key, selectedPlayerId)
                    this.recalculateOpenPredictionCount()
                } finally {
                    this.isSaving = false
                }
            },

            recalculateOpenPredictionCount() {
                let openCount = 0
                const groupOpenCounts = {}

                this.games.forEach((game) => {
                    const groupUlid = game.group?.ulid ?? ''
                    groupOpenCounts[groupUlid] = groupOpenCounts[groupUlid] ?? 0

                    if (! game.game.is_open) {
                        return
                    }

                    game.players.forEach((player) => {
                        if (! player.prediction) {
                            openCount++
                            groupOpenCounts[groupUlid]++
                        }
                    })
                })

                this.groupOpenCounts = groupOpenCounts
                this.summary.open_prediction_count = openCount
                this.summaryLoaded = true
            },
        }
    }
</script>
