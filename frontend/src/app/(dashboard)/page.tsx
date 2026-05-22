import {
  FileText,
  ShieldAlert,
  CheckCircle,
  Clock,
} from 'lucide-react'

const STATS = [
  { label: 'Total Documents', value: '—', icon: FileText, color: 'text-blue-400' },
  { label: 'Compliance Flags', value: '—', icon: ShieldAlert, color: 'text-amber-400' },
  { label: 'Resolved Flags', value: '—', icon: CheckCircle, color: 'text-green-400' },
  { label: 'Pending Review', value: '—', icon: Clock, color: 'text-orange-400' },
]

export default function DashboardPage() {
  return (
    <div>
      <div className="mb-6">
        <h2 className="text-lg font-semibold text-foreground">Overview</h2>
        <p className="text-sm text-muted-foreground mt-1">
          Welcome to Jurivex AI — your legal intelligence dashboard.
        </p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {STATS.map(({ label, value, icon: Icon, color }) => (
          <div
            key={label}
            className="rounded-xl border border-border bg-card p-5"
          >
            <div className="flex items-center justify-between mb-3">
              <p className="text-sm text-muted-foreground">{label}</p>
              <Icon size={18} className={color} />
            </div>
            <p className="text-2xl font-bold text-foreground">{value}</p>
          </div>
        ))}
      </div>

      <div className="mt-8 rounded-xl border border-border bg-card p-8 text-center">
        <FileText size={32} className="mx-auto text-muted-foreground mb-3" />
        <p className="text-foreground font-medium mb-1">No documents yet</p>
        <p className="text-muted-foreground text-sm">
          Upload your first document to get started with AI-powered legal analysis.
        </p>
      </div>
    </div>
  )
}
