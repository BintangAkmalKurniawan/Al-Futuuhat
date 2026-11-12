import type { User } from '~/types/api'

// Wajib login
export default defineNuxtRouteMiddleware(async (to) => {
  const token = useCookie<string | null>('token')

  if (!token.value) {
    return navigateTo('/masuk')
  }

  const user = useState<User | null>('user', () => null)

  if (!user.value) {
    try {
      await useAuth().fetchMe()
    } catch {
      token.value = null
      user.value = null

      if (useRoute().path !== '/masuk') {
        return navigateTo('/masuk')
      }
      return
    }
  }

  if (user.value?.role === 'admin' && to.meta.layout === 'anggota') {
    setPageLayout('admin')
  }
})