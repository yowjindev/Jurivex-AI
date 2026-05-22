export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground text-xl font-bold mb-4">
            J
          </div>
          <h1 className="text-2xl font-bold text-foreground">Jurivex AI</h1>
          <p className="text-muted-foreground text-sm mt-1">
            Legal &amp; Compliance Intelligence
          </p>
        </div>
        {children}
      </div>
    </div>
  )
}
