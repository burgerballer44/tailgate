<section
    x-data="quickPredictionsModalController({
        dataUrl: @js(route('dashboard.quick-predictions')),
        csrfToken: @js(csrf_token()),
    })"
    class="bg-white shadow sm:rounded-md"
>
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6 dark:border-white/10">
        <div class="-mt-4 -ml-4 flex flex-wrap items-center justify-between gap-3 sm:flex-nowrap">
            <div class="mt-4 ml-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Quick predictions ({{ $quickPredictionWindowLabel }})</h3>
                <template x-if="summaryLoaded">
                    <p class="mt-1 text-sm" :class="summary.open_prediction_count > 0 ? 'text-green-700 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">
                        <span x-show="summary.open_prediction_count > 0" x-text="`You still have ${summary.open_prediction_count} open prediction slot${summary.open_prediction_count === 1 ? '' : 's'} across your groups.`"></span>
                        <span x-show="summary.open_prediction_count === 0">All caught up. New games in your followed teams will appear here automatically.</span>
                    </p>
                </template>
                <template x-if="!summaryLoaded">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Load your quick prediction queue without leaving the dashboard.</p>
                </template>
            </div>

            <div class="mt-4 ml-4 flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded-md bg-navy px-3 py-2 text-xs font-semibold text-white transition hover:bg-navy/90 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isLoading"
                    @click="openModal()"
                >
                    <span x-text="isLoading ? 'Loading...' : 'Open quick predictions'"></span>
                </button>
            </div>
        </div>

        <template x-if="loadError">
            <p class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="loadError"></p>
        </template>
    </div>

    @include('dashboard.partials.quick-predictions-modal')
</section>
