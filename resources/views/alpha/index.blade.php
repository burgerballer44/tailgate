<x-layouts.guest>
    <div class="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-navy text-2xl font-bold">Closed alpha access</h1>
        <p class="mt-2 text-sm text-slate-600">Enter your invite code to continue.</p>

        <form method="POST" action="{{ route('alpha.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="invite_code" class="mb-1 block text-sm font-medium text-slate-700">Invite code</label>
                <input
                    id="invite_code"
                    name="invite_code"
                    type="password"
                    autocomplete="off"
                    required
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"
                />
                @error('invite_code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                Continue
            </button>
        </form>
    </div>
</x-layouts.guest>
