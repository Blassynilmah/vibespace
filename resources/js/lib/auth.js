// resources/js/lib/auth.js
import axios from 'axios'

// Create a pre‑configured Axios instance for API calls
const api = axios.create({
  baseURL: '/', // same‑origin SPA
  withCredentials: true, // send cookies for Sanctum auth
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
})

// Internal state
let currentUser = null
let hydrated = false
let booting = false
const listeners = new Set()

const notify = () => {
  listeners.forEach(fn => fn({ user: currentUser, hydrated }))
}

// Intercept 401s globally and reset auth
api.interceptors.response.use(
  r => r,
  err => {
    if (err?.response?.status === 401) {
      currentUser = null
      notify()
    }
    return Promise.reject(err)
  }
)

// Get CSRF cookie (needed before login or any state‑changing request)
async function getCsrf() {
  await api.get('/sanctum/csrf-cookie')
}

// Boot/hydrate auth state on app start
async function boot() {
  if (booting) return
  booting = true
  await getCsrf()

  try {
    const { data } = await api.get('/api/user')
    currentUser = data
  } catch {
    currentUser = null
  } finally {
    hydrated = true
    booting = false
    notify()
  }
}

// Login with credentials
async function login(credentials) {
  try {
    await getCsrf()
    await api.post('/login', credentials)
    const { data } = await api.get('/api/user')
    currentUser = data
    notify()
    return currentUser
  } catch (err) {
    // Pass error up so UI can handle invalid creds, etc.
    throw err
  }
}

// Logout user
async function logout() {
  await api.post('/logout')
  currentUser = null
  notify()
}

// Force‑clear auth state without API call
function reset() {
  currentUser = null
  hydrated = false
  notify()
}

// Subscribe to auth changes
function onChange(fn) {
  listeners.add(fn)
  return () => listeners.delete(fn)
}

// Public API
export const auth = {
  api,
  boot,
  login,
  logout,
  reset,
  onChange,
  get user() { return currentUser },
  get ready() { return hydrated },
}