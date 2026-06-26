@php
    $followDescription = 'No team followed yet.';
    $follows = $group->follow_collection;
    if ($follows->isNotEmpty()) {
        $followDescription = $follows->count().' team'.($follows->count() === 1 ? '' : 's').' followed';
    }

    $tabs = [
        'details' => 'Details',
        'players' => 'My players',
        'upcoming-games' => 'Upcoming games',
    ];
@endphp

<x-layouts.app
    mainHeading="{{ $group->name }}"
    mainDescription="{{ $followDescription }}"
    :mainActions="collect([
        ['text' => 'Back to dashboard', 'route' => 'dashboard'],
        $group->isAdminOrOwner(auth()->user()) ? [
            'text' => 'Manage group',
            'route' => 'groups.edit',
            'params' => ['group' => $group],
        ] : null,
    ])->filter()->values()->toArray()"
>
    <div class="mt-2">
        <div class="grid grid-cols-1 sm:hidden">
            <x-form.select
                id="group-tab-selector"
                name="group_tab_selector"
                label="Select a tab"
                labelClass="sr-only"
                :value="route('groups.show', ['group' => $group->ulid, 'tab' => $activeTab])"
                :options="collect($tabs)->mapWithKeys(fn ($label, $key) => [route('groups.show', ['group' => $group->ulid, 'tab' => $key]) => $label])->toArray()"
                containerClass="col-start-1 row-start-1"
                class="w-full appearance-none py-2 text-base focus:outline-indigo-600"
                aria-label="Select a tab"
                onchange="window.location.href = this.value"
            />
            <svg
                viewBox="0 0 16 16"
                fill="currentColor"
                aria-hidden="true"
                class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end fill-gray-500"
            >
                <path
                    d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                />
            </svg>
        </div>

        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav aria-label="Tabs" class="-mb-px flex space-x-8">
                    @foreach ($tabs as $key => $label)
                        @php
                            $isActive = $activeTab === $key;
                        @endphp

                        <a
                            href="{{ route('groups.show', ['group' => $group->ulid, 'tab' => $key]) }}"
                            @class([
                                'inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium',
                                'border-navy text-navy' => $isActive,
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! $isActive,
                            ])
                            @if($isActive) aria-current="page" @endif
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        @if ($activeTab === 'details')
            @include('groups.tabs.details')
        @endif

        @if ($activeTab === 'players')
            @include('groups.tabs.players')
        @endif

        @if ($activeTab === 'upcoming-games')
            @include('groups.tabs.upcoming-games')
        @endif
    </div>
</x-layouts.app>