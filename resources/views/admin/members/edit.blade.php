<x-layouts.app mainHeading="Edit Member" mainDescription="Edit the member details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.admin.member
        :member="$member"
        :group="$group"
        :users="$users ?? collect()"
        :action="route('admin.groups.members.update', [$group, $member])"
        :method="'PUT'"
    />
</x-layouts.app>
