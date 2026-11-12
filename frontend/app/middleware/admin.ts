import type { User } from '~/types/api'

// Wajib admin
export default defineNuxtRouteMiddleware(() => {
  const user = useState<User | null>('user', () => null)

  if (user.value && user.value.role !== 'admin') {
    return navigateTo('/dasbor')
  }
})