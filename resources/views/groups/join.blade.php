<x-layouts.app
    mainHeading="Join Group"
    mainDescription="Enter an invite code to join an existing group."
    :mainActions="[
        ['text' => 'Back to Dashboard', 'route' => 'dashboard'],
    ]"
>
    <x-forms.multi-section-form method="POST" action="{{ route('groups.request-join') }}">
        <x-slot name="sections">
            <x-forms.form-section
                title="Group Invitation"
                description="Enter the invite code provided by the group owner."
            >
                <x-inputs.input-label for="invite_code" :value="__('Invite Code')" />
                <x-inputs.text-input
                    type="text"
                    name="invite_code"
                    id="invite_code"
                    value="{{ old('invite_code') }}"
                    class="mt-1 block w-full"
                    placeholder="Enter invite code"
                    required
                />
                <x-inputs.input-error :messages="$errors->get('invite_code')" />
            </x-forms.form-section>
        </x-slot>

        <x-slot name="buttons">
            <x-buttons.primary-button type="submit">Request to Join</x-buttons.primary-button>
        </x-slot>
    </x-forms.multi-section-form>
</x-layouts.app>
