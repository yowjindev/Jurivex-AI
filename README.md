<div align="center">

# Jurivex AI

**AI-Powered Legal & Compliance Intelligence Platform**

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Next.js](https://img.shields.io/badge/Next.js-16-000000?style=for-the-badge&logo=next.js&logoColor=white)](https://nextjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)

*Analyze legal documents, detect compliance risks, and surface actionable intelligence —*
*built for accounting firms, HR agencies, construction companies, logistics operators, and SMEs.*

</div>

---

> **Disclaimer:** Jurivex AI does not provide legal advice and should not replace licensed legal professionals. All analysis is for informational purposes only.

---

## What is Jurivex AI?

Legal and compliance work is expensive, slow, and buried in documents most people never fully read. Jurivex AI is an operations intelligence platform that changes that — turning dense contracts, policies, and regulatory filings into plain-English summaries, risk signals, and actionable dashboards.

It is not a legal practice tool. It is a **business intelligence layer** on top of your legal documents, designed for the teams that live with compliance every day but can't afford a lawyer in every meeting.

**Core capabilities:**

- **Document Intelligence** — Upload contracts, policies, and regulatory documents. Get plain-English summaries, extracted key clauses, and risk scores — without reading 80 pages.
- **Compliance Monitoring** — Automatically surface deadlines, obligations, and gaps across your document library. Get alerted before things slip.
- **Risk Detection** — AI flags high-risk clauses, missing protections, and regulatory exposure — categorized by severity so teams know where to focus.
- **Executive Dashboards** — Compliance status at a glance. Designed for people who need answers, not documents.
- **Audit Trail** — Every action logged, immutable, and traceable. Built for regulated industries from day one.

---

## Who It's For

Jurivex AI is designed for compliance-heavy businesses that operate without a full legal team on retainer:

| Industry | Pain Point |
|---|---|
| Accounting Firms | Client contracts, regulatory filings, engagement letters |
| HR Agencies | Employment contracts, labor compliance, policy reviews |
| Mining & Construction | Safety regulations, permits, subcontractor agreements |
| Logistics | Carrier agreements, customs compliance, cross-border regulations |
| SMEs | Vendor contracts, NDAs, lease agreements, regulatory requirements |

---

## Tech Stack

### Backend
| | |
|---|---|
| Framework | Laravel 13 (PHP 8.4) |
| Authentication | Laravel Sanctum — SPA mode, httpOnly cookies |
| Architecture | Domain-driven modules via nwidart/laravel-modules |
| Permissions | spatie/laravel-permission — role-based per organization |
| Queue & Jobs | Laravel Horizon on Redis |
| Database | PostgreSQL 16 with pgvector for semantic search |
| File Storage | AWS S3 (production) · MinIO (local) |

### Frontend
| | |
|---|---|
| Framework | Next.js 16 — App Router |
| Language | TypeScript (strict mode) |
| UI | TailwindCSS + shadcn/ui |
| State | Zustand · TanStack Query |

### Infrastructure
| | |
|---|---|
| Reverse Proxy | Nginx |
| Containerization | Docker Compose |
| Vector Search | pgvector (PostgreSQL extension) |
| Queue Backend | Redis |

---

## Architecture

Jurivex AI is a monorepo with a clean separation between the API and the frontend. The backend follows a domain-driven module structure — each feature domain (Auth, Organizations, Documents, Compliance, AI) lives as a self-contained module with its own controllers, services, repositories, jobs, and migrations.

```
backend/app/Modules/
├── Auth/           — Registration, login, session management
├── Organizations/  — Multi-tenant org management, members, invitations
├── Documents/      — Upload pipeline, S3 storage, processing queue
├── Compliance/     — Flags, deadlines, risk levels, resolution tracking
└── AI/             — OCR, summarization, embeddings, risk detection
```

All API responses follow a consistent envelope:

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "meta": {}
}
```

---

## Multi-Tenancy & Roles

Every piece of data is scoped to an organization — no cross-tenant data access is architecturally possible. Three roles ship by default, extensible per organization for enterprise clients:

| Role | Access |
|---|---|
| `admin` | Full access — users, settings, all documents, all compliance flags |
| `manager` | Document review, compliance management — no user administration |
| `staff` | Upload documents, view own work, read-only on compliance |

---

## Roadmap

### Phase 1 — MVP Foundation *(in progress)*
- [x] Infrastructure — Dockerized monorepo, Nginx, all services
- [ ] Auth — Registration, login, Sanctum SPA session
- [ ] Organizations — Multi-tenancy, member management, invitations
- [ ] Documents — S3 upload pipeline, queue-based processing
- [ ] Compliance — Flag tracking, severity levels, deadlines
- [ ] Frontend — Auth flow, dashboard shell, document management UI

### Phase 2 — AI Layer *(planned)*
- [ ] OCR extraction from PDF and scanned documents
- [ ] AI document summarization and key clause extraction
- [ ] Semantic search via pgvector embeddings
- [ ] Automated risk detection and compliance flagging
- [ ] Notification system for deadlines and alerts

### Phase 3 — Scale *(planned)*
- [ ] Super admin — multi-organization management panel
- [ ] Billing and subscription management
- [ ] Third-party integrations
- [ ] Mobile application

---

## Security

Security is not an afterthought. Key design decisions:

- Organization-scoped queries everywhere — cross-tenant data leakage is architecturally prevented
- Files served exclusively via signed temporary URLs — raw S3 paths are never exposed
- Immutable append-only audit log on all sensitive actions
- CSRF protection via Sanctum's cookie mechanism
- UUID primary keys throughout — no sequential ID enumeration
- HTTPS enforced at the proxy layer in production

---

## License

This project is open source. You are welcome to study the code, learn from it, and build on the ideas. Please do not use it as a direct template to launch a competing commercial product.

---

<div align="center">
<sub>Built with Laravel, Next.js, and a lot of coffee.</sub>
</div>
