import './bootstrap';

import Alpine from 'alpinejs';

// resources/js/app.js
import '../css/app.css'
import { auth } from './lib/auth'

// Kick off initial auth hydration
auth.boot()

// Optional: expose globally for legacy code that “expects” auth everywhere
window.auth = auth

// Wire into Alpine once it initializes (you’re loading Alpine via CDN)
window.addEventListener('alpine:init', () => {
  const store = Alpine.reactive({
    ready: auth.ready,
    user: auth.user,
    async login(payload) { return auth.login(payload) },
    async logout() { return auth.logout() },
  })

  // Keep Alpine store in sync with auth
  auth.onChange(({ user, hydrated }) => {
    store.user = user
    store.ready = hydrated
  })

  Alpine.store('auth', store)
})

window.Alpine = Alpine;

Alpine.start();
