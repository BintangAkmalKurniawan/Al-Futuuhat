import type { User } from '~/types/api'

interface OpsiMinta {
  query?: Record<string, unknown>
  body?: unknown
  headers?: Record<string, string>
}

// Pemanggil API
export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('token')
  const user = useState<User | null>('user', () => null)

  async function minta<T>(
    path: string,
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    opsi: OpsiMinta = {},
  ): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
      ...(opsi.headers ?? {}),
    }

    try {
      return await $fetch<T>(path, {
        baseURL: config.public.apiBase,
        method,
        query: opsi.query,
        body: opsi.body as never,
        headers,
      })
    } catch (galat) {
      const status = (galat as { response?: { status?: number } }).response?.status

      if (status === 401 && useRoute().path !== '/masuk') {
        token.value = null
        user.value = null
        await navigateTo('/masuk')
      }

      throw galat
    }
  }

  return {
    get: <T>(path: string, opsi?: OpsiMinta) => minta<T>(path, 'GET', opsi),

    post: <T>(path: string, body?: unknown, opsi?: OpsiMinta) =>
      minta<T>(path, 'POST', { ...opsi, body }),

    put: <T>(path: string, body?: unknown, opsi?: OpsiMinta) =>
      minta<T>(path, 'PUT', { ...opsi, body }),

    patch: <T>(path: string, body?: unknown, opsi?: OpsiMinta) =>
      minta<T>(path, 'PATCH', { ...opsi, body }),

    hapus: <T>(path: string, opsi?: OpsiMinta) => minta<T>(path, 'DELETE', opsi),
  }
}