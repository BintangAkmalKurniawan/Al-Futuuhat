import type { User } from '~/types/api'

/**
 * Wajib admin. Dipakai setelah middleware `auth`
 * (mis. middleware: ['auth', 'admin']).
 */
export default defineNuxtRouteMiddleware(() => {
  const user = useState<User | null>('user', () => null)

  if (user.value && user.value.role !== 'admin') {
    return navigateTo('/dasbor')
  }
})