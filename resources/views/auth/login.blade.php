<x-guest-layout>
    <div id="login-bg">
        <div id="login-container">
            <div id="login-header">
                <h2 id="login-title">Welcome Back 👋</h2>
                <p id="login-subtitle">Log in to your VibeSpace account</p>
            </div>

            {{-- Session status --}}
            <x-auth-session-status id="session-status" :status="session('status')" />

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" id="login-form">
                @csrf

                {{-- Email --}}
                <div id="email-group">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                {{-- Password --}}
                <div id="password-group">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                {{-- Remember Me --}}
                <div id="remember-group">
                    <label for="remember_me" id="remember-label">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span id="remember-text">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a id="forgot-link" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                {{-- Login Button --}}
                <div id="login-btn-group">
                    <x-primary-button id="login-btn">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- No account? Register link --}}
            <div id="register-link-group">
                <p id="register-text">
                    Don’t have an account?
                    <a id="register-link" href="{{ route('register') }}">
                        Create one
                    </a>
                </p>
            </div>
        </div>
    </div>

    {{-- JS to handle live validation --}}
    <script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form')
  if (!form) return
  const email = document.getElementById('email')
  const password = document.getElementById('password')
  const submit = form.querySelector('button[type="submit"], #login-btn')

  if (!email || !password || !submit) return

  const validate = () => {
    const filled = email.value.trim() && password.value.trim()
    submit.disabled = !filled
    submit.classList.toggle('opacity-50', !filled)
    submit.classList.toggle('cursor-not-allowed', !filled)
  }

  email.addEventListener('input', validate)
  password.addEventListener('input', validate)
  validate()
})
    </script>
<style>
    /* Background matches homepage */
    #login-bg {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        padding-left: 1rem;
        padding-right: 1rem;
    }
    #login-container {
        width: 100%;
        max-width: 400px;
        background: #fff;
        border-radius: 1.5rem;
        box-shadow: 0 8px 32px rgba(236,72,153,0.12), 0 2px 8px rgba(139,92,246,0.08);
        padding: 2.5rem 2rem;
    }
    #login-header {
        margin-bottom: 1.5rem;
        text-align: center;
    }
    #login-title {
        font-size: 2rem;
        font-weight: 800;
        color: #ec4899;
        letter-spacing: -1px;
    }
    #login-subtitle {
        font-size: 1rem;
        color: #8b5cf6;
        margin-top: 0.25rem;
        font-weight: 500;
    }
    #session-status {
        margin-bottom: 1rem;
    }
    #login-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    #email-group, #password-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    #remember-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    #remember-label {
        display: inline-flex;
        align-items: center;
    }
    #remember-text {
        margin-left: 0.5rem;
        font-size: 0.95rem;
        color: #4b5563;
    }
    #forgot-link {
        font-size: 0.95rem;
        color: #8b5cf6;
        text-decoration: underline;
        font-weight: 600;
        transition: color 0.2s;
    }
    #forgot-link:hover {
        color: #ec4899;
    }
    #login-btn-group {
        margin-top: 0.5rem;
    }
    #login-btn {
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
    #login-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    #register-link-group {
        margin-top: 1.5rem;
        text-align: center;
    }
    #register-text {
        font-size: 0.95rem;
        color: #6b7280;
    }
    #register-link {
        color: #8b5cf6;
        text-decoration: underline;
        font-weight: 600;
        margin-left: 0.25rem;
        transition: color 0.2s;
    }
    #register-link:hover {
        color: #ec4899;
    }
    /* Disabled button styles */
    .opacity-50 {
        opacity: 0.5 !important;
    }
    .cursor-not-allowed {
        cursor: not-allowed !important;
    }
</style>
</x-guest-layout>