'use client'

import { Suspense } from 'react'
import Link from 'next/link'
import { useSearchParams } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, FileText, SearchX } from 'lucide-react'
import { searchDocuments } from '@/lib/api/documents'
import { LoadingSpinner } from '@/components/shared/LoadingSpinner'
import { parseApiError } from '@/lib/errors'
import type { SearchResult } from '@/types'

function SearchResults() {
  const searchParams = useSearchParams()
  const q = searchParams.get('q') ?? ''

  const { data, isPending, error } = useQuery({
    queryKey:  ['search', q],
    queryFn:   () => searchDocuments(q),
    enabled:   q.length >= 2,
  })

  if (q.length < 2) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-muted-foreground">
        <SearchX size={32} className="mb-3" />
        <p className="text-sm">Enter at least 2 characters to search.</p>
      </div>
    )
  }

  if (isPending) return <LoadingSpinner />

  if (error) {
    return <p className="text-sm text-destructive">{parseApiError(error)}</p>
  }

  const results: SearchResult[] = data?.data ?? []

  if (results.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-muted-foreground">
        <SearchX size={32} className="mb-3" />
        <p className="text-sm">No results found for &ldquo;{q}&rdquo;.</p>
        <p className="text-xs mt-1">Try a different query, or upload and analyze more documents first.</p>
      </div>
    )
  }

  return (
    <div className="space-y-3">
      <p className="text-xs text-muted-foreground">{results.length} result{results.length !== 1 ? 's' : ''}</p>
      {results.map((result) => (
        <Link
          key={result.chunk_id}
          href={`/documents/${result.document_id}`}
          className="block rounded-lg border border-border bg-card p-4 hover:bg-accent transition-colors"
        >
          <div className="flex items-start justify-between gap-2 mb-2">
            <div className="flex items-center gap-2 min-w-0">
              <FileText size={14} className="text-primary shrink-0" />
              <span className="text-sm font-medium text-foreground truncate">
                {result.document_title}
              </span>
            </div>
            <span className="text-xs text-muted-foreground shrink-0 bg-muted px-1.5 py-0.5 rounded">
              {Math.round(result.score * 100)}% match
            </span>
          </div>
          <p className="text-sm text-muted-foreground line-clamp-3 leading-relaxed">
            {result.chunk_text}
          </p>
        </Link>
      ))}
    </div>
  )
}

export default function SearchPage() {
  const searchParams = useSearchParams()
  const q = searchParams.get('q') ?? ''

  return (
    <div className="space-y-5">
      <div className="flex items-center gap-3">
        <Link
          href="/documents"
          className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft size={14} />
          Documents
        </Link>
        <span className="text-muted-foreground">/</span>
        <h1 className="text-sm font-semibold text-foreground truncate">
          {q ? `"${q}"` : 'Search'}
        </h1>
      </div>

      <Suspense fallback={<LoadingSpinner />}>
        <SearchResults />
      </Suspense>
    </div>
  )
}
