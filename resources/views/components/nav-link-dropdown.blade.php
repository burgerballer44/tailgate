@props([
    // text to show on the dropdown button
    'label' => 'Options',
    // should the dropdown align at the start of the button or end
    'align' => 'end',
    // how wide should the items in the menu be
    'width' => 'w-56',
    // array of items (or pass custom slot)
    'items' => [],

    'buttonClass' => 'inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring-1 inset-ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:shadow-none dark:inset-ring-white/5 dark:hover:bg-white/20',
    'menuClass' => 'origin-top-right rounded-md bg-white shadow-lg outline-1 outline-black/5 transition dark:bg-gray-800 dark:shadow-none dark:-outline-offset-1 dark:outline-white/10',
    'linkClass' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white',
])

@php
    $alignClasses = $align === 'start' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div x-data="dropdown()" class="relative inline-block text-left">
    {{-- Trigger --}}
    @isset($trigger)
        <div x-ref="button" @click="toggle" :aria-expanded="open" aria-haspopup="true">
            {{ $trigger }}
        </div>
    @else
        <button
            type="button"
            x-ref="button"
            @click="toggle"
            :aria-expanded="open"
            aria-haspopup="true"
            class="{{ $buttonClass }}"
        >
            {{ $label }}
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-mr-1 size-5 text-gray-400">
                <path
                    d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                />
            </svg>
        </button>
    @endisset

    {{-- Menu --}}
    <div
        x-ref="menu"
        x-show="open"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        @click.outside="close"
        @keydown.escape.prevent.stop="close"
        class="{{ $width }} {{ $menuClass }} {{ $alignClasses }} absolute z-50 mt-2"
        style="display: none"
        role="menu"
        aria-orientation="vertical"
    >
        <div class="py-1">
            @foreach ($items as $item)
                @php
                    $isForm = isset($item['form']);
                    $isActive = isset($item['route']) && request()->routeIs($item['route']);
                    $href = $item['route'] ? route($item['route']) : $item['href'] ?? '#';
                @endphp

                @if (! $isForm)
                    <a
                        href="{{ $href }}"
                        @if(isset($item['target'])) target="{{ $item['target'] }}" rel="noopener noreferrer" @endif
                        class="{{ $linkClass }} {{ $isActive ? 'bg-gray-100' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' }}"
                        @click="close"
                        role="menuitem"
                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                    >
                        {{ $item['label'] ?? 'Item' }}
                    </a>
                @else
                    <form
                        action="{{ $item['form']['action'] ?? '#' }}"
                        method="{{ $item['form']['method'] ?? 'POST' }}"
                        role="none"
                    >
                        @csrf
                        @if (isset($item['form']['method']) && ! in_array(strtoupper($item['form']['method']), ['GET', 'POST']))
                            @method($item['form']['method'])
                        @endif

                        <button type="submit" class="{{ $linkClass }} w-full text-left" role="menuitem" @click="close">
                            {{ $item['label'] ?? 'Submit' }}
                        </button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
</div>

<script>
    function dropdown() {
        return {
            open: false,
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            },
        };
    }
</script>
