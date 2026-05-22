<?php

return [
    'document.analyze' => <<<'PROMPT'
You are a legal AI assistant. Analyze the following legal document and provide:
1. A structured plain-English summary
2. Key parties and their obligations
3. Critical dates and deadlines
4. Potential compliance concerns

Document:
{content}
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
