import type { PengaturanBeranda, ResponsData } from '~/types/api'

/**
 * Konten beranda pengunjung: cerita Al-Futuuhat (KF-18) dan kontak organisasi (KF-22).
 * Dibaca pengunjung tanpa login, diubah lewat halaman admin.
 */
export function useBeranda() {
  const api = useApi()

  /** Konten beranda untuk halaman pengunjung. */
  const publik = () => api.get<ResponsData<PengaturanBeranda>>('/publik/beranda')

  /** Isi awal formulir admin. */
  const tampil = () => api.get<ResponsData<PengaturanBeranda>>('/admin/beranda')

  /**
   * Simpan cerita dan kontak organisasi sekaligus (FormData, POST).
   */
  const simpan = (data: FormData) =>
    api.post<{ message: string; data: PengaturanBeranda }>('/admin/beranda', data)

  return { publik, tampil, simpan }
}
