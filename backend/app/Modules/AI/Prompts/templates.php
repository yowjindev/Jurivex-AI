<?php

return [
    'document.analyze' => <<<'PROMPT'
You are a legal AI assistant specialized in contract and regulatory document analysis.

Analyze the following legal document and respond ONLY with a valid JSON object matching this exact schema — no preamble, no explanation, no markdown code fences:

{
  "summary": "string (2-4 sentences in plain English describing what this document is and what it does)",
  "key_points": ["string", "string"],
  "parties": ["string", "string"],
  "governing_law": "string or null",
  "effective_date": "string or null (ISO 8601 format, e.g. 2024-01-15)",
  "risk_score": 0.0,
  "confidence": 0.0
}

Field rules:
- summary: plain English, no legal jargon, 2-4 sentences
- key_points: 5-10 strings, each a distinct obligation, right, or material term
- parties: full legal names of all named parties only (not generic "the parties")
- governing_law: the jurisdiction that governs this agreement, or null if not stated
- effective_date: the date the agreement takes effect in ISO 8601 format, or null if not stated
- risk_score: float from 0.0 (no risk) to 1.0 (extreme risk) — assess based on one-sided terms, uncapped liability, missing standard protections, or unusual termination rights
- confidence: float from 0.0 to 1.0 — your confidence in this analysis given the document clarity and completeness

Respond with ONLY the JSON object. No other text.

Document filename: {filename}

Document content:
<document>
{content}
</document>
PROMPT,

    'document.extract_risks' => <<<'PROMPT'
You are a compliance AI assistant. Review the following document and identify compliance risks.
For each risk, provide:
- Type: risk | deadline | alert
- Severity: low | medium | high | critical
- Title: brief title (max 80 chars)
- Description: detailed explanation

Document:
{content}
PROMPT,

    'document.summarize' => <<<'PROMPT'
Summarize the following legal document in plain English.
Focus on the most important terms, obligations, and risks.
Keep the summary concise (under 300 words).

Document:
{content}
PROMPT,
];
