'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/hooks/useAuth'
import { LoadingSpinner } from '@/components/shared/LoadingSpinner'
import { Sidebar } from './Sidebar'
import { Header } from './Header'

export function DashboardShell({ children }: { children: React.ReactNode }) {
  const { isPending } = useAuth()
  const queryClient = useQueryClient()
  const router = useRouter()

  useEffect(() => {
    const refreshQueries = (): void => {
      // Invalidate all cached query data AND bust Next.js router cache
      queryClient.invalidateQueries()
      router.refresh()
    }

    const handlePageShow = (event: PageTransitionEvent): void => {
      if (event.persisted) {
        refreshQueries()
      }
    }

    window.addEventListener('popstate', refreshQueries)
    window.addEventListener('pageshow', handlePageShow)

    return () => {
      window.removeEventListener('popstate', refreshQueries)
      window.removeEventListener('pageshow', handlePageShow)
    }
  }, [queryClient, router])

  if (isPending) {
    return (
      <div className="flex h-screen items-center justify-center bg-background">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <Sidebar />
      <div className="flex flex-1 flex-col overflow-hidden">
        <Header />
        <main className="flex-1 overflow-y-auto p-6">{children}</main>
      </div>
    </div>
  )
}
