// HTTP status codes referenced by the frontend error handler and tests.
// Mirrors the statusCode values in backend/app/Exceptions/*.php.
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE: 422,
  SERVER_ERROR: 500,
} as const

// Document status values — must match Document::STATUS_* constants in backend.
export const DOCUMENT_STATUS = {
  PENDING: 'pending',
  PROCESSING: 'processing',
  OCR_PROCESSING: 'ocr_processing',
  OCR_COMPLETED: 'ocr_completed',
  ANALYZED: 'analyzed',
  FAILED: 'failed',
} as const

// Compliance severity values — must match ComplianceFlag::SEVERITY_* constants in backend.
export const COMPLIANCE_SEVERITY = {
  LOW: 'low',
  MEDIUM: 'medium',
  HIGH: 'high',
  CRITICAL: 'critical',
} as const

// Compliance type values — must match ComplianceFlag::TYPE_* constants in backend.
export const COMPLIANCE_TYPE = {
  RISK: 'risk',
  DEADLINE: 'deadline',
  ALERT: 'alert',
} as const
