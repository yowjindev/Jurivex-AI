# Architecture

## Overview

Jurivex AI is a **modular Laravel monolith** with a separate Next.js frontend. All modules share one database and one queue — there are no separate microservices.

## Backend Module Structure

Each module follows the same internal layout:

```
Modules/Domain/
├── DTOs/           — immutable request/response data objects
├── Events/         — Laravel events dispatched by this module
├── Http/
│   ├── Controllers/
│   ├── Requests/   — FormRequest validation
│   └── Resources/  — API response transformers
├── Jobs/           — queued background work
├── Listeners/      — handle events from other modules
├── Models/         — Eloquent models
├── Providers/      — ServiceProvider: binds interfaces, registers event listeners
├── Repositories/
│   ├── Contracts/  — repository interface (IDocumentRepository)
│   └── XRepository.php
├── Routes/
│   └── api.php
└── Services/       — business logic
```

## Request Flow

```
HTTP Request
    → Sanctum middleware (auth check)
    → FormRequest (validation)
    → Controller (thin — calls service)
    → Service (business logic)
        → Repository (database)
        → AuditLog::create() (audit trail)
        → Event::dispatch() (async side-effects)
        → Job::dispatch() (async processing)
    → Resource (response transformer)
    → JSON response
```

## Event System

Events decouple modules. Example: when a document is uploaded, `DocumentService` fires `DocumentUploaded`. Listeners in other modules (AI, Notifications) react without `DocumentService` knowing about them.

```
DocumentService::upload()
    → ProcessDocumentJob::dispatch()
    → DocumentUploaded::dispatch()
        ← LogDocumentUploadedActivity (Documents module — Phase 2: webhooks)

ProcessDocumentJob::handle()
    → transitions document: pending → ocr_processing
    → OCRJob::dispatch() (ocr queue)

OCRJob::handle()
    → OcrService::process() → PdfTextExtractor | ImageTextExtractor
    → DocumentExtractionRepository::upsert()
    → transitions document: ocr_processing → ocr_completed
    → OCRCompleted::dispatch()
        ← LogOCRActivity::handleCompleted (AI module — writes audit_logs)

OCRJob::failed()
    → transitions document: → failed
    → OCRFailed::dispatch()
        ← LogOCRActivity::handleFailed (AI module — writes audit_logs)
```

## Multi-Tenancy

All data is scoped to `organization_id`. Every database query in every repository filters by the authenticated user's `organization_id`. There is no shared data between organizations.

## Audit Log

`AuditLog` is append-only (`UPDATED_AT = null`). Every significant action writes a log entry:

| Action | Triggered by |
|--------|-------------|
| `user.registered` | AuthService::register() |
| `document.uploaded` | DocumentService::upload() |
| `document.deleted` | DocumentService::delete() |
| `flag.resolved` | ComplianceService::resolve() |

Each entry includes `metadata` (context-specific JSON) and optionally `old_values` / `new_values` for change tracking.

## Exception Handling

All domain exceptions extend `AppException`:

```
AppException (abstract, HTTP status code)
├── NotFoundException (404)
│   ├── DocumentNotFoundException
│   └── ComplianceFlagNotFoundException
├── ForbiddenException (403)
└── InvalidDocumentTransitionException (422)
```

The exception renderer in `bootstrap/app.php` converts all `AppException` subclasses to `{ success: false, message: "..." }` JSON responses.

## Queue Architecture

All background jobs use Laravel Horizon (Redis-backed):

| Job | Queue | Purpose |
|-----|-------|---------|
| `ProcessDocumentJob` | default | Orchestrate document analysis pipeline |
| `OCRJob` | ocr | Extract text from document (Phase 2A — implemented) |
| `AIAnalysisJob` (Phase 2B) | ai | Generate AI summary |
| `EmbeddingJob` (Phase 2B) | ai | Generate vector embeddings |
| `RiskDetectionJob` (Phase 2B) | ai | Detect compliance risks |
| `NotificationJob` (Phase 2B) | notifications | Send user notifications |
