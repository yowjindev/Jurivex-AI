'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useAuthStore } from '@/stores/authStore'

export default function SuperadminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const router = useRouter()
  const user   = useAuthStore((s) => s.user)

  useEffect(() => {
    if (user !== null && !user.roles.includes('superadmin')) {
      router.replace('/dashboard')
    }
  }, [user, router])

  if (!user?.roles.includes('superadmin')) {
    return null
  }

  return <>{children}</>
}
