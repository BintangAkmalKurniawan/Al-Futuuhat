import type { ResponsData, ResponsLogin, User } from '~/types/api'

/**
 * Autentikasi: token disimpan di cookie `token`, data pengguna di state `user`.
 */
export function useAuth() {
  const api = useApi()
  const token = useCookie<string | null>('token')
  const user = useState<User | null>('user', () => null)

  const isLoggedIn = computed(() => Boolean(token.value))
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function daftar(data: FormData) {
    return api.post<{ message: string; data: User }>('/register', data)
  }

  async function login(email: string, password: string) {
    const respons = await api.post<ResponsLogin>('/login', { email, password })

    token.value = respons.token
    user.value = respons.user

    return respons.user
  }

  async function fetchMe() {
    const respons = await api.get<ResponsData<User>>('/me')

    user.value = respons.data

    return respons.data
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch {
      // Token mungkin sudah tidak berlaku; sesi lokal tetap dibersihkan.
    } finally {
      token.value = null
      user.value = null
      await navigateTo('/masuk')
    }
  }

  return {
    token,
    user,
    isLoggedIn,
    isAdmin,
    daftar,
    login,
    fetchMe,
    logout,
  }
}