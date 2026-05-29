'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ShieldAlert, CheckCircle, Bot } from 'lucide-react'
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

type SourceFilter = 'all' | 'ai' | 'manual'

export default function CompliancePage() {
  const queryClient = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const canResolve = user?.roles.includes('admin') || user?.roles.includes('manager')
  const [sourceFilter, setSourceFilter] = useState<SourceFilter>('all')

  const { data, isPending, isError } = useQuery({
    queryKey: ['compliance', 'flags'],
    queryFn:  () => listFlags(),
  })

  const resolve = useMutation({
    mutationFn: (id: string) => resolveFlag(id),
    onSuccess:  () => queryClient.invalidateQueries({ queryKey: ['compliance', 'flags'] }),
  })

  const allFlags: ComplianceFlag[] = data?.data ?? []
  const flags = allFlags.filter((f) => {
    if (sourceFilter === 'ai')     return f.ai_generated
    if (sourceFilter === 'manual') return !f.ai_generated
    return true
  })
  const openCount = allFlags.filter((f) => !f.is_resolved).length

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Compliance Flags</h2>
          <p className="text-sm text-muted-foreground mt-1">
            {data ? `${openCount} open · ${data.meta.total} total` : ' '}
          </p>
        </div>

        <div role="group" aria-label="Filter by source" className="flex gap-1 rounded-lg border border-border p-1 text-xs">
          {(['all', 'ai', 'manual'] as SourceFilter[]).map((s) => (
            <button
              key={s}
              onClick={() => setSourceFilter(s)}
              aria-pressed={sourceFilter === s}
              className={`px-3 py-1 rounded-md font-medium transition-colors ${
                sourceFilter === s
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              {s === 'all' ? 'All' : s === 'ai' ? 'AI-detected' : 'Manual'}
            </button>
          ))}
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
          description={
            sourceFilter === 'ai'
              ? 'No AI-detected flags found.'
              : sourceFilter === 'manual'
                ? 'No manually created flags found.'
                : 'Flags will appear here once AI analysis detects compliance issues.'
          }
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
                  <div className="flex items-center gap-2 mb-1 flex-wrap">
                    <SeverityBadge severity={flag.severity} />
                    <span className="text-xs text-muted-foreground uppercase tracking-wide">{flag.type}</span>
                    {flag.ai_generated && (
                      <span className="inline-flex items-center gap-1 text-xs text-violet-400 bg-violet-400/10 border border-violet-400/20 rounded-md px-1.5 py-0.5">
                        <Bot size={11} />
                        AI
                        {flag.confidence !== null && (
                          <span className="text-violet-300/80">{Math.round(flag.confidence * 100)}%</span>
                        )}
                      </span>
                    )}
                    {flag.is_resolved && (
                      <span className="inline-flex items-center gap-1 text-xs text-green-400">
                        <CheckCircle size={12} />
                        Resolved
                      </span>
                    )}
                  </div>
                  <p className="text-sm font-medium text-foreground">{flag.title}</p>
                  <p className="text-sm text-muted-foreground mt-1 leading-relaxed">{flag.description}</p>
                  {flag.explanation && !flag.is_resolved && (
                    <p className="text-xs text-muted-foreground/70 mt-1 italic leading-relaxed">{flag.explanation}</p>
                  )}
                  {flag.due_date && (
                    <p className="text-xs text-muted-foreground mt-2">Due: {formatDate(flag.due_date)}</p>
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
