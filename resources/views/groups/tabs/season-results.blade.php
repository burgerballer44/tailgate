@php
    $resultsMode = $resultsMode ?? 'leaderboard';
    $highlightPlayerIds = collect($highlightPlayerIds ?? [])->map(static fn ($playerId): int => (int) $playerId)->values()->all();
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6" data-season-results-container data-mode="{{ $resultsMode }}" data-endpoint="{{ route('groups.season-results', ['group' => $group->ulid]) }}" data-highlight-player-ids='@json($highlightPlayerIds)'>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $resultsMode === 'leaderboard' ? 'Leaderboard' : 'Raw Prediction Data' }}
            </h3>
            <p class="text-sm text-gray-600">Season-scoped results for this group.</p>
        </div>

        @if ($resultsSeasonOptions !== [])
            <div class="w-full sm:w-72">
                <x-form.select
                    id="results-season-selector"
                    name="season_id"
                    label="Season"
                    :value="$selectedResultsSeasonId"
                    :options="$resultsSeasonOptions"
                    class="w-full"
                    aria-label="Season"
                />
            </div>
        @endif
    </div>

    @if ($resultsSeasonOptions === [])
        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            No followed seasons are available for results yet.
        </div>
    @else
        <div data-results-loading class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            Loading season results...
        </div>

        <div data-results-error class="hidden rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>

        <div data-results-empty class="hidden rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            No scorable games or prediction rows are available for this season yet.
        </div>

        <div data-results-content class="hidden"></div>

        <script>
            (() => {
                const container = document.currentScript.closest('[data-season-results-container]');
                if (!container) {
                    return;
                }

                const endpoint = container.dataset.endpoint;
                const mode = container.dataset.mode;
                const highlightPlayerIdsRaw = container.dataset.highlightPlayerIds || '[]';
                const seasonSelector = container.querySelector('#results-season-selector');
                const loadingState = container.querySelector('[data-results-loading]');
                const errorState = container.querySelector('[data-results-error]');
                const emptyState = container.querySelector('[data-results-empty]');
                const contentState = container.querySelector('[data-results-content]');

                let highlightPlayerIds = [];
                try {
                    highlightPlayerIds = JSON.parse(highlightPlayerIdsRaw);
                } catch (_) {
                    highlightPlayerIds = [];
                }

                const highlightPlayerIdSet = new Set(
                    (Array.isArray(highlightPlayerIds) ? highlightPlayerIds : [])
                        .map((playerId) => Number(playerId))
                        .filter((playerId) => Number.isInteger(playerId) && playerId > 0),
                );
                const hasHighlightedPlayers = highlightPlayerIdSet.size > 0;
                const isHighlightedPlayer = (playerId) => highlightPlayerIdSet.has(Number(playerId));

                const escapeHtml = (value) => {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                };

                const setState = ({ loading = false, error = '', empty = false }) => {
                    loadingState.classList.toggle('hidden', !loading);
                    errorState.classList.toggle('hidden', error === '');
                    emptyState.classList.toggle('hidden', !empty);
                    contentState.classList.toggle('hidden', loading || error !== '' || empty);

                    if (error !== '') {
                        errorState.textContent = error;
                    }
                };

                const formatGamePoints = (value) => {
                    const number = Number(value);
                    return Number.isFinite(number) ? number.toFixed(1) : '-';
                };

                const renderLeaderboard = (data) => {
                    const rows = data.leaderboard_rows ?? [];

                    if (rows.length === 0) {
                        setState({ empty: true });
                        contentState.innerHTML = '';
                        return;
                    }

                    const htmlRows = rows.map((row) => {
                        const isHighlighted = isHighlightedPlayer(row.player_id);
                        const playerLabel = isHighlighted
                            ? `${escapeHtml(row.player_name)} <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Your player</span>`
                            : escapeHtml(row.player_name);

                        return `
                            <tr class="${isHighlighted ? 'border-b border-amber-200 bg-amber-50' : 'border-b border-gray-100'}">
                                <td class="px-3 py-2 text-sm ${isHighlighted ? 'font-semibold text-amber-900' : 'text-gray-900'}">${playerLabel}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${formatGamePoints(row.total_points)}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(row.rank)}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${row.previous_rank ?? '-'}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(row.rank_change)}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${formatGamePoints(row.points_behind_leader)}</td>
                            </tr>
                        `;
                    }).join('');

                    const highlightedHint = hasHighlightedPlayers
                        ? '<p class="mb-3 text-xs font-medium text-amber-800">Highlighted rows show your players.</p>'
                        : '';

                    contentState.innerHTML = `
                        ${highlightedHint}
                        <div class="overflow-x-auto rounded-md border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Player</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Total points</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Rank</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Previous rank</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Rank change</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Behind leader</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${htmlRows}
                                </tbody>
                            </table>
                        </div>
                    `;

                    setState({});
                };

                const renderRawPredictionData = (data) => {
                    const gameRows = data.raw_game_rows ?? [];

                    if (gameRows.length === 0) {
                        setState({ empty: true });
                        contentState.innerHTML = '';
                        return;
                    }

                    const blocks = gameRows.map((game) => {
                        const playerRows = (game.player_rows ?? []).map((row) => {
                            const notes = (row.calculation_notes ?? []).map(escapeHtml).join(', ');
                            const isHighlighted = isHighlightedPlayer(row.player_id);
                            const playerLabel = isHighlighted
                                ? `${escapeHtml(row.player_name)} <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Your player</span>`
                                : escapeHtml(row.player_name);

                            return `
                                <tr class="${isHighlighted ? 'border-b border-amber-200 bg-amber-50' : 'border-b border-gray-100'}">
                                    <td class="px-3 py-2 text-sm ${isHighlighted ? 'font-semibold text-amber-900' : 'text-gray-900'}">${playerLabel}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">${row.predicted_followed_score ?? '-'}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">${row.predicted_opponent_score ?? '-'}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(row.penalty_points)}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">${formatGamePoints(row.game_points)}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700">${notes === '' ? '-' : notes}</td>
                                </tr>
                            `;
                        }).join('');

                        return `
                            <section class="rounded-md border border-gray-200 bg-white">
                                <header class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900">${escapeHtml(game.week_label)}: ${escapeHtml(game.followed_team)} vs ${escapeHtml(game.opponent_team)}</p>
                                    <p class="text-xs text-gray-600">
                                        Final: ${game.actual_followed_score ?? '-'} - ${game.actual_opponent_score ?? '-'}
                                        · Status: ${escapeHtml(game.game_status)}
                                    </p>
                                </header>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Player</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Pred followed</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Pred opponent</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Penalty</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Game points</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-600">Calculation notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${playerRows}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        `;
                    }).join('');

                    const highlightedHint = hasHighlightedPlayers
                        ? '<p class="text-xs font-medium text-amber-800">Highlighted rows show your players.</p>'
                        : '';

                    contentState.innerHTML = `<div class="space-y-4">${highlightedHint}${blocks}</div>`;
                    setState({});
                };

                const loadResults = async (seasonId) => {
                    if (!seasonId) {
                        setState({ empty: true });
                        contentState.innerHTML = '';
                        return;
                    }

                    setState({ loading: true, error: '', empty: false });

                    try {
                        const response = await fetch(`${endpoint}?season_id=${encodeURIComponent(seasonId)}`, {
                            headers: {
                                Accept: 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load results for the selected season.');
                        }

                        const payload = await response.json();
                        const data = payload.data ?? {};

                        if (mode === 'raw-prediction-data') {
                            renderRawPredictionData(data);
                        } else {
                            renderLeaderboard(data);
                        }
                    } catch (error) {
                        setState({ error: error.message || 'An unexpected error occurred while loading results.' });
                        contentState.innerHTML = '';
                    }
                };

                seasonSelector.addEventListener('change', (event) => {
                    const seasonId = event.target.value;
                    const url = new URL(window.location.href);

                    if (seasonId) {
                        url.searchParams.set('season_id', seasonId);
                    } else {
                        url.searchParams.delete('season_id');
                    }

                    window.location.href = url.toString();
                });

                loadResults(seasonSelector.value);
            })();
        </script>
    @endif
</div>
