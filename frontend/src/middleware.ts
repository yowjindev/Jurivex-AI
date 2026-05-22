import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

const PROTECTED_PREFIXES = ['/dashboard', '/documents', '/compliance', '/organization', '/settings']
const AUTH_ONLY_PREFIXES = ['/login', '/register']

export function middleware(request: NextRequest) {
  const isAuthenticated = request.cookies.has('auth_check')
  const { pathname } = request.nextUrl

  const isProtected = PROTECTED_PREFIXES.some((p) => pathname.startsWith(p))
  const isAuthOnly = AUTH_ONLY_PREFIXES.some((p) => pathname.startsWith(p))

  if (isProtected && !isAuthenticated) {
    return NextResponse.redirect(new URL('/login', request.url))
  }

  if (isAuthOnly && isAuthenticated) {
    return NextResponse.redirect(new URL('/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico).*)'],
}
