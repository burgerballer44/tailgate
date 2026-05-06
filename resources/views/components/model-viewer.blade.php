@props([
    'message' => '',
    'details' => '',
    'fields' => [],
    'tone' => 'neutral',
])

@php
    $toneClasses = [
        'neutral' => 'ring-slate-200',
        'info' => 'ring-sky-200',
        'success' => 'ring-emerald-200',
        'warning' => 'ring-amber-200',
        'danger' => 'ring-rose-200',
    ];

    $headerAccentClasses = [
        'neutral' => 'bg-slate-50 text-slate-700 ring-slate-200',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];

    $cardTone = $toneClasses[$tone] ?? $toneClasses['neutral'];
    $headerTone = $headerAccentClasses[$tone] ?? $headerAccentClasses['neutral'];
@endphp

<section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-inset {{ $cardTone }}">
    @if ($message || $details)
        <header class="border-b border-slate-100 px-4 py-4 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset {{ $headerTone }}">
                    i
                </span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">{{ $message }}</h3>
                    @if ($details)
                        <p class="mt-1 text-sm text-slate-600">{{ $details }}</p>
                    @endif
                </div>
            </div>
        </header>
    @endif

    <div class="px-4 py-4 sm:px-6">
        <dl class="divide-y divide-slate-100">
            @foreach ($fields as $field)
                @php
                    $label = $field['label'] ?? '';
                    $value = $field['value'] ?? null;
                    $displayValue = $value;

                    if (is_bool($value)) {
                        $displayValue = $value ? 'Yes' : 'No';
                    }

                    if (is_array($value)) {
                        $displayValue = collect($value)->filter()->join(', ');
                    }

                    if ($displayValue === null || $displayValue === '') {
                        $displayValue = 'N/A';
                    }
                @endphp

                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                    <dt class="text-sm font-medium text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 break-words text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $displayValue }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
