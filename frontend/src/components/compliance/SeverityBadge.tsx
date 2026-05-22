import type { ComplianceFlag } from '@/types'

const STYLES: Record<ComplianceFlag['severity'], string> = {
  low:      'bg-blue-400/10 text-blue-400 border border-blue-400/20',
  medium:   'bg-yellow-400/10 text-yellow-400 border border-yellow-400/20',
  high:     'bg-orange-400/10 text-orange-400 border border-orange-400/20',
  critical: 'bg-red-400/10 text-red-400 border border-red-400/20',
}

export function SeverityBadge({ severity }: { severity: ComplianceFlag['severity'] }) {
  return (
    <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ${STYLES[severity]}`}>
      {severity}
    </span>
  )
}
