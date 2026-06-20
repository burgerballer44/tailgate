<x-layouts.app
    mainHeading="Prediction Rules"
    mainDescription="Current app-level and group-level prediction policies used to validate submissions."
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Rules', 'active' => true],
        ]"
    />

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">App-level policies</h2>
                <p class="mt-1 text-sm text-gray-600">These rules apply to every prediction submission.</p>
            </div>

            <div class="space-y-4">
                @foreach ($appRules as $rule)
                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $rule->label() }}</p>
                                <p class="text-xs text-gray-500">{{ $rule->key() }}</p>
                            </div>
                            <span class="rounded-full bg-navy/10 px-3 py-1 text-xs font-medium text-navy">{{ $rule->scope()->value }}</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-700">{{ $rule->description() }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Group-level policies</h2>
                <p class="mt-1 text-sm text-gray-600">These rules are opt-in per group and stored on the group record.</p>
            </div>

            <div class="space-y-4">
                @foreach ($groupRules as $rule)
                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $rule->label() }}</p>
                                <p class="text-xs text-gray-500">{{ $rule->key() }}</p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900">{{ $rule->scope()->value }}</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-700">{{ $rule->description() }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>