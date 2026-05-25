# Domain Boundaries

## Module Ownership

Each module owns its models, routes, and business logic. Cross-module access is through **events** (async) or **service injection** (sync, one-way dependency).

| Module | Owns | Depends on |
|--------|------|-----------|
| Auth | User, AuditLog | — |
| Organizations | Organization | Auth (User) |
| Documents | Document, DocumentAnalysis | Auth (User, AuditLog), Organizations |
| Compliance | ComplianceFlag | Auth (User, AuditLog), Documents |
| AI | AI jobs, Prompts, Pipeline | Documents (Document model) |

## Allowed Cross-Module Dependencies

```
Auth ←─────────────── Documents (uses User, writes AuditLog)
Auth ←─────────────── Compliance (uses User, writes AuditLog)
Auth ←─────────────── Organizations (uses User)
Documents ←──────────── Compliance (ComplianceFlag references document_id)
Documents ←──────────── AI (AI jobs receive Document)
```

## Forbidden Patterns

- **Compliance calling DocumentService** — use events instead
- **AI calling ComplianceRepository** — AI jobs create ComplianceFlags via direct model writes; Compliance module fires the event
- **Controllers importing from other modules' services** — always inject via service provider bindings
- **Models importing from other modules' models directly** — use foreign key strings, not direct class imports (except for Eloquent relationship definitions)

## Event Contracts

Events are the public API between modules:

| Event | Fired by | Consumed by |
|-------|---------|-------------|
| `DocumentUploaded` | Documents | AI (Phase 2: start pipeline) |
| `DocumentProcessingStarted` | Documents | Notifications (Phase 2: real-time push) |
| `DocumentAnalysisCompleted` | Documents | Notifications (Phase 2: email/push) |
| `ComplianceFlagGenerated` | Compliance (Phase 2: RiskDetectionJob) | Notifications (Phase 2) |

## Tenant Isolation Contract

**Every query must filter by organization_id.** This is enforced by:

1. Repository methods accepting `$organizationId` as a parameter
2. Services passing `$user->organization_id` to repositories
3. No global scopes (explicit is safer than magic)

Violation of this rule is a **critical security bug**.
