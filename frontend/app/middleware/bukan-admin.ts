import type { User } from '~/types/api'

// Bukan admin
export default defineNuxtRouteMiddleware(() => {
  const user = useState<User | null>('user', () => null)

  if (user.value?.role === 'admin') {
    return navigateTo('/admin')
  }
})
