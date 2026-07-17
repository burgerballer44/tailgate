<x-layouts.app
    mainHeading="Manage {{ $group->name }}"
    mainDescription="Update group settings and manage members."
    :mainActions="[
        ['text' => 'View group', 'route' => 'groups.show', 'params' => ['group' => $group]],
        ['text' => 'Back to dashboard', 'route' => 'dashboard'],
    ]"
>
    @php
        $tabs = [
            'settings' => 'Settings',
            'seasons' => 'Seasons',
            'players' => 'Players',
            'members' => 'Members',
        ];

        $requestedTab = request()->query('tab');
        $fallbackTab = request()->filled('member') ? 'players' : 'settings';
        $activeTab = is_string($requestedTab) && array_key_exists($requestedTab, $tabs)
            ? $requestedTab
            : $fallbackTab;

        $selectedMemberQuery = request()->query('member');
        $selectedMemberQuery = is_string($selectedMemberQuery) ? $selectedMemberQuery : null;

        $buildTabRoute = fn (string $tab): string => route('groups.edit', array_filter([
            'group' => $group,
            'tab' => $tab,
            'member' => $selectedMemberQuery,
        ], fn ($value) => $value !== null && $value !== ''));
    @endphp

    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:hidden">
            <x-form.select
                id="group-edit-tab-selector"
                name="group_edit_tab_selector"
                label="Select a management section"
                labelClass="sr-only"
                :value="$buildTabRoute($activeTab)"
                :options="collect($tabs)->mapWithKeys(fn ($label, $key) => [$buildTabRoute($key) => $label])->toArray()"
                containerClass="col-start-1 row-start-1"
                class="w-full appearance-none py-2 text-base focus:outline-indigo-600"
                aria-label="Select a management section"
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
                <nav aria-label="Edit tabs" class="-mb-px flex space-x-8">
                    @foreach ($tabs as $key => $label)
                        @php
                            $isActive = $activeTab === $key;
                        @endphp

                        <a
                            href="{{ $buildTabRoute($key) }}"
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

        @if ($activeTab === 'settings')
            {{-- Group settings --}}
            <x-groups.section-card title="Group settings" description="Update the group name.">
                <x-forms.multi-section-form
                    method="PATCH"
                    action="{{ route('groups.update', $group) }}"
                    class="space-y-8 rounded-none bg-transparent p-0 shadow-none"
                >
                    <x-slot name="sections">
                        <x-forms.form-section title="Name" description="This is what members will see on the dashboard.">
                            <input type="hidden" name="tab" value="settings">

                            <x-inputs.input-label for="name" :value="__('Group name')" />
                            <x-inputs.text-input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $group->name) }}"
                                class="mt-1 block w-full"
                                required
                            />
                            <x-inputs.input-error :messages="$errors->get('name')" />
                        </x-forms.form-section>
                    </x-slot>

                    <x-slot name="buttons">
                        <x-buttons.primary-button type="submit">Save</x-buttons.primary-button>
                    </x-slot>
                </x-forms.multi-section-form>
            </x-groups.section-card>

            {{-- Followed teams --}}
            <x-groups.section-card title="Followed teams" description="The teams this group follows for predictions.">
                @php
                    $follows = $group->follow_collection;
                    $canAddFollow = $follows->count() < $group->follow_limit;
                @endphp

                <div class="mb-3 text-xs text-gray-500">
                    {{ $follows->count() }} of {{ $group->follow_limit }} follow slots used.
                </div>

                @if ($follows->isEmpty())
                    <p class="text-sm text-gray-500">No teams followed yet. Follow a team to start making predictions.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($follows as $follow)
                            <li class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $follow->team->display_name }}</p>
                                </div>
                                <form
                                    action="{{ route('groups.follow.destroy', ['group' => $group, 'follow' => $follow]) }}"
                                    method="POST"
                                    class="ml-4 shrink-0"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-buttons.danger-button type="submit" confirm="Are you sure you want to unfollow this team?">
                                        Unfollow
                                    </x-buttons.danger-button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4 flex items-center justify-end">
                    @if ($canAddFollow)
                        <x-buttons.nav-button route="groups.follow-team.create" :params="['group' => $group]" class="ml-4 shrink-0">
                            Follow a team
                        </x-buttons.nav-button>
                    @else
                        <p class="text-sm text-gray-500">Follow limit reached. Remove a follow to add another team.</p>
                    @endif
                </div>
            </x-groups.section-card>
        @endif

        @if ($activeTab === 'seasons')
            {{-- Season follows --}}
            <x-groups.section-card
                title="Season follows"
                description="Choose the active seasons this group explicitly participates in."
            >
                <form method="POST" action="{{ route('groups.update-season-follows', $group) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="tab" value="seasons">

                    <div class="space-y-4">
                        <p class="text-xs text-gray-500">
                            Select one or more active seasons to make them available for quick predictions, scoring,
                            and season-scoped group settings.
                        </p>

                        @if ($availableSeasonsForFollow->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($availableSeasonsForFollow as $season)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            name="season_ids[]"
                                            value="{{ $season->id }}"
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-navy focus:ring-navy"
                                            {{ in_array($season->id, $selectedSeasonIds->all(), true) ? 'checked' : '' }}
                                        >
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $season->name }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $season->sport_html_entity }} {{ $season->season_type }}
                                                @if ($season->active)
                                                    <span class="ml-1">(Active)</span>
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No active seasons are available to follow.</p>
                        @endif

                        <x-inputs.input-error :messages="$errors->get('season_ids')" />
                        <x-inputs.input-error :messages="$errors->get('season_ids.*')" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-buttons.primary-button type="submit">Save season follows</x-buttons.primary-button>
                    </div>
                </form>
            </x-groups.section-card>

            {{-- Prediction policies --}}
            <x-groups.section-card title="Season configuration" description="Configure scoring and optional rules per followed season.">
                <div class="space-y-4">
                    <p class="text-xs text-gray-500">
                        The following rules are always active for every group and cannot be disabled:
                        <strong>Prediction lock time</strong> (submissions close when the game starts) and
                        <strong>Season active</strong> (submissions are blocked when the season is inactive).
                    </p>

                    @if ($group->seasonFollows->isEmpty())
                        <p class="text-sm text-gray-500">No followed seasons are configured. Add season follows to manage optional policies.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($group->seasonFollows->sortBy(fn ($follow) => $follow->season?->name ?? '') as $seasonFollow)
                                @php
                                    $seasonLabel = $seasonFollow->season?->name ?? 'Season #'.$seasonFollow->season_id;
                                    $selectedPredictionScoringPolicy = (int) old('season_id') === $seasonFollow->season_id
                                        ? old('prediction_scoring_policy', $seasonFollow->prediction_scoring_policy ?? $defaultPredictionScoringPolicyKey)
                                        : ($seasonFollow->prediction_scoring_policy ?? $defaultPredictionScoringPolicyKey);
                                    $selectedPolicyKeys = (int) old('season_id') === $seasonFollow->season_id
                                        ? old('enabled_prediction_policies', [])
                                        : ($seasonFollow->enabled_prediction_policies ?? []);
                                @endphp

                                <div class="rounded-lg border border-gray-200 px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $seasonLabel }}</p>

                                    <form method="POST" action="{{ route('groups.update-prediction-scoring-policy', $group) }}" class="mt-3 space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="tab" value="seasons">
                                        <input type="hidden" name="season_id" value="{{ $seasonFollow->season_id }}">

                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Prediction scoring policy</p>
                                        @if (! empty($availablePredictionScoringPolicies))
                                            @foreach ($availablePredictionScoringPolicies as $policy)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50">
                                                    <input
                                                        type="radio"
                                                        name="prediction_scoring_policy"
                                                        value="{{ $policy->key }}"
                                                        class="mt-0.5 h-4 w-4 border-gray-300 text-navy focus:ring-navy"
                                                        {{ $selectedPredictionScoringPolicy === $policy->key ? 'checked' : '' }}
                                                        required
                                                    >
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            {{ $policy->label }}
                                                            @if ($policy->is_default)
                                                                <span class="ml-2 text-xs font-normal text-gray-500">(Default)</span>
                                                            @endif
                                                        </p>
                                                        <p class="mt-0.5 text-xs text-gray-500">{{ $policy->description }}</p>
                                                    </div>
                                                </label>
                                            @endforeach
                                        @else
                                            <p class="text-sm text-gray-500">No prediction scoring policies are available.</p>
                                        @endif

                                        @if ((int) old('season_id') === $seasonFollow->season_id)
                                            <x-inputs.input-error class="mt-2" :messages="$errors->get('season_id')" />
                                            <x-inputs.input-error class="mt-2" :messages="$errors->get('prediction_scoring_policy')" />
                                        @endif

                                        <div class="mt-4 flex justify-end">
                                            <x-buttons.primary-button type="submit">Save scoring for season</x-buttons.primary-button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('groups.update-policies', $group) }}" class="mt-4 space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="tab" value="seasons">
                                        <input type="hidden" name="season_id" value="{{ $seasonFollow->season_id }}">

                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Optional prediction policies</p>
                                        @if ($availableGroupPolicies->isNotEmpty())
                                            @foreach ($availableGroupPolicies as $policy)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50">
                                                    <input
                                                        type="checkbox"
                                                        name="enabled_prediction_policies[]"
                                                        value="{{ $policy->key() }}"
                                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-navy focus:ring-navy"
                                                        {{ in_array($policy->key(), $selectedPolicyKeys, true) ? 'checked' : '' }}
                                                    >
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">{{ $policy->label() }}</p>
                                                        <p class="mt-0.5 text-xs text-gray-500">{{ $policy->description() }}</p>
                                                    </div>
                                                </label>
                                            @endforeach
                                        @else
                                            <p class="text-sm text-gray-500">No optional policies are available.</p>
                                        @endif

                                        @if ((int) old('season_id') === $seasonFollow->season_id)
                                            <x-inputs.input-error class="mt-2" :messages="$errors->get('season_id')" />
                                            <x-inputs.input-error class="mt-2" :messages="$errors->get('enabled_prediction_policies')" />
                                            <x-inputs.input-error class="mt-2" :messages="$errors->get('enabled_prediction_policies.*')" />
                                        @endif

                                        <div class="mt-4 flex justify-end">
                                            <x-buttons.primary-button type="submit">Save policies for season</x-buttons.primary-button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-groups.section-card>
        @endif

        @if ($activeTab === 'players')
            <x-groups.section-card title="Player management" description="Manage players for any approved group member.">
            @if ($approvedMembers->isEmpty())
                <p class="text-sm text-gray-500">No approved members available yet.</p>
            @else
                <form method="GET" action="{{ route('groups.edit', $group) }}" class="mb-4 max-w-sm">
                    <input type="hidden" name="tab" value="players">
                    <x-form.select
                        name="member"
                        label="Member"
                        :value="request('member', $selectedMember?->ulid)"
                        :options="$approvedMembers->mapWithKeys(fn ($approvedMember) => [
                            $approvedMember->ulid => $approvedMember->user->name . ' (' . $approvedMember->players_count . ' player' . ($approvedMember->players_count === 1 ? '' : 's') . ')',
                        ])->toArray()"
                        onchange="this.form.submit()"
                        class="text-sm"
                    />
                </form>

                @if ($selectedMember)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-sm text-gray-700">
                            Managing players for <span class="font-semibold">{{ $selectedMember->user->name }}</span>
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-end">
                        @if ($selectedMember->players_count < $group->player_limit)
                            <x-buttons.nav-button
                                route="groups.manage.members.players.create"
                                :params="['group' => $group, 'member' => $selectedMember]"
                            >
                                Create player
                            </x-buttons.nav-button>
                        @else
                            <p class="text-sm text-gray-500">Player limit reached for this member.</p>
                        @endif
                    </div>

                    <div class="mt-4">
                        @if ($managedPlayers && $managedPlayers->isNotEmpty())
                            <x-tables.full-width
                                :headers="['Player Name', 'Created', 'Actions']"
                                :rows="$managedPlayers"
                                :columns="[
                                    'player_name',
                                    fn ($row) => $row->created_at?->format('M d, Y'),
                                ]"
                                :rowActions="[
                                    [
                                        'label' => 'Edit',
                                        'route' => 'groups.manage.members.players.edit',
                                        'routeParams' => ['group' => $group, 'member' => $selectedMember, 'player' => 'ulid'],
                                    ],
                                    [
                                        'label' => 'Delete',
                                        'type' => 'form',
                                        'route' => 'groups.manage.members.players.destroy',
                                        'routeParams' => ['group' => $group, 'member' => $selectedMember, 'player' => 'ulid'],
                                        'confirm' => 'Are you sure you want to delete this player?',
                                    ],
                                ]"
                                emptyTitle="No players yet"
                                emptyDescription="Create a player for this member to start predictions."
                            />
                        @else
                            <x-empty-state
                                title="No players yet"
                                description="Create a player for this member to start predictions."
                                buttonText="Create player"
                                buttonRoute="groups.manage.members.players.create"
                                :buttonParams="['group' => $group, 'member' => $selectedMember]"
                            />
                        @endif
                    </div>
                @endif
            @endif
            </x-groups.section-card>
        @endif

        @if ($activeTab === 'members')
            {{-- Members --}}
            <x-groups.section-card
                title="Members"
                description="Manage members and approve join requests. Historical member records are retained so past predictions stay intact."
                overflowClass="overflow-visible"
            >
                <ul role="list" class="-mx-6 -my-5 divide-y divide-gray-100">
                    @foreach ($group->members as $member)
                        @php
                            $dropdownItems = [];
                            if ($member->isPending()) {
                                $dropdownItems[] = ['label' => 'Approve', 'href' => route('groups.approve-member', [$group, $member]), 'method' => 'POST'];
                                $dropdownItems[] = ['label' => 'Reject', 'href' => route('groups.reject-member', [$group, $member]), 'method' => 'POST', 'confirm' => 'Are you sure you want to reject this join request?'];
                            } elseif ($member->canBeRemovedBy(request()->user())) {
                                $dropdownItems[] = ['label' => 'Remove', 'href' => route('groups.remove-member', [$group, $member]), 'method' => 'DELETE', 'confirm' => 'Are you sure you want to remove this member?'];
                            }
                        @endphp
                        <li class="flex items-center gap-x-4 px-6 py-4 {{ $member->isPending() ? 'bg-yellow-50' : '' }}">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-navy text-xs font-semibold uppercase text-white">
                                {{ strtoupper(substr($member->user->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $member->user->name }}</p>
                                <p class="text-xs text-gray-500">
                                    @if ($member->isPending())
                                        Requested to join
                                    @elseif ($member->status === \App\Models\MemberStatus::REJECTED->value)
                                        Join request rejected
                                    @elseif ($member->status === \App\Models\MemberStatus::LEFT->value)
                                        Left group
                                    @elseif ($member->status === \App\Models\MemberStatus::REMOVED->value)
                                        Removed by admin
                                    @elseif ($member->isOwner())
                                        Owner
                                    @elseif ($member->isAdmin())
                                        Admin
                                    @else
                                        Member
                                    @endif
                                </p>
                            </div>
                            @if ($member->isPending())
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                    Pending
                                </span>
                            @endif
                            @if (! empty($dropdownItems))
                                <x-tables.row-actions-dropdown :items="$dropdownItems" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-groups.section-card>
        @endif

    </div>
</x-layouts.app>
