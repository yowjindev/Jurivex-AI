# Frontend — Claude Code Context

Next.js 16 App Router at `frontend/`.

## IMPORTANT: Breaking Changes in This Version

This is Next.js 16. Read `node_modules/next/dist/docs/` before editing routing or middleware. Known breaking changes already applied:
- `middleware.ts` → `proxy.ts`, function `middleware` → `proxy`
- Route groups `(group)/page.tsx` maps to `/`, not `/group` — use `(group)/segment/page.tsx`

## Stack

- **Next.js 16.2.6** App Router, Turbopack dev
- **React 19** — server components by default, `'use client'` only where hooks are needed
- **Tailwind CSS v4** — CSS-only config in `globals.css @theme inline`, no `tailwind.config.js`
- **shadcn/ui v4.7.0** — uses `@base-ui/react` primitives (not Radix UI)
- **TanStack Query v5** — `isPending` not `isLoading`, import `useQueryClient` separately
- **Zustand v5** — `create<State>()((set) => ({...}))` double-call pattern
- **axios** — `withCredentials: true`; CSRF auto-fetched before first non-GET

## Component Structure

```
src/components/
├── ui/           shadcn/ui primitives (Button, etc.)
├── dashboard/    DashboardShell, Header, Sidebar
├── documents/    StatusBadge
├── compliance/   SeverityBadge
└── shared/       LoadingSpinner, EmptyState, ErrorState, Providers
```

## Key Patterns

- `parseApiError(error)` from `@/lib/errors` for all mutation error display
- `useAuthStore((s) => s.user)` — Zustand selector, never full store
- `headers: { 'Content-Type': undefined }` for FormData uploads
- `auth_check` cookie for proxy-level route protection (not `laravel_session` — it's httpOnly)
- `resetCsrf()` on logout to allow CSRF re-fetch on next login
- `useAuth()` must be called in any layout that guards by role — it hydrates the Zustand store

## Route Groups

- `(auth)/` — login, register pages (no shell)
- `(dashboard)/` — protected pages with DashboardShell (calls `useAuth`)
- `(superadmin)/` — superadmin-only pages (layout calls `useAuth`, redirects non-superadmins)

## Build Verification

```bash
docker compose exec nextjs npm run build 2>&1 | tail -20
```
