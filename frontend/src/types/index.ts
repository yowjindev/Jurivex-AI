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
