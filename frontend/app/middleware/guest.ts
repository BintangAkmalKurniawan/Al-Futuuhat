import type { User } from '~/types/api'

/**
 * Hanya untuk tamu. Pengguna yang sudah masuk diarahkan ke dasbornya
 * (admin → /admin, anggota → /dasbor).
 */
export default defineNuxtRouteMiddleware(async () => {
  const token = useCookie<string | null>('token')

  if (!token.value) return

  const user = useState<User | null>('user', () => null)

  if (!user.value) {
    try {
      await useAuth().fetchMe()
    } catch {
      // Token tidak berlaku; useApi sudah membersihkan sesi.
      return
    }
  }

  if (user.value) {
    return navigateTo(user.value.role === 'admin' ? '/admin' : '/dasbor')
  }
})