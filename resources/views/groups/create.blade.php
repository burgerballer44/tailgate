<x-layouts.app
    mainHeading="Create Group"
    mainDescription="Create a new group to start predicting sports outcomes with friends."
    :mainActions="[
        ['text' => 'Back to Dashboard', 'route' => 'dashboard'],
    ]"
>
    <x-forms.multi-section-form method="POST" action="{{ route('groups.store') }}">
        <x-slot name="sections">
            <x-forms.form-section title="Group Details" description="Enter the basic information for your group.">
                <x-inputs.input-label for="name" :value="__('Group Name')" />
                <x-inputs.text-input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    class="mt-1 block w-full"
                    required
                />
                <x-inputs.input-error :messages="$errors->get('name')" />
            </x-forms.form-section>
        </x-slot>

        <x-slot name="buttons">
            <x-buttons.primary-button type="submit">Create Group</x-buttons.primary-button>
        </x-slot>
    </x-forms.multi-section-form>
</x-layouts.app>
