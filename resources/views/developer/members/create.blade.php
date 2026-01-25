<x-layouts.app mainHeading="Add Member to {{ $group->name }}" mainDescription="Add a new member to the group.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => 'Add Member', 'active' => true],
        ]"
    />

    <x-form.developer.member
        :member="null"
        :group="$group"
        :users="$users ?? collect()"
        :action="route('developer.groups.members.store', $group)"
        :method="'POST'"
    />
</x-layouts.app>
