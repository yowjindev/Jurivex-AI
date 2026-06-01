import api from './client'
import type { ApiResponse, Conversation, ConversationMessage, ChatReply } from '@/types'

export function listConversations(documentId: string) {
  return api
    .get<ApiResponse<Conversation[]>>(`/api/v1/documents/${documentId}/conversations`)
    .then((r) => r.data)
}

export function startConversation(documentId: string, message: string) {
  return api
    .post<ApiResponse<ChatReply>>(`/api/v1/documents/${documentId}/conversations`, { message })
    .then((r) => r.data.data)
}

export function sendMessage(documentId: string, conversationId: string, message: string) {
  return api
    .post<ApiResponse<ChatReply>>(
      `/api/v1/documents/${documentId}/conversations/${conversationId}/messages`,
      { message }
    )
    .then((r) => r.data.data)
}

export function getMessages(documentId: string, conversationId: string) {
  return api
    .get<ApiResponse<ConversationMessage[]>>(
      `/api/v1/documents/${documentId}/conversations/${conversationId}/messages`
    )
    .then((r) => r.data)
}
