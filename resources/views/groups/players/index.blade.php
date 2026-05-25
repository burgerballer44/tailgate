@php
    $playerCount = $players->total();
    $playerLimit = $group->player_limit;
@endphp

<x-layouts.app
    mainHeading="{{ auth()->id() === $member->user_id ? 'My players in' : 'Players for '.$member->user->name.' in' }} {{ $group->name }}"
    mainDescription="Manage players and submit score predictions."
    :mainActions="collect([
        ['text' => 'Back to group', 'route' => 'groups.show', 'params' => ['group' => $group]],
        $playerCount < $playerLimit ? [
            'text' => 'Create player',
            'route' => $routeBaseName.'.create',
            'params' => ['group' => $group, 'member' => $member],
        ] : null,
    ])->filter()->values()->toArray()"
>
    <div class="space-y-6">
        {{-- Player Count Info --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-sm text-gray-700">
                You have <span class="font-semibold">{{ $playerCount }}</span>
                <span class="text-gray-500">/ {{ $playerLimit }} players</span>
            </p>
            @if ($playerCount >= $playerLimit)
                <p class="mt-1 text-xs text-amber-600">
                    You've reached your player limit. Delete a player to create a new one.
                </p>
            @endif
        </div>

        {{-- Players List --}}
        @if ($players->isEmpty())
            <x-empty-state
                title="No players yet"
                description="Create your first player to start submitting score predictions."
                buttonText="Create player"
                :buttonRoute="$routeBaseName.'.create'"
                :buttonParams="['group' => $group, 'member' => $member]"
            />
        @else
            <div class="space-y-3">
                @foreach ($players as $player)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $player->player_name }}</h3>
                            <p class="text-xs text-gray-500">{{ $player->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route($routeBaseName.'.edit', ['group' => $group, 'member' => $member, 'player' => $player]) }}"
                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                            >
                                Edit
                            </a>
                            <form method="POST" action="{{ route($routeBaseName.'.destroy', ['group' => $group, 'member' => $member, 'player' => $player]) }}">
                                @csrf
                                @method('DELETE')
                                <x-buttons.danger-button type="submit" confirm="Are you sure you want to delete this player?">
                                    Delete
                                </x-buttons.danger-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $players->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
