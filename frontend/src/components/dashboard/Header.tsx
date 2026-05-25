'use client'

import { useRouter } from 'next/navigation'
import { LogOut, User } from 'lucide-react'
import { useAuthStore } from '@/stores/authStore'
import api, { resetCsrf } from '@/lib/api/client'

export function Header() {
  const user = useAuthStore((s) => s.user)
  const setUser = useAuthStore((s) => s.setUser)
  const router = useRouter()

  async function handleLogout() {
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
      <span className="text-sm font-semibold text-foreground tracking-tight">
        Jurivex AI
      </span>

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
