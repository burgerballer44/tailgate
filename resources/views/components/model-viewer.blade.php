@props([
    'message' => '',
    'details' => '',
    'card' => true,
    'fields' => [],
])

<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
    {{-- optional card header --}}
    @if ($message || $details)
        <div class="px-4 py-6 sm:px-6">
            <h3 class="text-base/7 font-semibold">{{ $message }}</h3>
            <p class="mt-1 max-w-2xl text-sm/6">{{ $details }}</p>
        </div>
    @endif

    {{-- body --}}
    <div class="border-t border-gray-100">
        <dl class="divide-y divide-gray-100">
            @foreach ($fields as $field)
                @php
                    $label = $field['label'] ?? '';
                    $value = $field['value'] ?? '';
                @endphp

                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium">{{ $label }}</dt>
                    <dd class="mt-1 text-sm/6 sm:mt-0">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
