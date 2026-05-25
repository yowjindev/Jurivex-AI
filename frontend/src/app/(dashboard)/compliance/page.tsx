'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ShieldAlert, CheckCircle } from 'lucide-react'
import { useAuthStore } from '@/stores/authStore'
import { SeverityBadge } from '@/components/compliance/SeverityBadge'
import { LoadingSpinner } from '@/components/shared/LoadingSpinner'
import { EmptyState } from '@/components/shared/EmptyState'
import { ErrorState } from '@/components/shared/ErrorState'
import { listFlags, resolveFlag } from '@/lib/api/compliance'
import type { ComplianceFlag } from '@/types'

function formatDate(date: string | null): string {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

export default function CompliancePage() {
  const queryClient = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const canResolve = user?.roles.includes('admin') || user?.roles.includes('manager')

  const { data, isPending, isError } = useQuery({
    queryKey: ['compliance', 'flags'],
    queryFn: () => listFlags(),
  })

  const resolve = useMutation({
    mutationFn: (id: string) => resolveFlag(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['compliance', 'flags'] }),
  })

  const flags: ComplianceFlag[] = data?.data ?? []
  const openCount = flags.filter((f) => !f.is_resolved).length

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Compliance Flags</h2>
          <p className="text-sm text-muted-foreground mt-1">
            {data ? `${openCount} open · ${data.meta.total} total` : ' '}
          </p>
        </div>
      </div>

      {isPending && (
        <div className="flex justify-center py-16">
          <LoadingSpinner />
        </div>
      )}

      {isError && (
        <ErrorState
          title="Failed to load compliance flags."
          description="Check your connection and refresh the page."
        />
      )}

      {!isPending && !isError && flags.length === 0 && (
        <EmptyState
          icon={ShieldAlert}
          title="No compliance flags"
          description="Flags will appear here once AI analysis detects compliance issues."
        />
      )}

      {!isPending && !isError && flags.length > 0 && (
        <div className="space-y-3">
          {flags.map((flag) => (
            <div
              key={flag.id}
              className={`rounded-xl border bg-card p-5 transition-opacity ${
                flag.is_resolved ? 'opacity-50 border-border' : 'border-border'
              }`}
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <SeverityBadge severity={flag.severity} />
                    <span className="text-xs text-muted-foreground uppercase tracking-wide">{flag.type}</span>
                    {flag.is_resolved && (
                      <span className="inline-flex items-center gap-1 text-xs text-green-400">
                        <CheckCircle size={12} />
                        Resolved
                      </span>
                    )}
                  </div>
                  <p className="text-sm font-medium text-foreground">{flag.title}</p>
                  <p className="text-sm text-muted-foreground mt-1 leading-relaxed">{flag.description}</p>
                  {flag.due_date && (
                    <p className="text-xs text-muted-foreground mt-2">
                      Due: {formatDate(flag.due_date)}
                    </p>
                  )}
                </div>

                {canResolve && !flag.is_resolved && (
                  <button
                    onClick={() => { if (resolve.isPending) return; resolve.mutate(flag.id) }}
                    disabled={resolve.isPending}
                    className="shrink-0 rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-foreground hover:bg-accent transition-colors disabled:opacity-50"
                  >
                    {resolve.isPending && resolve.variables === flag.id ? 'Resolving…' : 'Resolve'}
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
