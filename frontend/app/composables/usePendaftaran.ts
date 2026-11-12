import type { ResponsData, ResponsHalaman, User } from '~/types/api'

// Verifikasi pendaftaran oleh admin
export function usePendaftaran() {
  const api = useApi()

  const daftarPendaftar = (status: 'menunggu' | 'ditolak', page = 1) =>
    api.get<ResponsHalaman<User>>('/admin/pendaftaran', { query: { status, page } })

  const detailPendaftar = (id: number | string) =>
    api.get<ResponsData<User>>(`/admin/pendaftaran/${id}`)

  const putuskan = (
    id: number | string,
    keputusan: 'disetujui' | 'ditolak',
    catatan?: string,
  ) =>
    api.patch<{ message: string; data: User }>(`/admin/pendaftaran/${id}`, {
      keputusan,
      catatan,
    })

  return {
    daftarPendaftar,
    detailPendaftar,
    putuskan,
  }
}