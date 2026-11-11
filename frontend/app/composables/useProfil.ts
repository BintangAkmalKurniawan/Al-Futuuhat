import type { ResponsData, StatistikAdmin, User } from '~/types/api'

/**
 * Profil sendiri (KF-24) dan statistik admin (KF-25).
 */
export function useProfil() {
  const api = useApi()

  const tampil = () => api.get<ResponsData<User>>('/profil')

  /** FormData karena dapat memuat foto; POST (bukan PUT) agar multipart terbaca PHP. */
  const ubah = (data: FormData) => api.post<{ message: string; data: User }>('/profil', data)

  const gantiKataSandi = (data: { password_lama: string; password: string; password_confirmation: string }) =>
    api.put<{ message: string }>('/profil/password', data)

  const statistik = () => api.get<ResponsData<StatistikAdmin>>('/admin/statistik')

  return { tampil, ubah, gantiKataSandi, statistik }
}
