'use client'

import { useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { LogOut, User, Search } from 'lucide-react'
import { useAuthStore } from '@/stores/authStore'
import api, { resetCsrf } from '@/lib/api/client'

export function Header() {
  const user = useAuthStore((s) => s.user)
  const setUser = useAuthStore((s) => s.setUser)
  const router = useRouter()
  const [query, setQuery] = useState('')
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  function handleSearchChange(value: string): void {
    setQuery(value)
    if (debounceRef.current) clearTimeout(debounceRef.current)
    if (value.trim().length >= 2) {
      debounceRef.current = setTimeout(() => {
        router.push(`/search?q=${encodeURIComponent(value.trim())}`)
      }, 500)
    }
  }

  function handleSearchSubmit(e: React.FormEvent): void {
    e.preventDefault()
    if (query.trim().length >= 2) {
      if (debounceRef.current) clearTimeout(debounceRef.current)
      router.push(`/search?q=${encodeURIComponent(query.trim())}`)
    }
  }

  async function handleLogout(): Promise<void> {
    try {
      await api.delete('/api/v1/auth/logout')
    } finally {
      resetCsrf()
      setUser(null)
      document.cookie = 'auth_check=; Max-Age=0; path=/'
      router.push('/login')
    }
  }

  return (
    <header className="flex h-14 shrink-0 items-center justify-between border-b border-border bg-card px-6">
      {/* Search bar */}
      <form onSubmit={handleSearchSubmit} className="flex items-center w-64">
        <div className="relative w-full">
          <Search
            size={13}
            className="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none"
          />
          <input
            type="search"
            value={query}
            onChange={(e) => handleSearchChange(e.target.value)}
            placeholder="Search documents…"
            className="w-full rounded-md border border-input bg-background pl-8 pr-3 py-1.5 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
          />
        </div>
      </form>

      {/* User + logout */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <User size={15} />
          <span>{user?.name ?? '…'}</span>
        </div>

        <button
          onClick={handleLogout}
          title="Sign out"
          className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
        >
          <LogOut size={15} />
        </button>
      </div>
    </header>
  )
}
