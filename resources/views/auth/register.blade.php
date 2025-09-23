<x-guest-layout>
    <div id="register-bg">
        <div id="register-container">
            <div id="register-header">
                <h2 id="register-title">Create Your Vibe 🎉</h2>
                <p id="register-subtitle">Join the space and express your mood</p>
            </div>

            {{-- Global form validation error --}}
            <div id="form-message" class="hidden">
                Please fill in all fields.
            </div>

            {{-- Registration Form --}}
            <form method="POST" action="{{ route('register') }}" id="register-form">
                @csrf

                {{-- Username --}}
                <div id="username-group">
                    <x-input-label for="username" :value="__('Username')" />
                    <x-text-input id="username" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" />
                </div>

                {{-- Email --}}
                <div id="email-group">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                {{-- Password --}}
                <div id="password-group">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                {{-- Confirm Password --}}
                <div id="password-confirm-group">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" />
                </div>

                {{-- Register Button --}}
                <div id="register-btn-group">
                    <x-primary-button id="register-btn">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- Already have an account? --}}
            <div id="login-link-group">
                <p id="login-text">
                    Already have an account?
                    <a id="login-link" href="{{ route('login') }}">
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
            };

            requiredFields.forEach(field => field.addEventListener('input', validate));
            validate(); // initial check
        });
    </script>
<style>
    /* Background matches homepage */
    #register-bg {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        padding-left: 1rem;
        padding-right: 1rem;
    }
    #register-container {
        width: 100%;
        max-width: 400px;
        background: #fff;
        border-radius: 1.5rem;
        box-shadow: 0 8px 32px rgba(236,72,153,0.12), 0 2px 8px rgba(139,92,246,0.08);
        padding: 2.5rem 2rem;
    }
    #register-header {
        margin-bottom: 1.5rem;
        text-align: center;
    }
    #register-title {
        font-size: 2rem;
        font-weight: 800;
        color: #ec4899;
        letter-spacing: -1px;
    }
    #register-subtitle {
        font-size: 1rem;
        color: #8b5cf6;
        margin-top: 0.25rem;
        font-weight: 500;
    }
    #form-message {
        font-size: 0.95rem;
        color: #ef4444;
        margin-bottom: 1rem;
        text-align: center;
        background: #fee2e2;
        border-radius: 0.5rem;
        padding: 0.5rem 0;
    }
    #register-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    #username-group, #email-group, #password-group, #password-confirm-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    #register-btn-group {
        margin-top: 0.5rem;
    }
    #register-btn {
        width: 100%;
        display: flex;
        justify-content: center;
        background: linear-gradient(90deg, #ec4899 0%, #8b5cf6 100%);
        color: #fff;
        font-weight: 700;
        border-radius: 0.75rem;
        padding: 0.75rem 0;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(236,72,153,0.08);
        transition: background 0.2s;
    }
    #register-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    #login-link-group {
        margin-top: 1.5rem;
        text-align: center;
    }
    #login-text {
        font-size: 0.95rem;
        color: #6b7280;
    }
    #login-link {
        color: #8b5cf6;
        text-decoration: underline;
        font-weight: 600;
        margin-left: 0.25rem;
        transition: color 0.2s;
    }
    #login-link:hover {
        color: #ec4899;
    }
    /* Disabled button styles */
    .opacity-50 {
        opacity: 0.5 !important;
    }
    .cursor-not-allowed {
        cursor: not-allowed !important;
    }
    .block {
        display: block !important;
    }
    .hidden {
        display: none !important;
    }
</style>
</x-guest-layout>