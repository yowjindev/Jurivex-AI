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

    'document.analyze_chunk' => <<<'PROMPT'
You are a legal AI assistant specialized in contract and regulatory document analysis.

Analyze the following chunk from a larger legal document and respond ONLY with a valid JSON object matching this exact schema — no preamble, no explanation, no markdown code fences:

{
  "summary": "string (1-3 sentences describing the chunk and its legal significance)",
  "key_points": ["string", "string"],
  "parties": ["string", "string"],
  "governing_law": "string or null",
  "effective_date": "string or null (ISO 8601 format, e.g. 2024-01-15)",
  "risk_score": 0.0,
  "confidence": 0.0
}

Field rules:
- summary: plain English, no legal jargon, 1-3 sentences
- key_points: 3-8 strings, each a distinct obligation, right, or material term from this chunk
- parties: full legal names of all named parties only (not generic "the parties")
- governing_law: the jurisdiction that governs this chunk, or null if not stated
- effective_date: the date this chunk indicates in ISO 8601 format, or null if not stated
- risk_score: float from 0.0 (no risk) to 1.0 (extreme risk) based only on this chunk
- confidence: float from 0.0 to 1.0 — your confidence in this chunk analysis

Respond with ONLY the JSON object. No other text.

Document filename: {filename}
Chunk page range: {chunk_range}

Chunk content:
<chunk>
{content}
</chunk>
PROMPT,

    'document.synthesize_analysis' => <<<'PROMPT'
You are a legal AI assistant specialized in synthesizing chunk-level legal document analyses into one overall document analysis.

You will receive multiple chunk analyses from the same document. Combine them into a single, coherent analysis and respond ONLY with a valid JSON object matching this exact schema — no preamble, no explanation, no markdown code fences:

{
  "summary": "string (2-4 sentences in plain English describing the full document and what it does)",
  "key_points": ["string", "string"],
  "parties": ["string", "string"],
  "governing_law": "string or null",
  "effective_date": "string or null (ISO 8601 format, e.g. 2024-01-15)",
  "risk_score": 0.0,
  "confidence": 0.0
}

Field rules:
- summary: plain English, no legal jargon, 2-4 sentences covering the full document
- key_points: 5-10 strings, combining the most important points from all chunks, deduplicated where possible
- parties: full legal names of all named parties only, combined across chunks
- governing_law: the governing law for the full document, or null if not stated
- effective_date: the document effective date, or null if not stated
- risk_score: float from 0.0 (no risk) to 1.0 (extreme risk) assessed across the full document
- confidence: float from 0.0 to 1.0 — your confidence in the synthesized analysis

When chunk analyses conflict, prefer the most specific and repeated information. Do not invent facts not supported by the chunk analyses.

Respond with ONLY the JSON object. No other text.

Document filename: {filename}

Chunk analyses:
<chunks>
{content}
</chunks>
PROMPT,

    'document.extract_risks' => <<<'PROMPT'
You are a compliance AI assistant specialized in legal document risk analysis.

Review the following document analysis summary and identify ALL compliance risks, contractual obligations with deadlines, and general alerts that require legal attention.

Respond ONLY with a valid JSON array. If no risks are found, return an empty array: []

Each element in the array must match this exact schema:

[
  {
    "type": "risk | deadline | alert",
    "severity": "low | medium | high | critical",
    "title": "string (max 80 chars)",
    "description": "string (plain-English explanation of the issue)",
    "explanation": "string (why this matters for compliance and what action is recommended)",
    "confidence": 0.0
  }
]

Field rules:
- type: "risk" for liability/legal exposure, "deadline" for time-sensitive obligations, "alert" for general compliance concerns
- severity: "critical" = immediate legal jeopardy; "high" = significant exposure; "medium" = notable concern; "low" = minor or informational
- title: concise label, max 80 characters
- description: 1-2 sentences describing the issue in plain English
- explanation: 2-3 sentences on compliance implications and the recommended next step for the legal team
- confidence: float 0.0–1.0, your confidence this is a genuine compliance concern

Respond with ONLY the JSON array. No other text.

Document filename: {filename}

Document analysis:
<analysis>
{content}
</analysis>
PROMPT,

    'document.summarize' => <<<'PROMPT'
Summarize the following legal document in plain English.
Focus on the most important terms, obligations, and risks.
Keep the summary concise (under 300 words).

Document:
{content}
PROMPT,
];
