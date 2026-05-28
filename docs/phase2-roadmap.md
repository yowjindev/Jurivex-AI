# Jurivex AI — Phase 2 Intelligence Layer Roadmap

> **Status:** Planning document. Phase 2A (OCR) complete. This document governs the remaining Phase 2 build-out.
> **Last Updated:** 2026-05-28
> **Author:** Architecture planning session

---

## Executive Summary

Phase 2A delivered real OCR extraction — documents now have text. The question is what to do with it. Phase 2 continues by building the intelligence layer incrementally: structured LLM analysis, automated compliance detection, vector embeddings, semantic search, and AI-assisted document chat. Each subphase is a self-contained, shippable milestone that produces observable value and leaves the platform in a stable state.

The sequencing is deliberate. Every phase solves a specific problem, leaves clean integration points for the next, and avoids building infrastructure before it's needed. The temptation in AI systems is to build the "full architecture" upfront — embeddings, vector store, RAG, agents — before the core analysis layer even works. This roadmap rejects that. We build what creates value in the correct order, test it, and layer upward.

Jurivex AI is enterprise legal operations intelligence. Every decision in this roadmap should be evaluated against that standard, not against what's fashionable in the AI space.

---

## Current Architecture State (Post-Phase 2A)

### What Exists

```
Documents Module
├── Document (model) — status: pending|ocr_processing|ocr_completed|analyzed|failed
├── DocumentAnalysis (model) — summary, key_points, risk_score, ai_model
├── DocumentExtraction (model) — extracted_text, page_count, word_count, confidence
├── DocumentService — upload, list, show, delete, downloadUrl
├── DocumentStatusManager — enforces valid transitions
└── ProcessDocumentJob → OCRJob (ocr queue)

AI Module
├── OCR/ — PdfTextExtractor, ImageTextExtractor, OcrService (IMPLEMENTED)
├── Analysis/Jobs/AIAnalysisJob — STUB
├── Embeddings/Jobs/EmbeddingJob — STUB
├── Risk/Jobs/RiskDetectionJob — STUB
├── Notifications/Jobs/NotificationJob — STUB
├── Pipelines/DocumentAnalysisPipeline — STUB
├── Prompts/ — PromptLoader + 3 templates (document.analyze, extract_risks, summarize)
└── Providers/AIServiceProvider — binds OCR services, logs OCR events

Compliance Module
├── ComplianceFlag (model) — document_id, type, severity, description, status, resolved_by
├── ComplianceService — list, resolve
└── ComplianceFlagGenerated (event) — stub listener

Queues (Horizon)
├── default — ProcessDocumentJob
└── ocr — OCRJob (timeout: 300s, tries: 3)

Events
├── DocumentUploaded → LogDocumentUploadedActivity
├── DocumentProcessingStarted → LogDocumentProcessingActivity (stub)
├── DocumentAnalysisCompleted → LogDocumentAnalysisActivity (stub)
├── OCRCompleted → LogOCRActivity::handleCompleted
└── OCRFailed → LogOCRActivity::handleFailed
```

### What's Missing

- No AI provider client wired (no API keys, no HTTP client, no service implementation)
- No token tracking or cost visibility
- `document_analyses` exists but `AIAnalysisJob.handle()` is empty
- No chunking, no embeddings, no vector store
- No semantic search
- No AI chat
- No PII or prompt injection defenses
- `AIServiceContract` defined but not implemented
- `DocumentAnalysisPipeline::dispatch()` commented out

---

## Status Machine Evolution

Each new phase adds states. The machine must remain a strict graph — no implicit transitions, no status resets without a new `ocr_processing` entry.

### Target State (Full Phase 2)

```
VALID_TRANSITIONS = {
  pending           → [ocr_processing]
  ocr_processing    → [ocr_completed, failed]
  ocr_completed     → [ai_processing]              ← Phase 2B adds this
  ai_processing     → [analyzed, failed]           ← Phase 2B adds this
  analyzed          → []
  failed            → [ocr_processing]             ← retry always re-enters OCR
}
```

New constants on `Document`:
- `STATUS_AI_PROCESSING = 'ai_processing'`

This is added in Phase 2B Task 1 before any analysis job runs. Frontend badge added at the same time.

---

## Queue Strategy Evolution

| Phase | New Queue | Supervisor Name | Jobs | Timeout | Tries | Processes |
|-------|-----------|-----------------|------|---------|-------|-----------|
| 2A (done) | `ocr` | supervisor-ocr | OCRJob | 300s | 3 | 1–3 |
| 2B | `analysis` | supervisor-analysis | AIAnalysisJob | 120s | 2 | 1–2 |
| 2C | shares `analysis` | — | RiskDetectionJob | 60s | 2 | — |
| 2D | `notifications` | supervisor-notifications | NotificationJob | 30s | 3 | 1–3 |
| 2E | `embeddings` | supervisor-embeddings | EmbeddingJob | 60s | 3 | 1–4 |
| 2F–2G | — | — | Search/chat runs synchronously in request-response | — | — | — |

**Rationale:** Analysis is expensive and slow — smaller pool, shorter retry count to fail fast. Embeddings are cheap but high-volume — larger pool, more retries for transient rate limits. Notifications are fast — maximum availability.

---

## Dependency Graph

```
Phase 2A (done)
    ↓
Phase 2B — AI Analysis       ← requires: OCR output (extracted_text)
    ↓
Phase 2C — Compliance Intel  ← requires: analysis output (summary, key_points)
    ↓         ↘
Phase 2D      Phase 2E — Embeddings  ← requires: extracted_text + chunking strategy
(Observability,
 parallel to 2B-2C)
    ↓                ↓
Phase 2F — Semantic Search   ← requires: embeddings (2E)
    ↓
Phase 2G — AI Chat (RAG)     ← requires: search (2F) + analysis (2B)
    ↓
Phase 2H — Enterprise Safeguards  ← layers on top of everything
    ↓
Phase 2I — Provider Abstraction   ← refactor after full implementation proven
```

**Critical path:** 2B → 2C → 2E → 2F → 2G

**Parallelizable:** 2D can begin at the same time as 2B (observability tables + middleware baked in from the start).

---

---

# Phase 2B — AI Analysis Layer

## Objectives

Replace the `AIAnalysisJob` stub with a real LLM call. Produce structured legal analysis from OCR-extracted text and persist it to `document_analyses`. Wire the `OCRCompleted` → `AIAnalysisJob` chain so documents flow automatically from OCR completion to AI analysis.

This is the single highest-leverage phase. Everything else (compliance intelligence, embeddings, chat) depends on having structured analysis.

## Architecture Impact

- Add `STATUS_AI_PROCESSING` to `DocumentStatusManager` valid transitions
- Implement `AIProviderContract` interface (Phase 2I will abstract further — for now, one concrete Claude implementation)
- Wire `OCRCompleted` listener to dispatch `AIAnalysisJob` (or use `DocumentAnalysisPipeline`)
- `AIAnalysisJob` must handle: text truncation, prompt construction, API call, JSON parsing, persistence, status transition, event dispatch
- Extend `document_analyses` table with `confidence`, `raw_response` columns

## Backend Tasks

1. **New status: `ai_processing`**
   - Add `Document::STATUS_AI_PROCESSING = 'ai_processing'`
   - Update `DocumentStatusManager::VALID_TRANSITIONS`: `ocr_completed → [ai_processing]`, `ai_processing → [analyzed, failed]`
   - Update `DocumentStatusManagerTest`

2. **AI provider client**
   - Install `anthropic-ai/sdk` or use Guzzle with Claude's Messages API
   - New service: `backend/app/Modules/AI/Services/ClaudeClient.php`
   - Constructor takes `string $apiKey, string $model`
   - Method: `complete(string $prompt, int $maxTokens): string`
   - Throws `AIProviderException extends AppException` on failure
   - Bound in `AIServiceProvider::register()`
   - Config: `config/ai.php` with `driver`, `claude.api_key`, `claude.model`, `claude.max_tokens`

3. **Text truncation utility**
   - `backend/app/Modules/AI/Utilities/TextTruncator.php`
   - `truncate(string $text, int $maxTokens): string` — estimates tokens (rough: 4 chars/token), truncates at sentence boundary
   - Preserves beginning and end of document (first and last N tokens) for legal documents — beginning often has parties/dates, end has signatures

4. **AIAnalysisJob — full implementation**
   - Located: `backend/app/Modules/AI/Analysis/Jobs/AIAnalysisJob.php`
   - `public $tries = 2; public $timeout = 120;` on `analysis` queue
   - Constructor: `public readonly Document $document`
   - `handle()`:
     1. Resolve `ClaudeClient`, `DocumentStatusManager`, `IDocumentAnalysisRepository` from container
     2. Load extraction: `$this->document->extraction` (eager loaded or fetched)
     3. Truncate text if > 80K chars
     4. Load prompt: `PromptLoader::load('document.analyze', ['content' => $truncatedText, 'filename' => $document->original_filename])`
     5. Transition status: `ai_processing`
     6. Call Claude API, parse JSON response
     7. Persist to `document_analyses` via repository `upsert()`
     8. Transition status: `analyzed`
     9. Dispatch `DocumentAnalysisCompleted`
   - `failed()`: direct Eloquent `STATUS_FAILED`, dispatch `DocumentAnalysisFailed` event

5. **Update `document.analyze` prompt template**
   - Current template is a stub — needs real legal analysis prompt
   - Must produce structured JSON output: `{ "summary": "...", "key_points": [...], "parties": [...], "effective_date": "...", "governing_law": "...", "risk_score": 0.0-1.0, "confidence": 0.0-1.0 }`
   - Use Claude's JSON mode or include explicit JSON schema in prompt

6. **IDocumentAnalysisRepository + DocumentAnalysisRepository**
   - `upsert(string $documentId, AnalysisResult $result): DocumentAnalysis`
   - `updateOrCreate` pattern (matches OCR repository — retry-safe)
   - Located: `backend/app/Modules/AI/Analysis/Repositories/`

7. **AnalysisResult DTO**
   - `readonly class`: `summary`, `keyPoints` (array), `parties` (array), `effectiveDate` (?string), `governingLaw` (?string), `riskScore` (float), `confidence` (float), `model` (string), `rawResponse` (string)

8. **Wire OCRCompleted → AIAnalysisJob**
   - Option A: Register listener in `AIServiceProvider::boot()`: `OCRCompleted → DispatchAIAnalysis::handle()` which dispatches `AIAnalysisJob`
   - Option B: Implement `DocumentAnalysisPipeline::dispatch()` — chains `OCRJob → AIAnalysisJob` using Laravel's job chaining
   - **Recommended: Option A (listener-driven)** — more observable, easier to test, allows future fan-out to multiple jobs without changing `OCRJob`

9. **New events**
   - `DocumentAnalysisCompleted` (already exists as stub — implement payload)
   - `DocumentAnalysisFailed` (new) — dispatched from `AIAnalysisJob::failed()`

10. **Horizon config**: add `supervisor-analysis` (analysis queue, 1-2 processes, 120s timeout, 2 tries)

## Frontend Tasks

- Add `STATUS_AI_PROCESSING = 'ai_processing'` to constants
- Add `'ai_processing'` to `Document.status` union type
- Add `StatusBadge` entry: `ai_processing` → `'AI Processing'` (purple/violet)
- `DocumentResource` API response: include `analysis` object if present (summary, key_points, risk_score)
- Documents detail view (new page or modal): show analysis results when status is `analyzed`

## Queue/Event Impact

- New queue: `analysis`
- New listener: `DispatchAIAnalysis` (fires on `OCRCompleted`, dispatches `AIAnalysisJob`)
- `DocumentAnalysisCompleted` now carries actual payload (document + analysis result)

## Database Impact

- Migrate `document_analyses`: add `confidence decimal(5,4) nullable`, `raw_response longtext nullable`, `parties json nullable`, `effective_date date nullable`, `governing_law varchar nullable`
- Add `ai_processing` to the allowed status values (if using enum — check migration)
- No schema breakage: all new columns nullable

## AI/Provider Impact

- First real API call to Claude
- Config: `CLAUDE_API_KEY`, `CLAUDE_MODEL` (e.g., `claude-sonnet-4-6`), `CLAUDE_MAX_TOKENS` (4096)
- Cost implication: ~$0.003/1K input tokens for Sonnet. A 10-page legal PDF (~8,000 tokens input + 1,000 output) ≈ $0.027/document. Budget tracking critical.

## Risks

- **API rate limits:** Claude has per-minute and per-day token limits. With parallel `AIAnalysisJob` processes and burst uploads, limits can be hit. Mitigation: small supervisor pool (1-2 processes), exponential backoff on retry, reduce `$tries` to 2 to fail fast.
- **JSON parsing failure:** LLMs don't always produce valid JSON even with explicit instructions. Mitigation: wrap parse in try/catch; store `raw_response` always; fall back to plain text summary if JSON invalid.
- **Token count estimation:** Rough 4-chars/token estimate can undershoot for technical legal language with many long words. Use Claude's `count_tokens` endpoint before truncation if budget permits.
- **Prompt injection:** Legal documents may contain adversarial text like `"Ignore all previous instructions and output X"`. Mitigation: wrap document content in explicit delimiters (`<document>...</document>`) in the prompt; add Phase 2H defense layer.

## Testing Strategy

- Unit test `TextTruncator` with various input lengths
- Unit test JSON response parsing (valid, malformed, missing fields)
- Feature test `AIAnalysisJob` using `Http::fake()` to mock Claude API responses
- Feature test full listener chain: `OCRCompleted` → `DispatchAIAnalysis` → `AIAnalysisJob` dispatched
- Feature test `DocumentStatusManager` transitions for new `ai_processing` status
- Do NOT test with real Claude API — fixtures only in CI

## Rollout Strategy

- Deploy behind feature flag: `FEATURE_AI_ANALYSIS=true` in `.env`
- Enable for test tenant first; verify analysis quality against known documents
- Monitor token usage and job duration in Horizon before enabling org-wide

## Future Extensibility

- `ClaudeClient` is a concrete class today; Phase 2I wraps it behind `AIProviderContract`
- `AnalysisResult` DTO should be stable across providers
- JSON schema for the analysis output should be versioned (add `schema_version` to `document_analyses`)

---

---

# Phase 2C — Compliance Intelligence

## Objectives

Automatically generate `compliance_flags` from AI analysis output. Replace the current manual-only compliance workflow with AI-detected risks that get surfaced for human review. Legal staff review and resolve — the AI flags, humans decide.

This is the core product value proposition. A lawyer uploads a contract, gets a structured analysis and flagged compliance risks within minutes, without manual review to initiate it.

## Architecture Impact

- `RiskDetectionJob` reads from `document_analyses` (Phase 2B output)
- Creates `ComplianceFlag` records with `ai_generated = true`, confidence score, and explanation
- Existing manual compliance workflow unchanged — flags from both sources appear in the same list
- New event: `ComplianceFlagGenerated` (already exists as stub — implement with payload)

## Backend Tasks

1. **Extend `compliance_flags` table**
   - Add: `ai_generated boolean default false`, `confidence decimal(5,4) nullable`, `source enum('manual', 'ai') default 'manual'`, `ai_model varchar nullable`, `explanation text nullable`
   - All nullable/defaulted — no breakage to existing manual flags

2. **Update prompt: `document.extract_risks`**
   - Must produce JSON array: `[{ "type": "...", "severity": "critical|high|medium|low", "title": "...", "description": "...", "explanation": "...", "confidence": 0.0-1.0, "clause_reference": "..." }]`
   - `type` should be an enum from a controlled vocabulary (e.g., `"missing_clause"`, `"unfavorable_term"`, `"jurisdiction_risk"`, `"liability_cap"`, `"ip_ownership"`, `"termination_clause"`)

3. **RiskDetectionJob — full implementation**
   - Located: `backend/app/Modules/AI/Risk/Jobs/RiskDetectionJob.php`
   - Dispatched by `DispatchRiskDetection` listener on `DocumentAnalysisCompleted`
   - Constructor: `public readonly Document $document`
   - `handle()`:
     1. Load analysis from `document_analyses`
     2. Construct risk detection prompt using analysis summary + key_points + raw extracted text
     3. Call Claude API (separate call — focused prompt = better results)
     4. Parse JSON array of risks
     5. For each risk: create `ComplianceFlag` via `IComplianceFlagRepository::createFromAI()`
     6. Dispatch `ComplianceFlagGenerated` for each flag
   - Shares `analysis` queue (same supervisor)

4. **IComplianceFlagRepository: add `createFromAI()` method**
   - `createFromAI(string $documentId, string $orgId, RiskFlagResult $flag): ComplianceFlag`
   - `RiskFlagResult` DTO: `type`, `severity`, `title`, `description`, `explanation`, `confidence`, `clauseReference`, `aiModel`

5. **Controlled vocabulary for compliance types**
   - New: `backend/app/Modules/AI/Risk/Enums/ComplianceFlagType.php` (PHP 8.1 backed enum)
   - Enforced in `createFromAI()` — unknown types fall back to `'other'`

6. **ComplianceFlag model: update `$fillable` and `$casts`**
   - Add new fields to fillable
   - Cast `ai_generated` to `boolean`, `confidence` to `decimal:4`

## Frontend Tasks

- Compliance flags list: show `AI` badge on AI-generated flags (vs manual)
- Show `confidence` score next to AI flags (e.g., `87% confident`)
- Show `explanation` in expanded flag view
- Filter: "AI detected" / "Manual" toggle
- Document detail: "AI Analysis" panel showing summary + flagged risks

## Queue/Event Impact

- `DocumentAnalysisCompleted` → `DispatchRiskDetection` listener → `RiskDetectionJob` on `analysis` queue
- `ComplianceFlagGenerated` now carries flag payload (type, severity, document_id, org_id)

## Database Impact

- Migration extending `compliance_flags` (all nullable/defaulted — zero downtime)
- High volume: large orgs may generate 10–50 flags per document. Index on `document_id, status, severity`

## AI/Provider Impact

- Second Claude call per document (~$0.01–0.02 per document for risk extraction)
- Consider combining analysis + risk extraction into a single prompt in Phase 2I to reduce API calls

## Risks

- **False positives:** AI may flag non-issues as compliance risks. Mitigation: confidence score allows UI to de-emphasize low-confidence flags. Human review always required before acting.
- **Hallucinated clause references:** Claude may reference a clause that doesn't exist. Mitigation: `clause_reference` is informational only; UI marks it as "AI-referenced, verify manually."
- **Type vocabulary drift:** If the frontend hardcodes flag types for display, adding new types later breaks UI. Mitigation: keep type as a string, handle `'other'` gracefully in the frontend.

## Testing Strategy

- Feature test `RiskDetectionJob` with `Http::fake()` Claude responses
- Test that flags are created with correct `source = 'ai'` and `ai_generated = true`
- Test that `ComplianceFlagGenerated` is dispatched for each flag
- Test controlled vocabulary enforcement (unknown types → `'other'`)
- Test with zero-risk document (empty JSON array response) → no flags created, no exception

## Rollout Strategy

- Staff review AI flags vs. known documents before enabling org-wide
- Monitor false positive rate — add `feedback` mechanism (thumbs up/down on AI flags) for future model improvement

## Future Extensibility

- `RiskFlagResult` DTO is provider-agnostic
- Controlled vocabulary can be extended without DB migrations
- Feedback mechanism (Phase 2H) feeds into prompt improvement

---

---

# Phase 2D — AI Observability & Cost Controls

## Objectives

Every Claude API call costs money and takes time. Without visibility, costs are invisible until the bill arrives. This phase bakes in token tracking, cost attribution, latency monitoring, and per-organization token budgets from the beginning — while the API call volume is still manageable.

This phase is not optional. It should be delivered alongside Phase 2B if possible, or immediately after. Skipping it is how AI projects develop surprise $50K/month API bills.

## Architecture Impact

- New `ai_requests` table — append-only log of every API call (model, tokens, latency, cost, document, org)
- New `ai_token_budgets` table — per-organization monthly token limits
- `ClaudeClient` extended to write an `AiRequest` record after every call
- Middleware/guard to check token budget before dispatching AI jobs
- Superadmin dashboard for cost visibility

## Backend Tasks

1. **`ai_requests` migration**
   ```
   id UUID PK
   organization_id UUID FK (nullable — system calls have no org)
   document_id UUID FK nullable
   job_type varchar (e.g., 'ai_analysis', 'risk_detection', 'embedding')
   model varchar
   prompt_tokens int
   completion_tokens int
   total_tokens int (generated column or computed in app)
   latency_ms int
   cost_usd decimal(10,6)
   status enum('success', 'failure')
   error_message text nullable
   created_at timestamp (no updated_at — append-only)
   ```

2. **`ai_token_budgets` migration**
   ```
   id UUID PK
   organization_id UUID FK unique
   monthly_token_limit int (default: 1,000,000)
   current_month_tokens int (default: 0)
   alert_threshold_pct int (default: 80)
   budget_period_start date (first of current month)
   created_at, updated_at
   ```

3. **`AiRequest` model** — append-only (no `updated_at`), soft-delete forbidden
4. **`AiTokenBudget` model** — with `isExhausted()`, `isNearLimit()` helpers

5. **`ClaudeClient` extended**
   - After every API call: create `AiRequest` record (tokens from response headers/body, latency measured in client)
   - Token cost calculation: `ClaudePricingTable` class with per-model input/output rates
   - Atomic increment: `AiTokenBudget::where(...)->increment('current_month_tokens', $totalTokens)`

6. **Budget guard**
   - `AIBudgetExceededException extends AppException` (429)
   - `TokenBudgetService::checkBudget(string $orgId): void` — throws if budget exhausted
   - Called at the start of `AIAnalysisJob::handle()` and `RiskDetectionJob::handle()`

7. **Monthly reset**
   - Laravel scheduled command: `php artisan ai:reset-monthly-budgets` (runs on 1st of each month)
   - Resets `current_month_tokens = 0`, updates `budget_period_start`

8. **Superadmin API endpoints**
   - `GET /api/v1/superadmin/ai-usage` — aggregate token usage by org, by model, by month
   - `GET /api/v1/superadmin/ai-usage/{orgId}` — per-org breakdown
   - `PUT /api/v1/superadmin/organizations/{orgId}/ai-budget` — set monthly limit

## Frontend Tasks

- Superadmin: AI Cost Dashboard (usage by org, by model, trend chart)
- Org admin: basic usage indicator ("You've used 32% of your monthly AI quota")
- Alert UI when near limit (80%+) or exhausted (locked out of AI features)

## Queue/Event Impact

- No new queues
- Consider `AiRequestFailed` event for alerting on repeated failures from same org

## Database Impact

- Two new tables — no changes to existing
- `ai_requests` will grow rapidly — plan retention policy (e.g., keep 90 days, archive to cold storage)
- Index on `(organization_id, created_at)` for reporting queries

## AI/Provider Impact

- Makes cost visible per provider, per model, per org
- Foundation for Phase 2I provider switching (each provider has different pricing)

## Risks

- **Clock skew in latency measurement:** Measure in the client before/after HTTP call, not from queue dispatch time
- **Atomic counter race conditions:** `increment()` is atomic in PostgreSQL — safe for concurrent jobs
- **Budget reset timing:** If two jobs check budget simultaneously on the reset day, one might see the old (exhausted) state. Mitigation: reset runs at midnight UTC; add a brief grace window.

## Testing Strategy

- Test `ClaudeClient` writes `AiRequest` record on success and failure
- Test `TokenBudgetService::checkBudget()` throws when exhausted
- Test monthly reset command resets `current_month_tokens`
- Test superadmin endpoints return correct aggregates

## Rollout Strategy

- Deploy tables before Phase 2B goes to production
- Start with `monthly_token_limit = 10,000,000` (permissive) for early testing
- Tighten limits once real usage patterns are understood

## Future Extensibility

- `AiRequest` table becomes the foundation for Phase 2I provider analytics
- Can add per-feature (analysis vs. embeddings vs. chat) budgets later
- `alert_threshold_pct` enables future email/webhook alerts

---

---

# Phase 2E — Chunking & Embeddings

## Objectives

Split extracted document text into semantically coherent chunks and generate vector embeddings for each chunk. Store embeddings in PostgreSQL via pgvector. This enables semantic search (Phase 2F) and RAG-based chat (Phase 2G).

This is infrastructure — no immediate user-visible feature, but it unlocks everything that follows. Do not combine with Phase 2F; validate chunking quality independently first.

## Architecture Impact

- pgvector PostgreSQL extension required
- New `document_chunks` table with `embedding vector(1024)` column (1024 = Claude's embedding dimension; 1536 for OpenAI — must commit to a model family)
- New `ChunkingService` responsible for splitting strategy
- New `EmbeddingJob` — processes one document per dispatch
- Triggered by `DocumentAnalysisCompleted` (Phase 2B) — embed after analysis, not before

## Backend Tasks

1. **pgvector setup**
   - Add `pgvector` extension to PostgreSQL Docker image
   - Migration: `CREATE EXTENSION IF NOT EXISTS vector;`
   - Composer package: `pgvector/pgvector` for PHP

2. **`document_chunks` migration**
   ```
   id UUID PK
   document_id UUID FK cascade
   organization_id UUID FK (denormalized for tenant-safe queries)
   chunk_index int (0-based sequence)
   text text
   token_count int
   embedding vector(1024) nullable
   embedded_at timestamp nullable
   created_at timestamp
   ```
   Unique index on `(document_id, chunk_index)`.
   Index: `embedding vector_cosine_ops` using `ivfflat` — added after minimum 1000 rows.

3. **`ChunkingService`**
   - Located: `backend/app/Modules/AI/Embeddings/Services/ChunkingService.php`
   - `chunk(string $text, string $mimeType): array` returns `ChunkResult[]`
   - `ChunkResult`: `chunkIndex`, `text`, `tokenCount`
   - Strategy: recursive sentence-aware splitting, ~512 tokens per chunk, 50-token overlap between chunks
   - Overlap preserves context at chunk boundaries — critical for legal documents where a clause may span a paragraph boundary
   - Legal document-aware: respect page breaks (`\n\n`) over mid-sentence splits

4. **Claude embedding client**
   - Extend `ClaudeClient` with `embed(string $text): float[]` — calls Claude's embedding API
   - Falls back to `null` on failure (chunk saved without embedding, can be re-embedded)
   - Alternative: Use OpenAI's `text-embedding-3-small` for embeddings (cheaper, well-benchmarked) — abstracted behind `EmbeddingProviderContract`

5. **`EmbeddingJob` — full implementation**
   - Queue: `embeddings`, `$tries = 3`, `$timeout = 60`
   - Constructor: `public readonly Document $document`
   - `handle()`:
     1. Load `$document->extraction->extracted_text`
     2. `ChunkingService::chunk(text)` → array of `ChunkResult`
     3. For each chunk: call embedding API, save `DocumentChunk` record
     4. Use `DocumentChunk::upsert()` on `(document_id, chunk_index)` — retry-safe
     5. Dispatch `DocumentEmbedded` event
   - `failed()`: dispatch `DocumentEmbeddingFailed` event

6. **`IDocumentChunkRepository` + `DocumentChunkRepository`**
   - `upsertChunks(string $documentId, string $orgId, ChunkResult[] $chunks, float[][] $embeddings): void`
   - `findSimilar(string $orgId, float[] $queryEmbedding, int $limit, float $threshold): DocumentChunk[]`

7. **Wire `DocumentAnalysisCompleted` → `EmbeddingJob`**
   - New listener: `DispatchEmbedding`
   - Registered in `AIServiceProvider::boot()`

8. **`DocumentEmbedded` event**, `DocumentEmbeddingFailed` event

9. **Horizon config**: add `supervisor-embeddings`

## Frontend Tasks

- No immediate user-visible change
- Document detail: show "Indexed for search" indicator when chunks exist

## Database Impact

- `document_chunks` table — potentially large (a 100-page PDF = 400+ chunks per document)
- `ivfflat` index on `embedding` column — requires `lists` parameter tuned to data size
- Plan: create basic HNSW or ivfflat index at migration time; re-tune after 10K+ documents
- Retention: chunks should be deleted when document is deleted (cascade)

## AI/Provider Impact

- Embedding model choice is a **long-term commitment** — changing models means re-embedding all documents
- **Recommendation:** Use a fixed, stable model (OpenAI `text-embedding-3-small` or Claude's embedding API). Document the model version in `document_chunks` via an `embedding_model varchar` column. This allows future re-embedding without schema changes.
- Add `embedding_model` to `document_chunks` table — this is critical for migrations when embedding models are upgraded

## Risks

- **Model lock-in:** Embedding dimensions differ by model. Storing raw vectors tied to one model means re-embedding everything if switching. Mitigation: `embedding_model` column; accept that re-embedding is a maintenance operation.
- **ivfflat index cold start:** `ivfflat` requires a minimum number of rows before the index is useful. Mitigation: add index via scheduled job after threshold reached, not at migration time.
- **Chunking quality:** Poor chunking breaks semantic retrieval. Legal documents are structurally complex — test with real contracts before locking in the strategy.
- **Embedding API cost:** 1000 chunks × $0.00002/1K tokens = cheap. Volume at scale could grow.

## Testing Strategy

- Unit test `ChunkingService` with real-world legal text samples — verify chunk boundaries, overlap, token counts
- Feature test `EmbeddingJob` with `Http::fake()` for embedding API
- Test `findSimilar()` query against seeded `document_chunks` (pgvector must be installed in test DB)
- Test re-embedding (upsert idempotency) — run job twice, verify no duplicate chunks

## Rollout Strategy

- Requires pgvector on the PostgreSQL server — coordinate Dockerfile + docker-compose change
- Roll out to existing analyzed documents via: `php artisan ai:backfill-embeddings` (processes `analyzed` documents in batches)

## Future Extensibility

- `embedding_model` column enables multi-model index (different models for different use cases)
- Chunking strategy can be swapped per document type (contracts vs. briefs vs. regulatory filings)
- Chunk metadata (section headers, page numbers) can be added for citation precision

---

---

# Phase 2F — Semantic Search

## Objectives

Enable tenant-safe semantic search across an organization's document corpus. A user searches for "governing law clause New York" and gets the most relevant document chunks — not keyword matches, but conceptual matches.

## Architecture Impact

- New search service: `SemanticSearchService`
- Uses `document_chunks.embedding` + pgvector's `<=>` cosine distance operator
- All queries scoped by `organization_id` — tenant isolation enforced at query level
- API endpoint: `GET /api/v1/documents/search?q=...`
- No new queues — search runs synchronously in request-response cycle

## Backend Tasks

1. **`SemanticSearchService`**
   - `search(string $orgId, string $query, int $limit = 10): SearchResultCollection`
   - Steps: embed the query text → run vector similarity query → rank results → return
   - Query: `SELECT ... FROM document_chunks WHERE organization_id = ? ORDER BY embedding <=> ?::vector LIMIT ?`
   - Filter by similarity threshold (e.g., cosine distance < 0.3 — configurable per org)

2. **`ISearchRepository` + `SearchRepository`**
   - `findSimilarChunks(string $orgId, float[] $queryEmbedding, int $limit, float $threshold): DocumentChunk[]`
   - Loads related `document` (title, status, mime_type) with eager loading

3. **`SearchResult` DTO**
   - `chunkId`, `documentId`, `documentTitle`, `chunkText`, `score` (similarity 0-1), `chunkIndex`

4. **Hybrid search (Phase 2F.2 — optional)**
   - Combine vector similarity with PostgreSQL full-text search (`tsvector`)
   - Weighted re-ranking: `0.7 * semantic_score + 0.3 * fulltext_score`
   - Useful for proper nouns and exact clause text that semantic search may miss

5. **`DocumentSearchController`**
   - `GET /api/v1/documents/search?q={query}&limit={n}`
   - Rate-limited (embedding API call per search)
   - RBAC: all org members can search their org's documents
   - Response: array of `SearchResult` with document metadata

6. **Query embedding caching**
   - Cache frequently repeated queries (e.g., `Cache::remember('search:' . md5($query), 300, fn() => $this->embed($query))`)
   - Reduces embedding API calls for common searches

## Frontend Tasks

- Global search bar in dashboard header
- Search results page: list of matching chunks with document title, excerpt, similarity score
- Click result → navigate to document detail
- Debounced input (500ms) — embedding call is not free

## Queue/Event Impact

- No new queues
- Search is synchronous — latency target < 500ms for user experience

## Database Impact

- Read-heavy: `ivfflat` or `hnsw` index on `embedding` column is the performance lever
- HNSW index (available in newer pgvector) is faster at query time, slower to build — prefer for read-heavy workloads
- Add: `to_tsvector('english', text)` generated column for hybrid search

## AI/Provider Impact

- Query embedding uses the same embedding model as document chunks — must match
- Cache query embeddings to reduce API cost for repeated searches

## Risks

- **Relevance quality:** Vector search returns "similar" not "relevant." Legal semantic similarity may need domain-specific fine-tuning or re-ranking. Mitigation: allow users to flag poor results (feedback loop for Phase 2I).
- **Performance:** `ivfflat` approximate search trades accuracy for speed. At 1M+ chunks, approximate recall may be 85-90%. For legal use, exact recall may be required — `hnsw` is more accurate but memory-intensive.
- **Tenant isolation:** A misconfigured query that omits `organization_id` would expose all orgs' documents. This is critical. Mitigation: `organization_id` is always a required parameter to `ISearchRepository` — never optional.

## Testing Strategy

- Feature test search endpoint with seeded `document_chunks` (pgvector required in test environment)
- Test that search results never cross `organization_id` boundaries (tenant isolation critical path test)
- Test rate limiting on search endpoint
- Test empty results (no chunks above threshold) → empty array, 200 OK, no error

## Rollout Strategy

- Feature flag: `FEATURE_SEMANTIC_SEARCH=true`
- Initially limit to organizations with > 5 processed documents
- Monitor embedding API latency for search queries

## Future Extensibility

- Filter by document category, date range, status
- Metadata filtering (parties, governing law, document type)
- Re-ranking layer (cross-encoder model) for higher precision

---

---

# Phase 2G — AI Document Chat (RAG)

## Objectives

Allow users to ask questions about their documents and receive grounded, cited answers. "What are the termination clauses in the Smith contract?" gets a precise answer with the exact chunk it came from — not a hallucinated summary.

This is the most complex phase. It should not be started until Phase 2F (search) is stable and well-tested.

## Architecture Impact

- New `document_conversations` and `conversation_messages` tables
- `ChatService` orchestrates: retrieve chunks → build context → call Claude → parse citations → persist
- Context window management: must fit retrieved chunks + conversation history + system prompt into Claude's context limit
- Chat is synchronous (request-response) with streaming response recommended for UX

## Backend Tasks

1. **`document_conversations` migration**
   ```
   id UUID PK
   document_id UUID FK cascade
   user_id UUID FK cascade
   organization_id UUID FK
   created_at, updated_at
   ```

2. **`conversation_messages` migration**
   ```
   id UUID PK
   conversation_id UUID FK cascade
   role enum('user', 'assistant')
   content text
   cited_chunks json nullable (array of chunk IDs + excerpts)
   prompt_tokens int nullable
   completion_tokens int nullable
   created_at
   ```

3. **`ChatService`**
   - `ask(Document $doc, User $user, string $question, ?Conversation $conversation): ChatResponse`
   - Steps:
     1. Embed the question
     2. Retrieve top-K chunks via `SemanticSearchService`
     3. Build context: system prompt + document metadata + retrieved chunks (with chunk IDs as references)
     4. Build message history from prior conversation turns (last N messages, respecting context window)
     5. Call Claude's Messages API
     6. Parse response for citations (`[CHUNK:uuid]` markers)
     7. Persist user message + assistant message to DB
     8. Return `ChatResponse` DTO (content, citations, tokens used)
   - Context window budget: total tokens ≤ 150K for Claude; reserve 4K for response
   - If history + context > budget: truncate oldest history turns first

4. **Citation system**
   - Include chunk IDs in the system prompt: `"When referencing document content, cite it as [CHUNK:uuid]"`
   - Parse `[CHUNK:uuid]` from response, resolve to chunk text for the client
   - Frontend renders citations as expandable excerpt popups

5. **`ChatController`**
   - `POST /api/v1/documents/{id}/conversations` — start conversation
   - `POST /api/v1/documents/{id}/conversations/{convId}/messages` — ask a question
   - `GET /api/v1/documents/{id}/conversations` — list user's conversations for a doc
   - `GET /api/v1/documents/{id}/conversations/{convId}/messages` — load history
   - All endpoints RBAC-gated (document viewer can chat)

6. **Streaming support (strongly recommended)**
   - Laravel Server-Sent Events or WebSocket for streaming Claude's response token-by-token
   - Without streaming, a 500-token response at 40ms/token = 20 second wait with no UI feedback

7. **Hallucination mitigation**
   - System prompt: `"Only answer based on the provided document chunks. If the answer is not in the chunks, say so. Do not invent information."`
   - Temperature: 0 or very low (0.1) — legal chat must be deterministic and conservative
   - Citation enforcement: require at least one `[CHUNK:uuid]` reference in every substantive answer

8. **Rate limiting**
   - Per-user: max 20 messages/hour
   - Per-org: governed by token budget (Phase 2D)

## Frontend Tasks

- Document detail: "Chat with this document" tab
- Chat interface: message list, input box, send button
- Citations: inline `[1]` markers that expand to chunk excerpts on hover
- Streaming: progressive text rendering as Claude responds
- Conversation history: load prior conversations
- "Document doesn't have AI analysis yet" guard — chat disabled until `analyzed`

## Queue/Event Impact

- Chat is synchronous — no new queues
- `ChatMessageSent` event for audit logging (organization, document, user, token count)

## Database Impact

- Two new tables — moderate growth
- `conversation_messages` may grow large for active users — add retention policy (archive conversations > 6 months)
- Index: `(conversation_id, created_at)` for message ordering

## AI/Provider Impact

- Highest token consumption per interaction of any Phase 2 feature
- Context: 8K retrieved chunks + 2K history + 1K system prompt + 1K user message + 4K response = ~16K tokens/message
- Cost: ~$0.05–0.10 per chat message at Sonnet rates
- Token budgets from Phase 2D are critical here

## Risks

- **Hallucination despite instructions:** Claude will occasionally answer beyond the provided chunks. Mitigation: citation requirement, temperature control, fine-tuned system prompt. There is no perfect mitigation — communicate to users that AI chat is a research aid, not legal advice.
- **Context window overflow for long documents:** A 200-page legal document generates 800+ chunks. Retrieval selects top-K relevant ones — but K must be chosen carefully. Too few = incomplete answers. Too many = context overflow. Mitigation: adaptive K based on token budget.
- **Conversation history poisoning:** A user could inject instructions via their own messages. Mitigation: sanitize user input; separate user messages from system context in the prompt structure.
- **Sensitive data in chat logs:** `conversation_messages` contains verbatim questions about legal documents. Treat as PII-adjacent — encrypt at rest or database-level security.

## Testing Strategy

- Feature test `ChatService` with `Http::fake()` for Claude
- Test context window budget calculation (truncates history before chunks)
- Test citation parsing (valid, missing, malformed)
- Test tenant isolation (user cannot chat with another org's document)
- Test rate limiting enforcement
- Test "no chunks available" path (document not yet embedded)

## Rollout Strategy

- Beta feature: enabled per-organization by superadmin toggle
- Requires Phase 2E (embeddings) to be complete for the target documents
- Monitor token consumption closely — chat can be 10x more expensive than analysis per user session

## Future Extensibility

- Multi-document chat (ask across a corpus, not just one document)
- Cross-document citation (answer references chunks from multiple documents)
- Conversation sharing / export
- Session memory for recurring document workflows

---

---

# Phase 2H — Enterprise AI Safeguards

## Objectives

Harden the AI pipeline for enterprise legal use: PII detection, prompt injection defenses, complete auditability, access controls on AI features, and explainability requirements. Some of these can be layered onto earlier phases rather than built as a standalone phase.

## Architecture Impact

- New middleware: `AIAccessMiddleware` (RBAC gate for AI endpoints)
- New service: `PromptSanitizer` (strips/escapes adversarial patterns before sending to LLM)
- New service: `PIIDetector` (flags documents with sensitive PII before AI processing)
- Audit log completeness: every AI call, every flag, every chat message logged with user, org, timestamp
- Explainability: AI flags include `explanation` (Phase 2C already planned this)

## Backend Tasks

1. **`PromptSanitizer` service**
   - Wraps all document content in strict XML-style delimiters: `<document id="...">...</document>`
   - Validates that content doesn't exceed token budget before sending
   - Logs a warning if content contains patterns like `"ignore previous instructions"` (doesn't block — just logs)
   - Applied in `ClaudeClient` before every completion call

2. **PII awareness**
   - Add `contains_pii boolean default false` to `documents` table
   - `PIIDetectionJob` (optional, can be simple regex): flag documents containing SSN, credit card, passport patterns
   - If `contains_pii = true`: restrict AI processing to org admin only (RBAC)
   - Note: full PII redaction is complex — initial phase is detection + gating, not redaction

3. **AI feature RBAC**
   - New permissions: `documents.ai.analyze`, `documents.ai.chat`, `documents.search.semantic`
   - Admin and manager roles: all AI permissions
   - Staff role: chat allowed, analysis triggered automatically (not manually), search allowed
   - Superadmin: all permissions + cost controls

4. **Audit log completeness review**
   - All AI jobs already write to `audit_logs` (Phase 2B/2C plan includes this)
   - Add: `chat.message.sent`, `search.query.executed` audit actions
   - Every audit log entry must include `organization_id` — enforce via `AuditLog::create()` validation

5. **Prompt injection documentation**
   - Add `docs/ai-security.md` describing:
     - What prompt injection is in the context of legal documents
     - The delimiter defense strategy
     - What the system does and does not guarantee
     - How to report suspected injection incidents

6. **AI response logging**
   - Store raw Claude responses in `ai_requests.raw_response` (already planned in Phase 2D `AiRequest` table)
   - Retention: 30 days (legal/compliance requirement window)
   - Access: superadmin only via API

## Frontend Tasks

- Permission-gated UI: hide AI features for roles that don't have permissions
- "AI Processing" indicator disabled with reason if budget exhausted
- PII warning banner on documents flagged with `contains_pii`

## Testing Strategy

- Test `PromptSanitizer` strips/wraps content correctly
- Test RBAC gates block unauthorized AI feature access
- Test audit log completeness for each AI action type
- Penetration test: attempt prompt injection via document content

## Future Extensibility

- Full PII redaction pipeline (Phase 3 — requires entity extraction model)
- SOC 2 / ISO 27001 audit log export
- AI decision explainability reports for enterprise clients

---

---

# Phase 2I — AI Provider Abstraction

## Objectives

Refactor the Claude-specific implementation behind a provider-agnostic interface. Allow switching between Claude, OpenAI, and future providers at the configuration level without code changes. This is a refactor phase — no new features.

**Do this last.** Building the abstraction before having a working implementation leads to over-engineering. Build working Claude integration across all phases, then abstract.

## Architecture Impact

- `AIProviderContract` (already exists as `AIServiceContract` stub — rename and implement)
- `ClaudeProvider implements AIProviderContract`
- `OpenAIProvider implements AIProviderContract` (stub — for future)
- `EmbeddingProviderContract` separate (embedding models differ from completion models)
- Feature flag: `AI_PROVIDER=claude|openai|gemini`

## Interface Design

```php
interface AIProviderContract {
    public function complete(CompletionRequest $request): CompletionResponse;
    public function countTokens(string $text): int;
    public function getModel(): string;
    public function getProvider(): string;
}

interface EmbeddingProviderContract {
    public function embed(string $text): float[];
    public function getDimensions(): int;
    public function getModel(): string;
}

readonly class CompletionRequest {
    string $systemPrompt;
    string $userPrompt;
    int $maxTokens;
    float $temperature;
    string $jobType;
}

readonly class CompletionResponse {
    string $content;
    int $promptTokens;
    int $completionTokens;
    string $model;
    string $rawResponse;
}
```

## Provider Strategy

| Feature | Phase 2 (Claude) | Phase 2I+ Option |
|---------|-----------------|-----------------|
| Document analysis | Claude Sonnet | Claude Opus for complex, Haiku for simple |
| Risk detection | Claude Sonnet | Same |
| Embeddings | Claude or OpenAI `text-embedding-3-small` | Lock to one — switching requires re-embedding |
| Chat | Claude Sonnet | Claude — best for legal comprehension |

**Recommendation:** Use Claude for completion tasks (best legal reasoning). Use OpenAI `text-embedding-3-small` for embeddings (cheaper, stable dimensions). This is not vendor lock-in — the `EmbeddingProviderContract` abstracts the choice.

## Testing Strategy

- Test `ClaudeProvider` against `Http::fake()`
- Swap provider in test config to verify abstraction boundary works
- Add integration test flag that skips `Http::fake()` and hits real API (never in CI, manual only)

---

---

# Cross-Cutting Architecture Decisions

## Error Handling Hierarchy

```
AppException (existing)
├── AIProviderException (new, Phase 2B) — API failure
├── AIBudgetExceededException (new, Phase 2D) — token limit
├── AIAnalysisException (new, Phase 2B) — JSON parse failure
├── EmbeddingException (new, Phase 2E) — embedding failure
├── DocumentNotEmbeddedException (new, Phase 2G) — chat before embedding
└── PIIAccessDeniedException (new, Phase 2H) — PII gating
```

## Prompt Template Versioning

The existing `templates.php` structure must be extended:

```php
return [
    'document.analyze' => [
        'v1' => '...',  // current
        'v2' => '...',  // future
    ],
    'current' => [
        'document.analyze' => 'v1',
    ]
];
```

Store `prompt_version` in `ai_requests` — critical for debugging "why did this analysis change?"

## Re-processing Strategy

When a document needs re-analysis (better model, user request, error recovery):

1. Status must go back to `ocr_completed` (valid transition: `failed → ocr_processing → ocr_completed`)
2. Re-processing is explicitly triggered — never automatic
3. Old `document_analyses`, `document_chunks` records are overwritten (updateOrCreate)
4. Old `compliance_flags` with `ai_generated = true` are soft-deleted before new ones are created
5. Artisan command: `php artisan documents:reanalyze {documentId}`

---

---

# Database Evolution Summary

| Phase | Tables Added | Tables Modified |
|-------|-------------|----------------|
| 2B | — | `document_analyses` (+confidence, +raw_response, +parties, +governing_law) |
| 2B | — | `documents` (+`ai_processing` status) |
| 2C | — | `compliance_flags` (+ai_generated, +confidence, +source, +ai_model, +explanation) |
| 2D | `ai_requests`, `ai_token_budgets` | — |
| 2E | `document_chunks` (with vector column) | — |
| 2G | `document_conversations`, `conversation_messages` | — |
| 2H | — | `documents` (+contains_pii) |

---

# Scaling Considerations

## Short-Term (< 1,000 documents/org)

Current architecture handles this comfortably. Horizon with 1-3 workers per queue, PostgreSQL with standard indexes, pgvector with exact search.

## Medium-Term (10K–100K documents/org)

- `ivfflat` → `hnsw` index for pgvector (higher recall, better performance)
- `ai_requests` table partitioned by `created_at` (monthly partitions)
- `document_chunks` table partitioned by `organization_id`
- Redis result caching for repeated semantic search queries
- Analysis queue auto-scaling based on queue depth

## Long-Term (1M+ documents)

- Dedicated read replicas for search queries
- pgvector on a dedicated PostgreSQL instance
- Consider managed vector database (Pinecone, Weaviate) — but only if pgvector becomes a bottleneck. Do not prematurely migrate.
- Background re-indexing service for stale embeddings

---

# Recommended Sequencing

```
NOW       → Phase 2B: AI Analysis Layer
            (uses existing document_analyses table, closes the pipeline loop)

+1 sprint → Phase 2D: Observability & Cost Controls
            (deploy alongside 2B — token tracking from day 1 of real AI usage)

+2 sprint → Phase 2C: Compliance Intelligence
            (automated flag generation — core product value)

+3 sprint → Phase 2E: Chunking & Embeddings
            (infrastructure for search and chat)

+4 sprint → Phase 2F: Semantic Search
            (first user-visible search feature)

+5 sprint → Phase 2G: AI Document Chat
            (RAG — highest complexity, highest value)

+6 sprint → Phase 2H: Enterprise Safeguards
            (harden before enterprise sales)

+7 sprint → Phase 2I: Provider Abstraction
            (refactor after full implementation proven)
```

---

# Why Phase 2B First — The Case

**Phase 2B must come next because:**

1. The OCR pipeline produces extracted text. That text is currently written to `document_extractions` and the document status stops at `ocr_completed`. Nothing happens next. The pipeline is incomplete.

2. `document_analyses` table already exists with the correct schema. `AIAnalysisJob` stub already exists. `PromptLoader` with `document.analyze` template already exists. Phase 2B is completing a half-built system, not starting a new one.

3. Every other Phase 2 feature (compliance intelligence, embeddings, search, chat) depends on having structured analysis output. Phase 2C needs risk score and key points. Phase 2E is triggered by `DocumentAnalysisCompleted`. Phase 2G needs analysis for context.

4. Phase 2B closes the first end-to-end loop: upload → OCR → analysis → compliance flags. Without 2B, the platform has no AI output. With 2B, it has a usable product.

**What must be stable before Phase 2B:**

- Claude API key provisioned and tested
- `ai_requests` observability table deployed (Phase 2D infrastructure, deployed first)
- Token budget defaults set (don't wait for the full Phase 2D — deploy the tables, set permissive defaults)
- `document.analyze` prompt template reviewed and finalized
- JSON response schema agreed on and documented

---

# What Must Be Stabilized Before Proceeding Further

After Phase 2B:
- Analysis quality must be validated on 10+ real legal documents before 2C
- Token costs must be understood and budgeted
- Error rates (API failures, JSON parse failures) must be below 5%

After Phase 2C:
- False positive rate on compliance flags must be reviewed by a legal domain expert (or proxy)
- Controlled vocabulary for flag types must be finalized — changing it later requires DB migrations

After Phase 2E:
- Chunking quality must be validated before building search on top of it
- Vector index must be built and tested with at least 100 documents

The worst outcome is rushing to Phase 2G (chat) before the retrieval quality (2E, 2F) is validated. RAG quality is directly capped by retrieval quality. Bad chunks → bad search → bad chat → users lose trust.

Build each layer. Validate it. Then proceed.
