import type {
  DokumentasiProgram,
  ProgramAdmin,
  ProgramDetail,
  ProgramRingkas,
  ResponsData,
  ResponsHalaman,
} from '~/types/api'

/**
 * Program kegiatan rutin Al-Futuuhat (KF-21): dibaca siapa saja tanpa login,
 * dikelola admin di `/admin/program`.
 */
export function useProgram() {
  const api = useApi()

  // ------------------------------------------------------------ Publik

  /** Semua program, yang aktif lebih dulu (KP-21.5). */
  const daftarPublik = () => api.get<ResponsData<ProgramRingkas[]>>('/publik/program')

  const detailPublik = (slug: string) =>
    api.get<ResponsData<ProgramDetail>>(`/publik/program/${encodeURIComponent(slug)}`)

  /** Dokumentasi berpaginasi, terbaru di atas; `jenis` = foto | video (KP-21.6). */
  const daftarDokumentasi = (slug: string, params: { jenis?: string; page?: number } = {}) =>
    api.get<ResponsHalaman<DokumentasiProgram>>(
      `/publik/program/${encodeURIComponent(slug)}/dokumentasi`,
      { query: params },
    )

  // ------------------------------------------------------------ Admin

  const daftarAdmin = () => api.get<ResponsData<ProgramAdmin[]>>('/admin/program')

  const detailAdmin = (id: number | string) =>
    api.get<ResponsData<ProgramAdmin>>(`/admin/program/${id}`)

  /**
   * Tambah/ubah memakai FormData karena sampulnya berkas; keduanya POST
   * (bukan PUT) agar body multipart terbaca PHP.
   */
  const simpan = (data: FormData) =>
    api.post<{ message: string; data: ProgramAdmin }>('/admin/program', data)

  const ubah = (id: number | string, data: FormData) =>
    api.post<{ message: string; data: ProgramAdmin }>(`/admin/program/${id}`, data)

  const hapus = (id: number | string) => api.hapus<{ message: string }>(`/admin/program/${id}`)

  /** Satu berkas atau satu tautan per permintaan (batas ukuran per berkas). */
  const unggahDokumentasi = (programId: number | string, data: FormData) =>
    api.post<{ message: string; data: DokumentasiProgram }>(
      `/admin/program/${programId}/dokumentasi`,
      data,
    )

  const hapusDokumentasi = (id: number | string) =>
    api.hapus<{ message: string }>(`/admin/dokumentasi/${id}`)

  return {
    daftarPublik,
    detailPublik,
    daftarDokumentasi,
    daftarAdmin,
    detailAdmin,
    simpan,
    ubah,
    hapus,
    unggahDokumentasi,
    hapusDokumentasi,
  }
}
