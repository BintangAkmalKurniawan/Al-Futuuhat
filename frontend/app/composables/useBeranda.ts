import type { PengaturanBeranda, ResponsData } from '~/types/api'

// Konten beranda pengunjung
export function useBeranda() {
  const api = useApi()

  // Konten beranda publik
  const publik = () => api.get<ResponsData<PengaturanBeranda>>('/publik/beranda')

  // Konten beranda admin
  const tampil = () => api.get<ResponsData<PengaturanBeranda>>('/admin/beranda')

  // Simpan konten beranda
  const simpan = (data: FormData) =>
    api.post<{ message: string; data: PengaturanBeranda }>('/admin/beranda', data)

  return { publik, tampil, simpan }
}
