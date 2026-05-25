interface ErrorStateProps {
  title?: string
  description?: string
}

export function ErrorState({
  title = 'Something went wrong.',
  description = 'Check your connection and refresh the page.',
}: ErrorStateProps) {
  return (
    <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-6 text-center">
      <p className="text-sm text-destructive font-medium">{title}</p>
      <p className="text-xs text-muted-foreground mt-1">{description}</p>
    </div>
  )
}
