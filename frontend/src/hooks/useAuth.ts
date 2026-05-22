'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/authStore'
import api, { resetCsrf } from '@/lib/api/client'
import type { ApiResponse, User } from '@/types'

export function useAuth() {
  const { user, setUser } = useAuthStore()
  const router = useRouter()

  const { data, isPending, error } = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () =>
      api.get<ApiResponse<User>>('/api/v1/auth/me').then((r) => r.data.data),
    retry: false,
    staleTime: 60_000,
  })

  useEffect(() => {
    if (data) setUser(data)
  }, [data, setUser])

  useEffect(() => {
    if (error) {
      resetCsrf()
      document.cookie = 'auth_check=; Max-Age=0; path=/'
      router.push('/login')
    }
  }, [error, router])

  return { user, isPending }
}
