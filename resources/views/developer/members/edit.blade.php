<x-layouts.app mainHeading="Edit Member" mainDescription="Edit the member details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('developer.groups.members.show', [$group, $member])],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.developer.member
        :member="$member"
        :group="$group"
        :users="$users ?? collect()"
        :role-options="$roleOptions"
        :status-options="$statusOptions"
        :default-status="$defaultStatus"
        :action="route('developer.groups.members.update', [$group, $member])"
        :method="'PUT'"
    />
</x-layouts.app>
