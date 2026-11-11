import type {
  DataTinjauRevisi,
  KeputusanVerifikasi,
  Penarikan,
  Revisi,
  ResponsData,
  ResponsHalaman,
} from '~/types/api'

/**
 * Verifikasi oleh admin: pengajuan publikasi (KF-11) dan penarikan materi
 * dari publik (KF-20). Kedua antrean berisi yang masih menunggu, terlama di atas.
 */
export function useVerifikasi() {
  const api = useApi()

  const antrean = (page = 1) =>
    api.get<ResponsHalaman<Revisi>>('/admin/verifikasi-materi', { query: { page } })

  const tinjau = (id: number | string) =>
    api.get<ResponsData<DataTinjauRevisi>>(`/admin/revisi/${id}`)

  const putuskan = (id: number | string, keputusan: KeputusanVerifikasi, catatan?: string) =>
    api.post<{ message: string; data: Revisi }>(`/admin/revisi/${id}/verifikasi`, {
      keputusan,
      catatan,
    })

  /** Antrean penarikan materi yang menunggu (KP-20.4). */
  const antreanPenarikan = (page = 1) =>
    api.get<ResponsHalaman<Penarikan>>('/admin/penarikan-materi', { query: { page } })

  /** Setujui/tolak penarikan; catatan wajib saat menolak (KP-20.5, KP-20.6). */
  const putuskanPenarikan = (id: number | string, keputusan: KeputusanVerifikasi, catatan?: string) =>
    api.post<{ message: string; data: Penarikan }>(`/admin/penarikan/${id}/verifikasi`, {
      keputusan,
      catatan,
    })

  return { antrean, tinjau, putuskan, antreanPenarikan, putuskanPenarikan }
}
