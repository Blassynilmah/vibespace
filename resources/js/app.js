import './bootstrap'
import Alpine from 'alpinejs'

// resources/js/app.js
import '../css/app.css'
import { auth } from './lib/auth'
import dayjs from 'dayjs'

// Kick off initial auth hydration
auth.boot()

// Optional: expose globally for legacy code that “expects” auth everywhere
window.auth = auth

window.addEventListener('alpine:init', () => {
  // --- Auth store ---
  const store = Alpine.reactive({
    ready: auth.ready,
    user: auth.user,
    async login(payload) { return auth.login(payload) },
    async logout() { return auth.logout() },
  })

  auth.onChange(({ user, hydrated }) => {
    store.user = user
    store.ready = hydrated
  })

  Alpine.store('auth', store)

  // --- Notifications component ---
  Alpine.data('notificationInbox', () => ({
    notifications: [],
    page: 1,
    hasMore: true,
    isLoading: false,

    async init() {
      await this.fetchNotifications(1)
    },

    async fetchNotifications(page = 1) {
      this.isLoading = true
      try {
        let res = await fetch(`/api/notifications?page=${page}`)
        let data = await res.json()
        let items = Array.isArray(data.data) ? data.data : []

        if (page === 1) {
          this.notifications = items
        } else {
          this.notifications.push(...items)
        }

        this.page = data.current_page || page
        this.hasMore = !!data.next_page_url
      } catch (err) {
        console.error('fetchNotifications error:', err)
      } finally {
        this.isLoading = false
      }
    },

    async markAllAsRead() {
      await fetch('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
      })

      this.notifications = this.notifications.map(group => {
        group.notifications = group.notifications.map(n => ({ ...n, is_read: 1 }))
        return group
      })
    },

    formatTime(ts) {
      return dayjs(ts).fromNow()
    }
  }))
})

window.Alpine = Alpine
Alpine.start()
