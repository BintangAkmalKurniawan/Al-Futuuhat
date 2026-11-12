import type { ResponsData, StatistikAdmin, User } from '~/types/api'

// Profil dan statistik
export function useProfil() {
  const api = useApi()

  const tampil = () => api.get<ResponsData<User>>('/profil')

  // Ubah profil
  const ubah = (data: FormData) => api.post<{ message: string; data: User }>('/profil', data)

  const gantiKataSandi = (data: { password_lama: string; password: string; password_confirmation: string }) =>
    api.put<{ message: string }>('/profil/password', data)

  const statistik = () => api.get<ResponsData<StatistikAdmin>>('/admin/statistik')

  return { tampil, ubah, gantiKataSandi, statistik }
}
