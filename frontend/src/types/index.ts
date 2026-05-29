import type { DOCUMENT_STATUS, COMPLIANCE_SEVERITY, COMPLIANCE_TYPE } from '@/lib/api/constants'

export interface User {
  id: string
  name: string
  email: string
  organization_id: string
  roles: string[]
  created_at: string
}

export interface Organization {
  id: string
  name: string
  slug: string
  industry: string
  plan: string
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  message: string
  meta: Record<string, unknown>
}

export interface ValidationError {
  message: string
  errors: Record<string, string[]>
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedApiResponse<T> {
  success: boolean
  data: T[]
  message: string
  meta: PaginationMeta
}

export interface DocumentAnalysis {
  summary: string
  key_points: string[]
  parties: string[]
  governing_law: string | null
  effective_date: string | null
  risk_score: number
  confidence: number
  ai_model: string
  analyzed_at: string
}

export interface Document {
  id: string
  title: string
  original_filename: string
  mime_type: string
  file_size: number
  status: 'pending' | 'processing' | 'ocr_processing' | 'ocr_completed' | 'ai_processing' | 'analyzed' | 'failed'
  category: string | null
  tags: string[]
  uploaded_by: string
  organization_id: string
  download_url?: string | null
  failure_reason?: string | null
  analysis?: DocumentAnalysis | null
  created_at: string
  updated_at: string
}

export interface ComplianceFlag {
  id: string
  organization_id: string
  document_id: string | null
  type: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  title: string
  description: string
  due_date: string | null
  is_resolved: boolean
  ai_generated: boolean
  confidence: number | null
  source: string | null
  explanation: string | null
  created_at: string
  updated_at: string
}

export interface InvitationLookup {
  organization_name: string
  role: 'admin' | 'manager' | 'staff'
}

export interface OrgStats {
  id: string
  name: string
  slug: string
  member_count: number
  document_count: number
  flag_count: number
  created_at: string
}

export interface InvitationCode {
  id: string
  code: string
  role: 'admin' | 'manager' | 'staff'
  is_used: boolean
  used_at: string | null
  expires_at: string | null
  created_at: string
}

export interface ApiError {
  success: false
  message: string
}

export type DocumentStatus = typeof DOCUMENT_STATUS[keyof typeof DOCUMENT_STATUS]
export type ComplianceSeverity = typeof COMPLIANCE_SEVERITY[keyof typeof COMPLIANCE_SEVERITY]
export type ComplianceType = typeof COMPLIANCE_TYPE[keyof typeof COMPLIANCE_TYPE]
