# AI Pipeline

## Overview

The AI pipeline processes documents through a chain of jobs, each adding structured analysis data. The pipeline is **event-driven and asynchronous** — no AI call blocks an HTTP request.

## Current State (Phase 1.5)

`ProcessDocumentJob` is a stub:

```
Document uploaded (pending)
    → ProcessDocumentJob dispatched
        → status: processing
        → [stub: immediately sets analyzed]
        → status: analyzed
```

The full Phase 2 pipeline is designed but not yet wired.

## Phase 2 Pipeline Design

```
Document uploaded
    → ProcessDocumentJob
        → DocumentStatusManager.transition(processing)
        → DocumentProcessingStarted event
        → DocumentAnalysisPipeline.dispatch()
            → OCRJob
                → Extract text from PDF/image
                → Store raw text in document_analyses.raw_text
            → AIAnalysisJob (chained after OCR)
                → Load prompt: PromptLoader.load('document.analyze', {content})
                → Call OpenAI/Claude API
                → Store: document_analyses.summary, .key_points, .risk_score
            → EmbeddingJob (chained after Analysis)
                → Generate vector embedding
                → Store in pgvector column
            → RiskDetectionJob (chained after Embedding)
                → Load prompt: PromptLoader.load('document.extract_risks', {content})
                → Parse AI response
                → Create ComplianceFlag records
                → ComplianceFlagGenerated events dispatched
        → DocumentAnalysisCompleted event
            → NotificationJob dispatched
```

## Prompt Management

All prompts are centralized in `app/Modules/AI/Prompts/`:

```php
// Load a prompt template with variable interpolation
$loader = app(PromptLoaderContract::class);
$prompt = $loader->load('document.analyze', ['content' => $rawText]);
```

Available templates:
- `document.analyze` — structured legal analysis
- `document.extract_risks` — compliance risk extraction (produces flag candidates)
- `document.summarize` — plain English summary

**Never hardcode prompts in jobs or services.** Always use `PromptLoader`.

## DocumentAnalysis Model

The `document_analyses` table stores AI output:

| Column | Type | Purpose |
|--------|------|---------|
| `id` | uuid | Primary key |
| `document_id` | uuid | FK to documents |
| `summary` | text | Plain-English summary |
| `key_points` | json | Array of key findings |
| `risk_score` | decimal | 0.0–1.0 overall risk |
| `ai_model` | string | Model used (e.g. gpt-4o) |
| `analyzed_at` | timestamp | When analysis completed |

## AI Module Structure

```
app/Modules/AI/
├── Analysis/Jobs/AIAnalysisJob.php      — LLM summarization
├── Contracts/AIServiceContract.php      — base interface for AI services
├── Embeddings/Jobs/EmbeddingJob.php     — vector embedding generation
├── Notifications/Jobs/NotificationJob.php — user notification dispatch
├── OCR/Jobs/OCRJob.php                  — text extraction
├── Pipelines/DocumentAnalysisPipeline.php — Phase 2 job chain orchestrator
├── Prompts/
│   ├── Contracts/PromptLoaderContract.php
│   ├── PromptLoader.php
│   └── templates.php                    — all prompt templates
├── Risk/Jobs/RiskDetectionJob.php       — compliance risk detection
└── Providers/AIServiceProvider.php      — binds PromptLoaderContract, registers pipeline
```
