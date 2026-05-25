'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import api from '@/lib/api/client'
import { lookupInvitation } from '@/lib/api/superadmin'
import type { InvitationLookup, ValidationError } from '@/types'

type Step = 'code' | 'details'

interface DetailsForm {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export default function RegisterPage() {
  const router = useRouter()

  const [step, setStep]                 = useState<Step>('code')
  const [code, setCode]                 = useState('')
  const [codeError, setCodeError]       = useState('')
  const [codeLoading, setCodeLoading]   = useState(false)
  const [preview, setPreview]           = useState<InvitationLookup | null>(null)

  const [form, setForm]                 = useState<DetailsForm>({
    name: '', email: '', password: '', password_confirmation: '',
  })
  const [errors, setErrors]             = useState<Record<string, string[]>>({})
  const [submitLoading, setSubmitLoading] = useState(false)
  const [submitError, setSubmitError]   = useState('')

  function updateForm(field: keyof DetailsForm, value: string) {
    setForm((prev) => ({ ...prev, [field]: value }))
    setErrors((prev) => ({ ...prev, [field]: [] }))
  }

  async function handleVerifyCode(e: React.FormEvent) {
    e.preventDefault()
    setCodeError('')
    setCodeLoading(true)
    try {
      const data = await lookupInvitation(code.trim().toUpperCase())
      setPreview(data)
      setStep('details')
    } catch {
      setCodeError('Invalid or expired invitation code.')
    } finally {
      setCodeLoading(false)
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setErrors({})
    setSubmitError('')
    setSubmitLoading(true)
    try {
      await api.post('/api/v1/auth/register', {
        invitation_code: code.trim().toUpperCase(),
        ...form,
      })
      document.cookie = 'auth_check=1; path=/; SameSite=Lax'
      router.push('/dashboard')
    } catch (err: unknown) {
      const data = (err as { response?: { data?: ValidationError } })?.response?.data
      if (data?.errors) {
        setErrors(data.errors)
      } else {
        setSubmitError('Something went wrong. Please try again.')
      }
    } finally {
      setSubmitLoading(false)
    }
  }

  return (
    <div className="rounded-xl border border-border bg-card p-8 shadow-sm">
      <h2 className="text-xl font-semibold text-foreground mb-1">Create account</h2>
      <p className="text-muted-foreground text-sm mb-6">
        {step === 'code'
          ? 'Enter your invitation code to get started'
          : `Joining ${preview?.organization_name} as ${preview?.role}`}
      </p>

      {step === 'code' && (
        <form onSubmit={handleVerifyCode} className="space-y-4">
          <div>
            <label htmlFor="invitation_code" className="block text-sm font-medium text-foreground mb-1.5">
              Invitation code
            </label>
            <input
              id="invitation_code"
              type="text"
              value={code}
              onChange={(e) => { setCode(e.target.value); setCodeError('') }}
              required
              placeholder="XXXXXXXX"
              maxLength={16}
              autoComplete="off"
              className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring uppercase tracking-widest"
            />
            {codeError && <p className="text-destructive text-xs mt-1">{codeError}</p>}
          </div>
          <Button type="submit" disabled={codeLoading} className="w-full h-10 mt-2">
            {codeLoading ? 'Verifying…' : 'Verify code'}
          </Button>
        </form>
      )}

      {step === 'details' && (
        <form onSubmit={handleSubmit} className="space-y-4">
          {preview && (
            <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
              <span className="text-muted-foreground">Joining </span>
              <span className="font-medium text-foreground">{preview.organization_name}</span>
              <span className="text-muted-foreground"> as </span>
              <span className="font-medium text-foreground capitalize">{preview.role}</span>
              <button
                type="button"
                onClick={() => { setStep('code'); setPreview(null); setCodeError(''); setForm({ name: '', email: '', password: '', password_confirmation: '' }); setErrors({}) }}
                className="ml-3 text-xs text-muted-foreground underline hover:text-foreground"
              >
                Change
              </button>
            </div>
          )}

          {(
            [
              { id: 'name',                  label: 'Your name',        type: 'text',     placeholder: 'Jane Smith',   autoComplete: 'name' },
              { id: 'email',                 label: 'Email',            type: 'email',    placeholder: 'jane@firm.com', autoComplete: 'email' },
              { id: 'password',              label: 'Password',         type: 'password', placeholder: '••••••••',     autoComplete: 'new-password' },
              { id: 'password_confirmation', label: 'Confirm password', type: 'password', placeholder: '••••••••',     autoComplete: 'new-password' },
            ] as { id: keyof DetailsForm; label: string; type: string; placeholder: string; autoComplete: string }[]
          ).map(({ id, label, type, placeholder, autoComplete }) => (
            <div key={id}>
              <label htmlFor={id} className="block text-sm font-medium text-foreground mb-1.5">
                {label}
              </label>
              <input
                id={id}
                type={type}
                value={form[id]}
                onChange={(e) => updateForm(id, e.target.value)}
                required
                placeholder={placeholder}
                autoComplete={autoComplete}
                aria-invalid={errors[id]?.length > 0}
                aria-describedby={errors[id]?.length > 0 ? `${id}-error` : undefined}
                className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              />
              {errors[id]?.length > 0 && (
                <div id={`${id}-error`}>
                  {errors[id].map((msg) => (
                    <p key={msg} className="text-destructive text-xs mt-1">{msg}</p>
                  ))}
                </div>
              )}
            </div>
          ))}

          <Button type="submit" disabled={submitLoading} className="w-full h-10 mt-2">
            {submitLoading ? 'Creating account…' : 'Create account'}
          </Button>
          {submitError && (
            <p className="text-destructive text-xs text-center">{submitError}</p>
          )}
        </form>
      )}

      <p className="text-center text-sm text-muted-foreground mt-6">
        Already have an account?{' '}
        <Link href="/login" className="text-foreground font-medium hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  )
}
