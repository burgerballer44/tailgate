<x-layouts.guest>

    <div class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h1 class="text-4xl font-bold tracking-tight text-navy sm:text-6xl">Tar Heel Tailgate</h1>
                <p class="mt-6 text-lg leading-8 text-carolina">Score prediction with friends</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="{{ route('login') }}">
                        <button type="button" class="rounded-md bg-carolina px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy">Log in</button>
                    </a>
                    <a href="{{ route('register') }}">
                        <button type="button" class="rounded-md bg-carolina px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy">Register</button>
                    </a>
                </div>
            </div>
        </div>
    </div>

</x-layouts.guest>