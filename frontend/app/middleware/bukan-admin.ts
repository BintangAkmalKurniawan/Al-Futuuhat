import type { User } from '~/types/api'

/**
 * Halaman khusus penulis (dasbor anggota dan "Materi saya"). Draf 2.6 (KP-08.6):
 * admin hanya mengelola dan tidak menulis materi, jadi dialihkan ke panel admin.
 * Dipakai setelah middleware `auth` (mis. middleware: ['auth', 'bukan-admin']).
 */
export default defineNuxtRouteMiddleware(() => {
  const user = useState<User | null>('user', () => null)

  if (user.value?.role === 'admin') {
    return navigateTo('/admin')
  }
})
