import type { LucideIcon } from 'lucide-react'

interface EmptyStateProps {
  icon: LucideIcon
  title: string
  description: string
  action?: React.ReactNode
}

export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
  return (
    <div className="rounded-xl border border-border bg-card p-12 text-center">
      <Icon size={32} className="mx-auto text-muted-foreground mb-3" />
      <p className="text-foreground font-medium mb-1">{title}</p>
      <p className="text-muted-foreground text-sm mb-4">{description}</p>
      {action}
    </div>
  )
}
