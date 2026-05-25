# Jurivex AI — Claude Code Context

## Project

Multi-tenant legal AI compliance platform. Laravel 11 backend + Next.js 16 frontend, connected via Sanctum SPA auth.

## Repository Structure

```
/
├── backend/          Laravel 11 modular monolith
├── frontend/         Next.js 16 App Router
├── nginx/            Nginx reverse proxy config
├── docs/             Architecture, conventions, AI pipeline docs (public)
└── docker-compose.yml
```

## Running Commands

```bash
# All commands from repo root
docker compose up -d                                    # start everything
docker compose exec laravel php artisan test            # run backend tests
docker compose exec laravel php artisan migrate         # run migrations
docker compose exec nextjs npm run build                # verify frontend build
```

## Critical Conventions

See `docs/conventions.md` for full detail. Key rules:

1. **Scope every query by organization_id** — tenant isolation is non-negotiable
2. **Throw domain exceptions, not abort()** — use `DocumentNotFoundException`, `ForbiddenException`, etc.
3. **Events for cross-module side effects** — never call another module's service directly for async work
4. **PromptLoader for all prompts** — no hardcoded strings in jobs/services
5. **TDD** — write failing test, implement, verify pass, commit
6. **Never add Co-Authored-By trailers to git commits**
7. **Diary, plans, .claude/, .superpowers/ stay local only** — never push to GitHub

## Tech Stack Gotchas

- **Tailwind CSS v4** — no `tailwind.config.js`. CSS-only config in `globals.css @theme inline`
- **TanStack Query v5** — use `isPending` not `isLoading`
- **Zustand v5** — `create<State>()((set) => ({...}))` double-call pattern
- **Next.js 16** — middleware file renamed to `proxy.ts`, function renamed to `proxy`
- **Docker env_file** — bakes at container creation; use `--force-recreate` to reload
- **Roles seeder** — must run manually: `php artisan db:seed --class=RolesAndPermissionsSeeder`
- **Superadmin** — create via `php artisan superadmin:create`; manages orgs + invite codes at `/superadmin`

## Module Quick Reference

| Module | Service | Key operations |
|--------|---------|----------------|
| Auth | AuthService | register (invite-code), login, logout, me |
| Documents | DocumentService | upload, list, show, delete, downloadUrl |
| Compliance | ComplianceService | list, resolve |
| Organizations | OrganizationService | show, invite |
| Superadmin | SuperadminController | list orgs, create org, generate/list invite codes |
| AI | — | PromptLoader, DocumentAnalysisPipeline (Phase 2) |

## Exception Hierarchy

```
AppException
├── NotFoundException (404)
│   ├── DocumentNotFoundException
│   └── ComplianceFlagNotFoundException
├── ForbiddenException (403)
└── InvalidDocumentTransitionException (422)
```

## AI Pipeline Status

Phase 1.5: All AI jobs are stubs. `ProcessDocumentJob` transitions `pending → processing → analyzed` without real AI calls. Phase 2 will wire `DocumentAnalysisPipeline` with real OCR/LLM calls.
