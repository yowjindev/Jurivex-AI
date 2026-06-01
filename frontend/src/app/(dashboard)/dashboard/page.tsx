'use client'

import Link from 'next/link'
import { useQuery } from '@tanstack/react-query'
import {
  FileText,
  ShieldAlert,
  CheckCircle,
  Clock,
  ArrowRight,
} from 'lucide-react'
import { listDocuments } from '@/lib/api/documents'
import { listFlags } from '@/lib/api/compliance'
import { StatusBadge } from '@/components/documents/StatusBadge'
import { LoadingSpinner } from '@/components/shared/LoadingSpinner'
import type { Document } from '@/types'

const PROCESSING_STATUSES = new Set([
  'pending', 'processing', 'ocr_processing', 'ocr_completed', 'ai_processing',
])

function formatBytes(bytes: number): string {
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

export default function DashboardPage() {
  const { data: documentsData, isPending: docsPending } = useQuery({
    queryKey: ['documents'],
    queryFn:  () => listDocuments(),
    refetchInterval: (query) => {
      const docs: Document[] = query.state.data?.data ?? []
      return docs.some((d) => PROCESSING_STATUSES.has(d.status)) ? 3000 : false
    },
  })

  const { data: flagsData, isPending: flagsPending } = useQuery({
    queryKey: ['compliance-flags'],
    queryFn:  () => listFlags(),
  })

  const docs         = documentsData?.data ?? []
  const totalDocs    = documentsData?.meta.total ?? 0
  const totalFlags   = flagsData?.meta.total ?? 0
  const resolvedFlags = flagsData?.data.filter((f) => f.is_resolved).length ?? 0
  const processing   = docs.filter((d) => PROCESSING_STATUSES.has(d.status)).length
  const recentDocs   = docs.slice(0, 6)

  const stats = [
    {
      label: 'Total Documents',
      value: docsPending ? '…' : String(totalDocs),
      icon:  FileText,
      color: 'text-blue-500',
      href:  '/documents',
    },
    {
      label: 'Compliance Flags',
      value: flagsPending ? '…' : String(totalFlags),
      icon:  ShieldAlert,
      color: 'text-amber-500',
      href:  '/compliance',
    },
    {
      label: 'Resolved Flags',
      value: flagsPending ? '…' : String(resolvedFlags),
      icon:  CheckCircle,
      color: 'text-green-500',
      href:  '/compliance',
    },
    {
      label: 'Processing',
      value: docsPending ? '…' : String(processing),
      icon:  Clock,
      color: 'text-orange-500',
      href:  '/documents',
    },
  ]

  return (
    <div className="space-y-8">
      {/* Header */}
      <div>
        <h2 className="text-lg font-semibold text-foreground">Overview</h2>
        <p className="text-sm text-muted-foreground mt-0.5">
          Your legal intelligence dashboard.
        </p>
      </div>

      {/* Stat cards */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stats.map(({ label, value, icon: Icon, color, href }) => (
          <Link
            key={label}
            href={href}
            className="rounded-xl border border-border bg-card p-5 hover:bg-accent transition-colors"
          >
            <div className="flex items-center justify-between mb-3">
              <p className="text-sm text-muted-foreground">{label}</p>
              <Icon size={18} className={color} />
            </div>
            <p className="text-2xl font-bold text-foreground">{value}</p>
          </Link>
        ))}
      </div>

      {/* Recent documents */}
      <div>
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-sm font-semibold text-foreground">Recent Documents</h3>
          <Link
            href="/documents"
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
          >
            View all <ArrowRight size={12} />
          </Link>
        </div>

        {docsPending ? (
          <div className="flex justify-center py-10">
            <LoadingSpinner />
          </div>
        ) : recentDocs.length === 0 ? (
          <div className="rounded-xl border border-border bg-card p-10 text-center">
            <FileText size={28} className="mx-auto text-muted-foreground mb-3" />
            <p className="text-sm font-medium text-foreground mb-1">No documents yet</p>
            <p className="text-xs text-muted-foreground mb-4">
              Upload your first document to get started with AI-powered legal analysis.
            </p>
            <Link
              href="/documents"
              className="inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
            >
              Go to Documents <ArrowRight size={12} />
            </Link>
          </div>
        ) : (
          <div className="rounded-xl border border-border bg-card divide-y divide-border">
            {recentDocs.map((doc) => (
              <Link
                key={doc.id}
                href={`/documents/${doc.id}`}
                className="flex items-center justify-between px-5 py-3.5 hover:bg-accent transition-colors first:rounded-t-xl last:rounded-b-xl"
              >
                <div className="flex items-center gap-3 min-w-0">
                  <FileText size={15} className="text-muted-foreground shrink-0" />
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-foreground truncate">
                      {doc.title || doc.original_filename}
                    </p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      {formatBytes(doc.file_size)}
                      {doc.category ? ` · ${doc.category}` : ''}
                      {doc.created_at ? ` · ${formatDate(doc.created_at)}` : ''}
                    </p>
                  </div>
                </div>
                <div className="ml-4 shrink-0">
                  <StatusBadge status={doc.status} />
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
