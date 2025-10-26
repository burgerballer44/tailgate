@props([
    'mainHeading' => '',
])

<x-layouts.html-header></x-layouts.html-header>

<body class="h-full bg-gray-100">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-2xl/9 text-xl font-bold font-semibold tracking-tight text-gray-900">
                {{ $mainHeading }}
            </h2>
        </div>

        <div class="flex flex-col items-center pt-6 sm:justify-center sm:pt-0">
            <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

<x-layouts.html-footer></x-layouts.html-footer>
