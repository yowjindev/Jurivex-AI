# Jurivex AI — Platform Design Spec
**Date:** 2026-05-18
**Status:** Approved
**Phase:** 1 (MVP Foundation)

---

## 1. Project Overview

Jurivex AI is an AI-powered Legal & Compliance Intelligence Platform for businesses. It is **not** legal advice software and does not replace licensed legal professionals. Its purpose is to:

- Analyze and summarize legal/compliance documents into plain English
- Detect risks, deadlines, and compliance gaps
- Provide executive-friendly intelligence dashboards
- Centralize legal operations workflows for compliance-heavy industries

**Target industries:** Accounting firms, HR agencies, mining companies, construction firms, logistics companies, SMEs.

**Deployment target (Phase 1):** Single EC2 instance, Docker Compose.

---

## 2. Key Architecture Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Authentication | Laravel Sanctum (SPA mode) | Laravel-native, zero extra deps, httpOnly cookies, supports API tokens for future mobile/third-party |
| File Storage | AWS S3 + MinIO (local dev) | Production-grade, infinitely scalable, Laravel Storage facade abstracts the driver |
| Multi-tenancy | Schema-level from day one | `organization_id` on every tenant table; prevents painful migration later |
| Roles & Permissions | spatie/laravel-permission | Industry standard, extensible to custom roles per org, enterprise clients will require it |
| Dashboard Layout | Icon sidebar (collapsed) + stats grid | Maximum content area, modern legaltec aesthetic |
| Backend Structure | Domain-driven modules (nwidart/laravel-modules) | Each domain self-contained; new features (OCR, AI, audit) slot in as new modules |

---

## 3. Tech Stack

### Frontend
- **Next.js** (latest, App Router)
- **TypeScript** (strict mode)
- **TailwindCSS** + **shadcn/ui**
- **Zustand** (global/auth state)
- **TanStack Query** (server state, caching, mutations)
- **axios** (typed API client with interceptors)

### Backend
- **Laravel 12** (PHP 8.4)
- **nwidart/laravel-modules** (domain module structure)
- **spatie/laravel-permission** (RBAC)
- **Laravel Sanctum** (SPA authentication)
- **Laravel Horizon** (Redis queue management)

### Infrastructure
- **PostgreSQL** (primary database)
- **pgvector** (vector embeddings, ready from day one)
- **Redis** (queue backend, caching)
- **MinIO** (local S3-compatible object storage)
- **Nginx** (reverse proxy)
- **Docker + Docker Compose**

---

## 4. Monorepo Structure

```
/ JurivexAI (monorepo root)
├── backend/                    # Laravel 12 API
│   ├── app/
│   │   └── Modules/
│   │       ├── Auth/
│   │       ├── Organizations/
│   │       ├── Documents/
│   │       ├── Compliance/
│   │       └── AI/             # Stub only in Phase 1
│   ├── database/
│   └── .env.example
├── frontend/                   # Next.js app
│   ├── src/
│   │   ├── app/
│   │   │   ├── (auth)/
│   │   │   │   ├── login/
│   │   │   │   └── register/
│   │   │   └── (dashboard)/
│   │   │       ├── layout.tsx
│   │   │       ├── page.tsx
│   │   │       ├── documents/
│   │   │       ├── compliance/
│   │   │       ├── organization/
│   │   │       └── settings/
│   │   ├── components/
│   │   │   ├── ui/             # shadcn — never edit directly
│   │   │   ├── layout/
│   │   │   ├── documents/
│   │   │   ├── compliance/
│   │   │   └── shared/
│   │   ├── lib/
│   │   │   ├── api/            # Typed axios client
│   │   │   ├── auth/           # Sanctum helpers
│   │   │   └── utils.ts
│   │   ├── hooks/
│   │   ├── types/
│   │   └── middleware.ts       # Route protection
│   └── .env.example
├── docker/                     # Dockerfiles per service
├── nginx/                      # Nginx config
├── docs/                       # Architecture docs, specs
├── docker-compose.yml
├── Makefile
└── README.md
```

---

## 5. Docker Services

| Service | Image | Port(s) | Purpose |
|---|---|---|---|
| nginx | nginx:alpine | 80, 443 | Reverse proxy — routes `/api/*` to Laravel, `/*` to Next.js |
| laravel | custom php-fpm | 9000 (internal) | Laravel API server |
| queue | same as laravel | none | Laravel Horizon queue worker (same image, different command) |
| nextjs | custom node | 3000 (internal) | Next.js frontend |
| postgres | postgres:16 | 5432 | Primary database |
| redis | redis:alpine | 6379 | Queue backend + cache |
| minio | minio/minio | 9000, 9001 | Local S3-compatible storage |

**Traffic flow:** Browser → Nginx :80 → `/api/*` → Laravel (php-fpm) | `/*` → Next.js :3000

---

## 6. Database Schema

All IDs are UUIDs. All tenant tables carry `organization_id` as isolation key. Spatie permission tables auto-generated by package.

### `organizations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| name | string | |
| slug | string unique | URL-safe identifier |
| industry | string | accounting, hr, mining, construction, logistics, other |
| plan | string | default: 'free' |
| settings | jsonb nullable | Feature flags, org preferences |
| is_active | boolean | |
| timestamps + softDeletes | | |

### `users`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| organization_id | uuid FK → organizations | Tenant isolation |
| name | string | |
| email | string unique | Globally unique — one email = one account = one organization (MVP constraint) |
| password | hashed | bcrypt |
| avatar | string nullable | S3 path |
| is_active | boolean | |
| last_login_at | timestamp nullable | |
| timestamps + softDeletes | | |

### `documents`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| organization_id | uuid FK | Tenant isolation |
| uploaded_by | uuid FK → users | |
| title | string | User-provided or derived from filename |
| original_filename | string | |
| mime_type | string | |
| file_size | bigint | Bytes |
| s3_path | string | Relative path in S3 bucket |
| status | enum | pending, processing, analyzed, failed |
| category | string nullable | Contract, Policy, Regulation, etc. |
| tags | jsonb nullable | |
| timestamps + softDeletes | | |

### `document_analyses` (AI stub — schema ready)
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| document_id | uuid FK → documents | |
| summary | text nullable | AI-generated summary |
| key_points | jsonb nullable | Extracted key clauses |
| risk_score | decimal nullable | 0.0–1.0 |
| ai_model | string nullable | Model used (gpt-4o, claude-3-5, etc.) |
| embedding | vector(1536) | pgvector — OpenAI/Claude embedding |
| analyzed_at | timestamp nullable | |
| timestamps | | |

### `compliance_flags`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| organization_id | uuid FK | Tenant isolation |
| document_id | uuid FK nullable | May not be tied to a document |
| type | enum | risk, deadline, alert |
| severity | enum | low, medium, high, critical |
| title | string | |
| description | text | |
| due_date | date nullable | |
| is_resolved | boolean | default: false |
| timestamps | | |

### `audit_logs` (append-only, immutable)
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| organization_id | uuid FK | |
| user_id | uuid FK nullable | null for system events |
| action | string | e.g. `document.upload`, `user.login` |
| entity_type | string nullable | Model class name |
| entity_id | uuid nullable | |
| payload | jsonb nullable | Before/after or context data |
| ip_address | string | |
| created_at | timestamp | No updated_at — immutable by design |

---

## 7. Backend Module Structure

Every module follows this internal layout (example: Documents):

```
Modules/Documents/
├── Http/
│   ├── Controllers/
│   │   └── DocumentController.php    # Thin — delegates to Service
│   ├── Requests/
│   │   ├── UploadDocumentRequest.php
│   │   └── UpdateDocumentRequest.php
│   └── Resources/
│       └── DocumentResource.php      # API output transformer
├── Services/
│   └── DocumentService.php           # All business logic lives here
├── Repositories/
│   ├── DocumentRepository.php
│   └── Contracts/
│       └── IDocumentRepository.php   # Interface for DI
├── DTOs/
│   └── UploadDocumentDTO.php
├── Jobs/
│   └── ProcessDocumentJob.php        # Dispatched to Redis queue
├── Events/
├── Listeners/
├── Models/
│   └── Document.php
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Routes/
│   └── api.php
└── Providers/
    └── DocumentsServiceProvider.php
```

### Request → Response Flow
1. **FormRequest** — validates input, authorizes via policy
2. **Controller** — maps request to DTO, calls Service (no logic here)
3. **Service** — all business logic, calls Repository
4. **Repository** — all DB queries via Eloquent
5. **Service** — dispatches Jobs to Redis queue if async work needed
6. **Controller** — wraps Service result in ApiResource → JSON response

### Standard API Response Envelope
```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "meta": { "pagination": {} }
}
```

---

## 8. API Routes (Phase 1)

All routes prefixed `/api/v1/`. All protected routes require Sanctum auth.

```
# Auth (public)
POST   /api/v1/auth/register
POST   /api/v1/auth/login
DELETE /api/v1/auth/logout
GET    /api/v1/auth/me

# Documents (auth required)
GET    /api/v1/documents
POST   /api/v1/documents
GET    /api/v1/documents/{id}
PATCH  /api/v1/documents/{id}
DELETE /api/v1/documents/{id}

# Compliance (auth required)
GET    /api/v1/compliance/flags
PATCH  /api/v1/compliance/flags/{id}/resolve

# Organization (auth required)
GET    /api/v1/organization
GET    /api/v1/organization/members
POST   /api/v1/organization/invitations
```

---

## 9. Frontend Architecture

### Auth Flow (Sanctum SPA)
1. Next.js calls `GET /sanctum/csrf-cookie` → Laravel sets `XSRF-TOKEN` cookie
2. `POST /api/v1/auth/login` → Laravel sets httpOnly session cookie
3. All subsequent requests auto-send session cookie
4. `middleware.ts` protects dashboard routes — checks `/api/v1/auth/me`, redirects to `/login` if unauthenticated
5. Auth state held in Zustand store, hydrated via `useAuth` hook on mount

### Route Groups
- `(auth)` — public: `/login`, `/register`
- `(dashboard)` — protected: all app routes, wrapped in sidebar + header layout

### State Management
- **Zustand** — auth state, current org, user profile
- **TanStack Query** — all server data: documents list, compliance flags, org members. Handles caching, background refetch, optimistic updates.

### UI System
- **shadcn/ui** in `components/ui/` — never edited directly
- Domain components in `components/documents/`, `components/compliance/`, etc.
- Dark mode by default (`darkMode: 'class'` in Tailwind config)
- CSS variables for design tokens
- Inter font

---

## 10. Document Upload Flow

```
User selects file(s)
  → Frontend: POST /api/v1/documents (multipart/form-data, progress tracking)
  → Laravel: Validate (type, size, MIME) → Store to S3 → Create DB record (status: pending)
  → Dispatch ProcessDocumentJob → Redis queue
  → Worker picks up job → updates status (processing → analyzed | failed)
  → Frontend: TanStack Query auto-refetch → user sees updated status badge
```

### Document Status States
| Status | Meaning |
|---|---|
| `pending` | Uploaded, job queued |
| `processing` | Worker picked up job |
| `analyzed` | AI/OCR complete, summary available |
| `failed` | Job failed — retry available |

### Upload Constraints
- Accepted types: PDF, DOCX, DOC, TXT
- Max file size: 50MB per file
- Max files per upload: 10
- MIME type validation on backend (not just extension)
- S3 path pattern: `org/{organization_id}/documents/{uuid}/{filename}`
- Downloads via signed S3 URLs (raw S3 URLs never exposed)

### Phase 1 AI/OCR Stubs (no-op job classes, ready for implementation)
Located in `Modules/AI/Jobs/`:
- `OCRJob` — extract text from PDF/scanned image
- `AIAnalysisJob` — summarize via OpenAI API
- `EmbeddingJob` — generate + store vector in pgvector
- `RiskDetectionJob` — flag compliance issues
- `NotificationJob` — alert users when analysis completes

---

## 11. RBAC Structure

Using `spatie/laravel-permission`. Three default roles per organization:

| Role | Capabilities |
|---|---|
| `admin` | Full access: manage users, org settings, all documents, all compliance flags |
| `manager` | Review/approve documents, view reports, manage compliance flags. Cannot manage users. |
| `staff` | Upload documents, view own documents, view compliance flags. Read-only on others. |

Roles are scoped per organization (`teams` feature of spatie). Custom roles can be added per org for enterprise clients.

---

## 12. Security Considerations

- All API routes protected by Sanctum middleware
- CSRF protection via Sanctum's cookie mechanism
- `organization_id` scoping on every query — no cross-tenant data leakage
- File uploads: MIME validation, size limits, virus-scan hook ready
- S3 files only accessible via signed temporary URLs
- Audit log on all sensitive actions (immutable, append-only)
- Passwords hashed with bcrypt
- UUIDs prevent enumeration attacks
- Environment variables for all secrets (never hardcoded)
- HTTPS enforced via Nginx (production)

---

## 13. What Is NOT in Phase 1

The following are architecturally prepared (stubs, schemas, job classes) but not implemented:

- OCR text extraction
- AI document summarization
- pgvector semantic search
- Risk auto-detection
- Multi-org admin panel (super admin)
- Email notifications
- Billing / plan management
- Mobile app
- Third-party integrations

---

## 14. Legal Disclaimer (embedded in all user-facing surfaces)

> Jurivex AI does not provide legal advice and should not replace licensed legal professionals. All analysis is for informational purposes only.
