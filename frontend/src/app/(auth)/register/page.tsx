'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import api from '@/lib/api/client'
import type { ValidationError } from '@/types'

interface FormState {
  organization_name: string
  name: string
  email: string
  password: string
  password_confirmation: string
}

export default function RegisterPage() {
  const router = useRouter()
  const [form, setForm] = useState<FormState>({
    organization_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [loading, setLoading] = useState(false)

  function update(field: keyof FormState, value: string) {
    setForm((prev) => ({ ...prev, [field]: value }))
    setErrors((prev) => ({ ...prev, [field]: [] }))
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setErrors({})
    setLoading(true)

    try {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/api/v1/auth/register', form)
      document.cookie = 'auth_check=1; path=/; SameSite=Lax'
      router.push('/dashboard')
    } catch (err: unknown) {
      const data = (err as { response?: { data?: ValidationError } })?.response?.data
      if (data?.errors) setErrors(data.errors)
    } finally {
      setLoading(false)
    }
  }

  const fields: { id: keyof FormState; label: string; type: string; placeholder: string }[] = [
    { id: 'organization_name', label: 'Organization name', type: 'text', placeholder: 'Acme Legal LLC' },
    { id: 'name', label: 'Your name', type: 'text', placeholder: 'Jane Smith' },
    { id: 'email', label: 'Email', type: 'email', placeholder: 'jane@acme.com' },
    { id: 'password', label: 'Password', type: 'password', placeholder: '••••••••' },
    { id: 'password_confirmation', label: 'Confirm password', type: 'password', placeholder: '••••••••' },
  ]

  return (
    <div className="rounded-xl border border-border bg-card p-8 shadow-sm">
      <h2 className="text-xl font-semibold text-foreground mb-1">Create account</h2>
      <p className="text-muted-foreground text-sm mb-6">
        Set up your organization and admin account
      </p>

      <form onSubmit={handleSubmit} className="space-y-4">
        {fields.map(({ id, label, type, placeholder }) => (
          <div key={id}>
            <label
              htmlFor={id}
              className="block text-sm font-medium text-foreground mb-1.5"
            >
              {label}
            </label>
            <input
              id={id}
              type={type}
              value={form[id]}
              onChange={(e) => update(id, e.target.value)}
              required
              placeholder={placeholder}
              className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
            {errors[id]?.map((msg) => (
              <p key={msg} className="text-destructive text-xs mt-1">
                {msg}
              </p>
            ))}
          </div>
        ))}

        <Button
          type="submit"
          disabled={loading}
          className="w-full h-10 mt-2"
        >
          {loading ? 'Creating account…' : 'Create account'}
        </Button>
      </form>

      <p className="text-center text-sm text-muted-foreground mt-6">
        Already have an account?{' '}
        <Link
          href="/login"
          className="text-foreground font-medium hover:underline"
        >
          Sign in
        </Link>
      </p>
    </div>
  )
}
