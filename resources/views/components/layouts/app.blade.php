<x-layouts.html-header></x-layouts.html-header>

<body class="h-full">

    <div class="min-h-full">
      
        <x-navigation></x-navigation>

        <header class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <h2>{{ $heading }}</h2>
            </div>
        </header>

        <main>
            <div id="app" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>

</body>

<x-layouts.html-footer></x-layouts.html-footer>