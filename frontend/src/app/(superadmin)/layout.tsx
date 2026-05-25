'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useAuth } from '@/hooks/useAuth'

export default function SuperadminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const router = useRouter()
  const { user, isPending } = useAuth()

  useEffect(() => {
    if (!isPending && user && !user.roles.includes('superadmin')) {
      router.replace('/dashboard')
    }
  }, [isPending, user, router])

  if (isPending || !user?.roles.includes('superadmin')) return null

  return <>{children}</>
}
