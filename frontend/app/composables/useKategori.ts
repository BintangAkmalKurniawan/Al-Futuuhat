import type { Kategori, ResponsData } from '~/types/api'

/**
 * Kategori materi (KF-19): daftar untuk penyaring di halaman anggota dan
 * publik, serta pengelolaan oleh admin di `/admin/kategori`.
 */
export function useKategori() {
  const api = useApi()

  /** Daftar kategori untuk penyaring; dapat dibaca tanpa login (KP-19.2). */
  const daftar = () => api.get<ResponsData<Kategori[]>>('/kategori')

  /** Daftar kategori beserta jumlah materi pemakai untuk halaman admin. */
  const daftarAdmin = () => api.get<ResponsData<Kategori[]>>('/admin/kategori')

  const simpan = (data: { nama: string }) =>
    api.post<{ message: string; data: Kategori }>('/admin/kategori', data)

  const ubah = (id: number | string, data: { nama: string }) =>
    api.put<{ message: string; data: Kategori }>(`/admin/kategori/${id}`, data)

  const hapus = (id: number | string) => api.hapus<{ message: string }>(`/admin/kategori/${id}`)

  return { daftar, daftarAdmin, simpan, ubah, hapus }
}
