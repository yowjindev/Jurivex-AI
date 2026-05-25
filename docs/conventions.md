# Development Conventions

## PHP / Laravel

### Module structure
Every module under `app/Modules/` follows the same layout: `DTOs/`, `Events/`, `Http/`, `Jobs/`, `Listeners/`, `Models/`, `Providers/`, `Repositories/Contracts/`, `Routes/`, `Services/`.

### Services are the business logic layer
Controllers call services. Services call repositories. Repositories call Eloquent. Never put business logic in controllers or models.

### Repositories abstract all Eloquent queries
Never call `Model::where(...)` in a service. Always go through a repository. Every repository has an interface in `Repositories/Contracts/`.

### DTOs are immutable value objects
Use `readonly` properties. DTOs carry validated request data from controller/request to service.

### Always scope by organization_id
Every query filters by `$user->organization_id`. Never allow cross-tenant data access.

### Throw domain exceptions, not abort()
Use `throw new DocumentNotFoundException()` not `abort(404)`. Exceptions extend `AppException` for consistent JSON responses.

### AuditLog every significant action
Any action that modifies data should write an `AuditLog` entry. Include `metadata` for context.

### TDD: write the test first
Write the failing test, run it (confirm FAIL), implement, run again (confirm PASS), commit.

## TypeScript / Next.js

### Client components only where needed
Default to Server Components. Add `'use client'` only when hooks (useState, useEffect, etc.) are required.

### TanStack Query v5 API
- Use `isPending` not `isLoading`
- Import `useQueryClient` separately from `@tanstack/react-query`
- Use `invalidateQueries` in `onSuccess` callbacks

### Zustand selector pattern
`useAuthStore((s) => s.user)` — never `useAuthStore()` (subscribes to entire store).

### Error handling
Use `parseApiError(error)` from `@/lib/errors` for all mutation error callbacks. Never display raw axios error messages.

### Shared components
Use `LoadingSpinner`, `EmptyState`, `ErrorState` from `@/components/shared/` instead of inline implementations.

### FormData uploads
Pass `headers: { 'Content-Type': undefined }` to let the browser set the correct multipart boundary.

## Git

### Commit message format
`type: Phase X.Y — short description`

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`

### Never add Co-Authored-By trailers

### Never commit to main without review
Feature work should be on branches. Hotfixes may go direct to main with care.

## Docker

### docker compose restart does not reload env_file
Use `docker compose up -d <service> --force-recreate` to reload environment variables.

### NEXT_PUBLIC_ vars bake at build time
If you change a `NEXT_PUBLIC_` env var, you must recreate the container — it is baked into the JavaScript bundle at build time.

### Running commands
- Laravel: `docker compose exec laravel php artisan ...`
- Next.js: `docker compose exec nextjs npm run ...`
- PostgreSQL: `docker compose exec postgres psql -U jurivexai -d jurivexai`
