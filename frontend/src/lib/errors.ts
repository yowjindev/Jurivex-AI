import type { AxiosError } from 'axios'
import type { ApiError, ValidationError } from '@/types'

export function parseApiError(error: unknown): string {
  if (!error) return 'An unexpected error occurred.'

  const axiosError = error as AxiosError<ApiError | ValidationError>
  const data = axiosError.response?.data

  if (!data) {
    if (axiosError.code === 'ERR_NETWORK') return 'Network error. Check your connection.'
    return 'An unexpected error occurred.'
  }

  // Laravel validation error shape: { message, errors: { field: [messages] } }
  if ('errors' in data && typeof data.errors === 'object') {
    const firstField = Object.values(data.errors)[0]
    if (Array.isArray(firstField) && firstField.length > 0) {
      return firstField[0]
    }
  }

  // Standard API error shape: { success: false, message }
  if ('message' in data && typeof data.message === 'string') {
    return data.message
  }

  return 'An unexpected error occurred.'
}
