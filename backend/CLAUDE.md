# Backend — Claude Code Context

Laravel 11 modular monolith at `backend/`.

## Run Tests

```bash
docker compose exec laravel php artisan test
docker compose exec laravel php artisan test --filter=DocumentsApiTest
```

## Module Layout

```
app/Modules/Domain/
├── DTOs/           readonly value objects
├── Events/         Laravel events (Dispatchable + SerializesModels)
├── Http/Controllers|Requests|Resources/
├── Jobs/           queued jobs (ShouldQueue)
├── Listeners/      event listeners
├── Models/         Eloquent (HasUuids + SoftDeletes where applicable)
├── Providers/      bind interfaces, register event listeners in boot()
├── Repositories/Contracts/  interface + implementation
├── Routes/api.php
└── Services/       business logic — no Eloquent calls, only repository
```

## Key Patterns

- Services inject `IDocumentRepository`, not `DocumentRepository` directly
- `abort_if()` is replaced by domain exceptions (`DocumentNotFoundException`, etc.)
- `AuditLog::create()` writes audit trail — include `metadata` for context
- `DocumentStatusManager::transition()` enforces valid status transitions
- `PromptLoader::load()` for all AI prompt strings

## Testing Setup

```php
use RefreshDatabase;
$this->seed(RolesAndPermissionsSeeder::class);  // always
Storage::fake('s3');  // for document upload tests
Queue::fake();         // for job dispatch tests
```

## Invite System

- Superadmin creates invite codes via `POST /api/v1/superadmin/organizations/{id}/invitation-codes`
- Users register with codes via `POST /api/v1/auth/register` + `invitation_code` field
- Codes are single-use, optionally expiring, tied to org + role
- `InvitationCode::isValid()` checks used + expired state

## AI Pipeline Status

**Phase 2A complete** — real OCR extraction is wired.
**Phase 2B complete** — real AI analysis via Claude is wired.

- `OCRJob` dispatched from `ProcessDocumentJob`, runs on the `ocr` queue via Horizon
- `AIAnalysisJob` dispatched from `DispatchAIAnalysis` listener (on `OCRCompleted`), runs on `analysis` queue
- `OcrService` delegates to `PdfTextExtractor` (pdftotext/GhostScript) or `ImageTextExtractor` (Tesseract)
- `ClaudeClient` calls Claude Messages API, parses JSON, produces `AnalysisResult`
- Extracted text stored via `DocumentExtractionRepository` → `document_extractions` table
- Analysis stored via `DocumentAnalysisRepository` → `document_analyses` table
- Status flow: `pending → ocr_processing → ocr_completed → ai_processing → analyzed`
- Events: `OCRCompleted` → `LogOCRActivity` + `DispatchAIAnalysis`; `DocumentAnalysisCompleted` → `LogDocumentAnalysisActivity`

Phase 2C (automated compliance flags from analysis) is not yet implemented.
