<x-layouts.guest>
    <div class="mx-auto max-w-2xl text-center">
        <h1 class="text-navy text-4xl font-bold sm:text-6xl">{{ config('app.name') }}</h1>
        <p class="text-carolina mt-6 text-lg leading-8">Coming Soon</p>

        <p class="mt-8 text-sm text-slate-600">Do you have an invite code?</p>
        <a
            href="{{ route('alpha.create') }}"
            class="mt-3 inline-flex rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
            Join here
        </a>
    </div>
</x-layouts.guest>
