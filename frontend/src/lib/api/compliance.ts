import api from './client'
import type { ApiResponse, ComplianceFlag, PaginatedApiResponse } from '@/types'

export function listFlags(page = 1) {
  return api
    .get<PaginatedApiResponse<ComplianceFlag>>('/api/v1/compliance/flags', { params: { page } })
    .then((r) => r.data)
}

export function resolveFlag(id: string) {
  return api
    .patch<ApiResponse<ComplianceFlag>>(`/api/v1/compliance/flags/${id}/resolve`)
    .then((r) => r.data.data)
}
