'use client'

import { useState, useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Upload, FileText, Trash2, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { StatusBadge } from '@/components/documents/StatusBadge'
import { LoadingSpinner } from '@/components/shared/LoadingSpinner'
import { EmptyState } from '@/components/shared/EmptyState'
import { listDocuments, uploadDocument, deleteDocument } from '@/lib/api/documents'
import { parseApiError } from '@/lib/errors'
import type { Document } from '@/types'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

export default function DocumentsPage() {
  const queryClient = useQueryClient()
  const [modalOpen, setModalOpen] = useState(false)
  const [file, setFile] = useState<File | null>(null)
  const [category, setCategory] = useState('')
  const [uploadError, setUploadError] = useState('')
  const fileRef = useRef<HTMLInputElement>(null)

  const { data, isPending } = useQuery({
    queryKey: ['documents'],
    queryFn: () => listDocuments(),
  })

  const upload = useMutation({
    mutationFn: () => uploadDocument(file!, category || undefined),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
      setModalOpen(false)
      setFile(null)
      setCategory('')
      setUploadError('')
    },
    onError: (error) => {
      setUploadError(parseApiError(error))
    },
  })

  const remove = useMutation({
    mutationFn: (id: string) => deleteDocument(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['documents'] }),
  })

  const documents: Document[] = data?.data ?? []

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Documents</h2>
          <p className="text-sm text-muted-foreground mt-1">
            {data ? `${data.meta.total} document${data.meta.total !== 1 ? 's' : ''}` : ' '}
          </p>
        </div>
        <Button onClick={() => setModalOpen(true)} className="flex items-center gap-2">
          <Upload size={15} />
          Upload
        </Button>
      </div>

      {isPending && (
        <div className="flex justify-center py-16">
          <LoadingSpinner />
        </div>
      )}

      {!isPending && documents.length === 0 && (
        <EmptyState
          icon={FileText}
          title="No documents yet"
          description="Upload a PDF, DOCX, DOC, or TXT file to get started."
          action={
            <Button onClick={() => setModalOpen(true)} className="flex items-center gap-2 mx-auto">
              <Upload size={15} />
              Upload your first document
            </Button>
          }
        />
      )}

      {!isPending && documents.length > 0 && (
        <div className="rounded-xl border border-border bg-card overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wide">Name</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wide">Status</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wide">Size</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wide">Uploaded</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {documents.map((doc) => (
                <tr key={doc.id} className="border-b border-border last:border-0 hover:bg-accent/30 transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <FileText size={15} className="text-muted-foreground shrink-0" />
                      <span className="text-foreground font-medium truncate max-w-xs">{doc.title || doc.original_filename}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3"><StatusBadge status={doc.status} /></td>
                  <td className="px-4 py-3 text-muted-foreground">{formatBytes(doc.file_size)}</td>
                  <td className="px-4 py-3 text-muted-foreground">{formatDate(doc.created_at)}</td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => remove.mutate(doc.id)}
                      disabled={remove.isPending}
                      title="Delete"
                      className="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors ml-auto"
                    >
                      <Trash2 size={14} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div className="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-xl">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-base font-semibold text-foreground">Upload Document</h3>
              <button
                onClick={() => { setModalOpen(false); setFile(null); setCategory(''); setUploadError('') }}
                className="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent transition-colors"
              >
                <X size={15} />
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-foreground mb-1.5">
                  File <span className="text-muted-foreground font-normal">(PDF, DOCX, DOC, TXT — max 50 MB)</span>
                </label>
                <input
                  ref={fileRef}
                  type="file"
                  accept=".pdf,.docx,.doc,.txt"
                  onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                  className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:text-primary-foreground file:px-3 file:py-1 file:text-xs file:font-medium"
                />
                {file && (
                  <p className="text-xs text-muted-foreground mt-1">{file.name} — {formatBytes(file.size)}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-1.5">
                  Category <span className="text-muted-foreground font-normal">(optional)</span>
                </label>
                <input
                  type="text"
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  placeholder="e.g. Contract, NDA, Policy"
                  className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                />
              </div>

              {uploadError && (
                <div className="rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm px-4 py-3">
                  {uploadError}
                </div>
              )}

              <div className="flex gap-3 pt-2">
                <Button
                  type="button"
                  onClick={() => upload.mutate()}
                  disabled={!file || upload.isPending}
                  className="flex-1"
                >
                  {upload.isPending ? 'Uploading…' : 'Upload'}
                </Button>
                <button
                  type="button"
                  onClick={() => { setModalOpen(false); setFile(null); setCategory(''); setUploadError('') }}
                  className="flex-1 rounded-lg border border-border bg-card px-4 py-2 text-sm font-medium text-foreground hover:bg-accent transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
