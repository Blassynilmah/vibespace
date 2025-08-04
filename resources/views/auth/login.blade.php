<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-6 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Welcome Back 👋</h2>
                <p class="text-sm text-gray-500 mt-1">Log in to your VibeSpace account</p>
            </div>

            {{-- Session status --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
                @csrf

                {{-- Email --}}
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-indigo-600 hover:underline" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                {{-- Login Button --}}
                <div>
                    <x-primary-button class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- No account? Register link --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don’t have an account?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:underline font-medium">
                        Create one
                    </a>
                </p>
            </div>
        </div>
    </div>

    {{-- JS to handle live validation --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('login-form');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const submit = form.querySelector('button[type="submit"]');

            const validate = () => {
                const filled = email.value.trim() !== '' && password.value.trim() !== '';
                submit.disabled = !filled;
                submit.classList.toggle('opacity-50', !filled);
                submit.classList.toggle('cursor-not-allowed', !filled);
            };

            email.addEventListener('input', validate);
            password.addEventListener('input', validate);
            validate(); // on load
        });
    </script>
</x-guest-layout>
