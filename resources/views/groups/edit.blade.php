<x-layouts.app
    mainHeading="Manage {{ $group->name }}"
    mainDescription="Update group settings and manage members."
    :mainActions="[
        ['text' => 'View group', 'route' => 'groups.show', 'params' => ['group' => $group]],
        ['text' => 'Back to dashboard', 'route' => 'dashboard'],
    ]"
>
    <div class="space-y-6">

        {{-- Group settings --}}
        <x-groups.section-card title="Group settings" description="Update the group name.">
            <x-forms.multi-section-form
                method="PATCH"
                action="{{ route('groups.update', $group) }}"
                class="space-y-8 rounded-none bg-transparent p-0 shadow-none"
            >
                <x-slot name="sections">
                    <x-forms.form-section title="Name" description="This is what members will see on the dashboard.">
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
                                <p class="mt-0.5 text-xs text-gray-500">Sport scope: {{ $follow->sport?->value ?? 'All sports' }}</p>
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

        {{-- Members --}}
        <x-groups.section-card
            title="Members"
            description="Manage members and approve join requests."
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

    </div>
</x-layouts.app>
