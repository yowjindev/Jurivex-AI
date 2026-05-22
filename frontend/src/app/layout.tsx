import type { Metadata } from 'next'
import { Geist } from 'next/font/google'
import { Providers } from '@/components/shared/Providers'
import './globals.css'

const geist = Geist({ subsets: ['latin'], variable: '--font-sans' })

export const metadata: Metadata = {
  title: 'Jurivex AI',
  description: 'AI-powered Legal & Compliance Intelligence Platform',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en" className={`${geist.variable} dark h-full antialiased`}>
      <body className="min-h-full bg-background text-foreground">
        <Providers>{children}</Providers>
      </body>
    </html>
  )
}
