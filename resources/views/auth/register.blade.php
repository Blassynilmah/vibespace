<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-6 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Create Your Vibe 🎉</h2>
                <p class="text-sm text-gray-500 mt-1">Join the space and express your mood</p>
            </div>

            {{-- Global form validation error --}}
            <div id="form-message" class="hidden text-sm text-red-500 mb-4 text-center">
                Please fill in all fields.
            </div>

            {{-- Registration Form --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-5" id="register-form">
                @csrf

                {{-- Username --}}
                <div>
                    <x-input-label for="username" :value="__('Username')" />
                    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                {{-- Email --}}
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Confirm Password --}}
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                {{-- Register Button --}}
                <div class="pt-2">
                    <x-primary-button class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- Already have an account? --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-medium">
                        Log in here
                    </a>
                </p>
            </div>
        </div>
    </div>

    {{-- JS for validation and feedback --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('#register-form');
            const submitBtn = form.querySelector('button[type="submit"]');
            const formMessage = document.getElementById('form-message');

            const requiredFields = [
                form.querySelector('#username'),
                form.querySelector('#email'),
                form.querySelector('#password'),
                form.querySelector('#password_confirmation'),
            ];

            const validate = () => {
                const allFilled = requiredFields.every(field => field.value.trim() !== '');
                submitBtn.disabled = !allFilled;

                formMessage.classList.toggle('hidden', allFilled);
                formMessage.classList.toggle('block', !allFilled);
            };

            requiredFields.forEach(field => field.addEventListener('input', validate));
            validate(); // initial check
        });
    </script>
</x-guest-layout>
