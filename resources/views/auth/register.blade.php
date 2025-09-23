<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-pink-500 to-purple-600 px-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-6 text-center">
                <h2 class="text-3xl font-extrabold text-pink-500">Create Your Vibe 🎉</h2>
                <p class="text-sm text-purple-500 mt-1">Join the space and express your mood</p>
            </div>

            {{-- Global form validation error --}}
            <div id="form-message" class="hidden text-sm text-red-500 mb-4 text-center bg-red-100 rounded p-2">
                Please fill in all fields.
            </div>

            {{-- Registration Form --}}
            <form method="POST" action="{{ route('register') }}" id="register-form" class="space-y-5">
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
                <div>
                    <x-primary-button id="register-btn" class="w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- Already have an account? --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-purple-600 hover:underline font-semibold ml-1">
                        Log in here
                    </a>
                </p>
            </div>
        </div>
    </div>

    {{-- JS for validation and feedback --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('register-form');
            const submitBtn = document.getElementById('register-btn');
            const formMessage = document.getElementById('form-message');

            const requiredFields = [
                document.getElementById('username'),
                document.getElementById('email'),
                document.getElementById('password'),
                document.getElementById('password_confirmation'),
            ];

            const validate = () => {
                const allFilled = requiredFields.every(field => field.value.trim() !== '');
                submitBtn.disabled = !allFilled;

                formMessage.classList.toggle('hidden', allFilled);
                formMessage.classList.toggle('block', !allFilled);
                console.log('Validation checked:', {
                    username: requiredFields[0].value,
                    email: requiredFields[1].value,
                    password: requiredFields[2].value,
                    password_confirmation: requiredFields[3].value,
                    allFilled
                });
            };

            requiredFields.forEach(field => field.addEventListener('input', (e) => {
                console.log(`${field.id} input:`, field.value);
                validate();
            }));

            form.addEventListener('submit', (e) => {
                console.log('Register button pressed');
                console.log('Form values:', {
                    username: requiredFields[0].value,
                    email: requiredFields[1].value,
                    password: requiredFields[2].value,
                    password_confirmation: requiredFields[3].value
                });
            });

            validate(); // initial check
        });
    </script>
</x-guest-layout>