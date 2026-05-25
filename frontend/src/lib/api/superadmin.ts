import api from './client'
import type { ApiResponse, InvitationCode, InvitationLookup, OrgStats } from '@/types'

export function lookupInvitation(code: string) {
  return api
    .get<ApiResponse<InvitationLookup>>(`/api/v1/auth/invitation/${code}`)
    .then((r) => r.data.data)
}

export function listOrganizations() {
  return api
    .get<ApiResponse<OrgStats[]>>('/api/v1/superadmin/organizations')
    .then((r) => r.data.data)
}

export function createOrganization(name: string) {
  return api
    .post<ApiResponse<{ id: string; name: string; slug: string }>>(
      '/api/v1/superadmin/organizations',
      { name },
    )
    .then((r) => r.data.data)
}

export function listInvitationCodes(orgId: string) {
  return api
    .get<ApiResponse<InvitationCode[]>>(
      `/api/v1/superadmin/organizations/${orgId}/invitation-codes`,
    )
    .then((r) => r.data.data)
}

export function generateInvitationCode(
  orgId: string,
  role: 'admin' | 'manager' | 'staff',
  expiresAt?: string,
) {
  return api
    .post<ApiResponse<InvitationCode>>(
      `/api/v1/superadmin/organizations/${orgId}/invitation-codes`,
      { role, expires_at: expiresAt ?? null },
    )
    .then((r) => r.data.data)
}
