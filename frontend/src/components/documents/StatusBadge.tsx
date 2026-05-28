import type { Document } from '@/types'

const STYLES: Record<Document['status'], string> = {
  pending:        'bg-yellow-400/10 text-yellow-400 border border-yellow-400/20',
  processing:     'bg-blue-400/10 text-blue-400 border border-blue-400/20',
  ocr_processing: 'bg-indigo-400/10 text-indigo-400 border border-indigo-400/20',
  ocr_completed:  'bg-cyan-400/10 text-cyan-400 border border-cyan-400/20',
  ai_processing:  'bg-violet-400/10 text-violet-400 border border-violet-400/20',
  analyzed:       'bg-green-400/10 text-green-400 border border-green-400/20',
  failed:         'bg-red-400/10 text-red-400 border border-red-400/20',
}

const LABELS: Record<Document['status'], string> = {
  pending:        'Pending',
  processing:     'Processing',
  ocr_processing: 'OCR Processing',
  ocr_completed:  'OCR Completed',
  ai_processing:  'AI Processing',
  analyzed:       'Analyzed',
  failed:         'Failed',
}

export function StatusBadge({ status }: { status: Document['status'] }) {
  return (
    <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ${STYLES[status]}`}>
      {LABELS[status]}
    </span>
  )
}
