@php
    $confirmMessage = $attributes->get('confirm', 'Are you sure you want to perform this action?');
    $attributes = $attributes->except('confirm');
@endphp

<button
    {{
        $attributes->merge([
            'type' => 'submit',
            'class' => 'cursor-pointer inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150',
            'onclick' => $confirmMessage ? 'return confirm(' . json_encode($confirmMessage) . ')' : null,
        ])
    }}
>
    {{ $slot }}
</button>
