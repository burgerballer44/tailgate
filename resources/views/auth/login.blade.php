<x-layouts.guest mainHeading="Sign in to your account">
    <div class="mb-4 flex justify-center">
        <img src="{{ asset('images/tar-heel-tailgate-logo.svg') }}" alt="Tar Heel Tailgate logo" class="h-24 w-24" />
    </div>

    <x-layouts.partials.flash-alert></x-layouts.partials.flash-alert>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div>
            <x-inputs.input-label for="email" :value="__('Email')" />
            <x-inputs.text-input
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-inputs.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-inputs.input-label for="password" :value="__('Password')" />

            <x-inputs.text-input
                id="password"
                class="mt-1 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />

            <x-inputs.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="text-navy-600 focus:ring-navy-500 rounded border-gray-300 shadow-sm"
                    name="remember"
                />
                <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-4 flex items-center justify-end">
            @if (Route::has('password.request'))
                <a
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                    href="{{ route('password.request') }}"
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-buttons.primary-button class="ml-3">
                {{ __('Sign in') }}
            </x-buttons.primary-button>
        </div>
    </form>

    <p class="mt-6 mb-2 text-center text-sm text-gray-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-carolina hover:text-navy font-medium">Register here</a>
    </p>

    @include('auth.partials.google-auth', ['buttonText' => 'Sign in with Google'])
</x-layouts.guest>
