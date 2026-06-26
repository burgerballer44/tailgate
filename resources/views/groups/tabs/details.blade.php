<x-groups.section-card title="Group snapshot" description="Quick status for this group.">
    <div class="-mx-6 -my-5 grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-4 sm:divide-y-0">
        <div class="px-6 py-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Following</p>

            @if ($follows->isEmpty())
                <p class="mt-1 text-sm text-gray-500">No teams followed yet.</p>
            @else
                <ul class="mt-2 space-y-2">
                    @foreach ($follows as $follow)
                        <li class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <p class="font-semibold text-gray-900">{{ $follow->team->display_name }}</p>
                            <p class="text-gray-500">{{ $follow->sport_display }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
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

    @php
        $enabledPolicies = $group->enabled_prediction_policies ?? [];
        $policyLabels = collect($availableGroupPolicies)->keyBy(fn ($p) => $p->key());
    @endphp

    @if (! empty($enabledPolicies))
        <div class="mt-4 border-t border-gray-100 px-6 py-4">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Active prediction rules</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($enabledPolicies as $policyKey)
                    <span class="inline-flex items-center rounded-full bg-navy/10 px-2.5 py-0.5 text-xs font-medium text-navy">
                        {{ $policyLabels->get($policyKey)?->label() ?? $policyKey }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</x-groups.section-card>
