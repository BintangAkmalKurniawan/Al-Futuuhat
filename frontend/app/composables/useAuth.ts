import type { ResponsData, ResponsLogin, User } from '~/types/api'

// Autentikasi
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

  // Lupa kata sandi
  async function lupaKataSandi(email: string) {
    return api.post<{ message: string }>('/lupa-kata-sandi', { email })
  }

  // Atur ulang kata sandi
  async function aturUlangKataSandi(data: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }) {
    return api.post<{ message: string }>('/atur-ulang-kata-sandi', data)
  }

  // Kirim ulang email konfirmasi
  async function kirimUlangVerifikasi(email: string) {
    return api.post<{ message: string }>('/email/kirim-ulang', { email })
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch {
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
    lupaKataSandi,
    aturUlangKataSandi,
    kirimUlangVerifikasi,
    logout,
  }
}