<x-groups.section-card title="Group snapshot" description="Quick status for this group.">
    @php
        $followedTeams = $group->follow_collection;
        $followedSeasons = $group->seasonFollows
            ->filter(fn ($seasonFollow) => $seasonFollow->season !== null)
            ->sortBy(fn ($seasonFollow) => $seasonFollow->season->name)
            ->values();
    @endphp

    <div class="-mx-6 -my-5 grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-4 sm:divide-y-0">
        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Followed teams</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $followedTeams->count() }}</p>
        </div>

        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Followed seasons</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $followedSeasons->count() }}</p>
        </div>

        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Owner</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $group->owner->name }}</p>
        </div>

        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Members</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">
                {{ $group->members_count }}
                <span class="font-normal text-gray-400">/ {{ $group->member_limit }}</span>
            </p>
        </div>

        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Invite code</p>
            <p class="mt-1 font-mono text-sm font-semibold tracking-widest text-gray-900">{{ $group->invite_code }}</p>
        </div>
    </div>

</x-groups.section-card>

<x-groups.section-card title="Followed teams and seasons" description="Teams and seasons this group is configured to use for predictions.">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Followed teams</h3>

            @if ($followedTeams->isEmpty())
                <p class="mt-2 text-sm text-gray-500">No teams followed yet.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($followedTeams as $follow)
                        <li class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            {{ $follow->team->display_name }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-900">Followed seasons</h3>

            @if ($followedSeasons->isEmpty())
                <p class="mt-2 text-sm text-gray-500">No seasons followed yet.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($followedSeasons as $seasonFollow)
                        <li class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            {{ $seasonFollow->season->name }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</x-groups.section-card>

@if (auth()->id() !== $group->owner_id)
    <x-groups.section-card title="Leave group" description="Leave this group without losing your historical contribution data.">
        <div class="space-y-3">
            <p class="text-sm text-gray-700">
                Leaving removes your active participation, but your existing players and past predictions in this group remain preserved for historical results.
            </p>

            <form action="{{ route('groups.leave', ['group' => $group]) }}" method="POST" onsubmit="return confirm('Leave this group? Your historical players and predictions will be preserved.');">
                @csrf
                @method('DELETE')

                <x-buttons.danger-button type="submit">
                    Leave group
                </x-buttons.danger-button>
            </form>
        </div>
    </x-groups.section-card>
@endif
