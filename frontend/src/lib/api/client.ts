import axios from 'axios'

const BASE = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost'

const api = axios.create({
  baseURL: BASE,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

let csrfFetched = false

api.interceptors.request.use(async (config) => {
  const method = (config.method ?? 'get').toLowerCase()
  if (!csrfFetched && method !== 'get') {
    await axios.get(`${BASE}/sanctum/csrf-cookie`, { withCredentials: true })
    csrfFetched = true
  }
  return config
})

export default api
