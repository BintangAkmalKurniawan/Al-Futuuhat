import type { User } from '~/types/api'

/**
 * Wajib login. Data pengguna dimuat ulang dari /me bila state masih kosong
 * (mis. setelah halaman dimuat ulang).
 */
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

  // Draf 2.6: admin tidak pernah melihat tampilan anggota. Halaman anggota yang
  // tetap boleh dibuka admin (arsip, baca materi + forum, struktur, notifikasi)
  // ditampilkan di dalam layout admin.
  if (user.value?.role === 'admin' && to.meta.layout === 'anggota') {
    setPageLayout('admin')
  }
})