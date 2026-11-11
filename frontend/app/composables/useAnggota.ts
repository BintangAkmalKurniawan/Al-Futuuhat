import type { Peran, ResponsData, ResponsHalaman, StatusAkun, User } from '~/types/api'

/**
 * Pengelolaan data anggota oleh admin (F3).
 */
export function useAnggota() {
  const api = useApi()

  const daftarAnggota = (params: { q?: string; status?: StatusAkun | ''; page?: number } = {}) =>
    api.get<ResponsHalaman<User>>('/admin/anggota', { query: params })

  const detailAnggota = (id: number | string) =>
    api.get<ResponsData<User>>(`/admin/anggota/${id}`)

  const ubahAnggota = (
    id: number | string,
    data: { nama: string; no_hp: string; alamat: string; role: Peran },
  ) => api.put<{ message: string; data: User }>(`/admin/anggota/${id}`, data)

  const ubahStatus = (id: number | string, status: 'aktif' | 'nonaktif') =>
    api.patch<{ message: string; data: User }>(`/admin/anggota/${id}/status`, { status })

  const aturUlangSandi = (
    id: number | string,
    password: string,
    password_confirmation: string,
  ) =>
    api.patch<{ message: string }>(`/admin/anggota/${id}/password`, {
      password,
      password_confirmation,
    })

  return {
    daftarAnggota,
    detailAnggota,
    ubahAnggota,
    ubahStatus,
    aturUlangSandi,
  }
}