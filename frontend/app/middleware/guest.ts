import type { User } from '~/types/api'

// Hanya untuk tamu
export default defineNuxtRouteMiddleware(async () => {
  const token = useCookie<string | null>('token')

  if (!token.value) return

  const user = useState<User | null>('user', () => null)

  if (!user.value) {
    try {
      await useAuth().fetchMe()
    } catch {
      return
    }
  }

  if (user.value) {
    return navigateTo(user.value.role === 'admin' ? '/admin' : '/dasbor')
  }
})