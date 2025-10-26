@if (session()->has('alert'))
    @php
        // retrieve alert data from session
        $alert = session('alert');
        $type = $alert['type'] ?? '';
        $message = $alert['message'] ?? '';
        $text = $alert['text'] ?? null;
        $links = $alert['links'] ?? [];

        // default values
        // covers success type
        $iconColor = 'text-green-400';
        $borderColor = 'border-green-400';
        $bgColor = 'bg-green-100';
        $textColor = 'text-green-700';
        $buttonBg = 'bg-green-100';
        $buttonText = 'text-green-500';
        $buttonHover = 'hover:bg-green-200';
        $focusRing = 'focus:ring-green-600';
        $focusOffset = 'focus:ring-offset-green-50';
        $linkBg = 'bg-green-50';
        $linkText = 'text-green-800';
        $linkHover = 'hover:bg-green-100';
        $iconPath = 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z';

        if ($type === 'error') {
            $iconColor = 'text-red-400';
            $borderColor = 'border-red-400';
            $bgColor = 'bg-red-100';
            $textColor = 'text-red-700';
            $buttonBg = 'bg-red-100';
            $buttonText = 'text-red-500';
            $buttonHover = 'hover:bg-red-200';
            $focusRing = 'focus:ring-red-600';
            $focusOffset = 'focus:ring-offset-red-50';
            $linkBg = 'bg-red-50';
            $linkText = 'text-red-800';
            $linkHover = 'hover:bg-red-100';
            $iconPath = 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z';
        } elseif ($type === 'warning') {
            $iconColor = 'text-yellow-400';
            $borderColor = 'border-yellow-400';
            $bgColor = 'bg-yellow-100';
            $textColor = 'text-yellow-700';
            $buttonBg = 'bg-yellow-100';
            $buttonText = 'text-yellow-500';
            $buttonHover = 'hover:bg-yellow-200';
            $focusRing = 'focus:ring-yellow-600';
            $focusOffset = 'focus:ring-offset-yellow-50';
            $linkBg = 'bg-yellow-50';
            $linkText = 'text-yellow-800';
            $linkHover = 'hover:bg-yellow-100';
            $iconPath = 'M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z';
        } elseif ($type === 'info') {
            $iconColor = 'text-blue-400';
            $borderColor = 'border-blue-400';
            $bgColor = 'bg-blue-50';
            $textColor = 'text-blue-700';
            $buttonBg = 'bg-blue-50';
            $buttonText = 'text-blue-500';
            $buttonHover = 'hover:bg-blue-100';
            $focusRing = 'focus:ring-blue-600';
            $focusOffset = 'focus:ring-offset-blue-50';
            $linkBg = 'bg-blue-50';
            $linkText = 'text-blue-800';
            $linkHover = 'hover:bg-blue-100';
            $iconPath = 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z';
        }
    @endphp

    <div class="{{ $borderColor }} {{ $bgColor }} mb-4 rounded-md border-l-4 p-4">
        <div class="flex">
            {{-- icon --}}
            <div class="flex-shrink-0">
                <svg class="{{ $iconColor }} h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="{{ $iconPath }}" clip-rule="evenodd" />
                </svg>
            </div>

            {{-- message string or array --}}
            <div class="ml-3">
                <div class="{{ $textColor }} text-sm">
                    @if (is_iterable($message))
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            @foreach ($message as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ $message }}
                    @endif
                </div>
            </div>

            {{-- dismiss button --}}
            <div class="ml-auto pl-3" onclick="this.parentElement.parentElement.style.display='none';">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        class="{{ $buttonBg }} {{ $buttonText }} {{ $buttonHover }} {{ $focusRing }} {{ $focusOffset }} inline-flex rounded-md p-1.5 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                    >
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path
                                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- optional extra text --}}
        @if ($text)
            <div class="{{ $textColor }} mt-4 text-sm">
                <p>{{ $text }}</p>
            </div>
        @endif

        {{-- optional links --}}
        @if ($links)
            <div class="mt-4">
                <div class="-mx-2 -my-1.5 flex">
                    @foreach ($links as $link)
                        <a
                            href="{{ $link['link'] ?? '' }}"
                            class="{{ $linkBg }} {{ $linkText }} {{ $linkHover }} {{ $focusRing }} {{ $focusOffset }} mx-2 rounded-md px-2 py-1.5 text-sm font-medium focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        >
                            {{ $link['text'] ?? '' }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
