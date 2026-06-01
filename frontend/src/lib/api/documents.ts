import api from './client'
import type { ApiResponse, Document, PaginatedApiResponse, SearchResult } from '@/types'

export function listDocuments(page = 1) {
  return api
    .get<PaginatedApiResponse<Document>>('/api/v1/documents', { params: { page } })
    .then((r) => r.data)
}

export function uploadDocument(file: File, category?: string) {
  const form = new FormData()
  form.append('file', file)
  if (category) form.append('category', category)
  return api
    .post<ApiResponse<Document>>('/api/v1/documents', form, {
      headers: { 'Content-Type': undefined },
    })
    .then((r) => r.data.data)
}

export function deleteDocument(id: string) {
  return api.delete<ApiResponse<null>>(`/api/v1/documents/${id}`).then((r) => r.data)
}

export function getDocument(id: string) {
  return api
    .get<ApiResponse<Document>>(`/api/v1/documents/${id}`)
    .then((r) => r.data.data)
}

export function searchDocuments(query: string, limit = 10) {
  return api
    .get<ApiResponse<SearchResult[]>>('/api/v1/documents/search', {
      params: { q: query, limit },
    })
    .then((r) => r.data)
}
