<x-layouts.html-header></x-layouts.html-header>

<body class="h-full">

    <div class="min-h-full">
        <main>
            <div id="app" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>

</body>

<x-layouts.html-footer></x-layouts.html-footer>