import type {
  Divisi,
  Jabatan,
  Kepengurusan,
  Periode,
  RiwayatStruktur,
  StrukturPublik,
} from '~/types/api'

interface PayloadDivisi {
  nama_divisi: string
  deskripsi?: string | null
}

interface PayloadJabatan {
  nama_jabatan: string
  urutan: number
}

/** Draf 2.5: tanpa periode — perubahan selalu dicatat pada periode saat ini. */
interface PayloadPengurus {
  divisi_id: number | null
  jabatan_id: number
  anggota_id: number
}

/**
 * Struktur organisasi (KF-01, KF-07).
 *
 * Draf 2.5: pengunjung hanya membaca periode saat ini lewat `strukturPublik`;
 * daftar periode, struktur periode lama, dan riwayat perubahan butuh login.
 * Periode tidak lagi dikelola manual — dibuat otomatis per tahun oleh backend.
 */
export function useStruktur() {
  const api = useApi()

  // ------------------------------------------------------------- Publik
  const daftarDivisi = () => api.get<{ data: Divisi[] }>('/divisi')

  /** Struktur periode saat ini; `data` null bila belum ada periode (KP-01.2). */
  const strukturPublik = () => api.get<{ data: StrukturPublik | null }>('/publik/struktur')

  // ------------------------------------------------------------ Anggota
  const daftarPeriode = () => api.get<{ data: Periode[] }>('/periode')

  const strukturPeriode = (id: number | string) =>
    api.get<{ data: StrukturPublik }>(`/periode/${id}/struktur`)

  /** Perbandingan dengan periode sebelumnya + log perubahan (KP-07.8). */
  const riwayatPeriode = (id: number | string) =>
    api.get<{ data: RiwayatStruktur }>(`/periode/${id}/riwayat`)

  // -------------------------------------------------------------- Admin
  const simpanDivisi = (data: PayloadDivisi) =>
    api.post<{ message: string; data: Divisi }>('/admin/divisi', data)

  const ubahDivisi = (id: number, data: PayloadDivisi) =>
    api.put<{ message: string; data: Divisi }>(`/admin/divisi/${id}`, data)

  const hapusDivisi = (id: number) => api.hapus<{ message: string }>(`/admin/divisi/${id}`)

  const daftarJabatan = () => api.get<{ data: Jabatan[] }>('/admin/jabatan')

  const simpanJabatan = (data: PayloadJabatan) =>
    api.post<{ message: string; data: Jabatan }>('/admin/jabatan', data)

  const ubahJabatan = (id: number, data: PayloadJabatan) =>
    api.put<{ message: string; data: Jabatan }>(`/admin/jabatan/${id}`, data)

  const hapusJabatan = (id: number) => api.hapus<{ message: string }>(`/admin/jabatan/${id}`)

  /** Pengurus periode aktif + data periode itu (null bila belum ada periode). */
  const daftarPengurus = () =>
    api.get<{ data: Kepengurusan[]; periode: Periode | null }>('/admin/kepengurusan')

  const simpanPengurus = (data: PayloadPengurus) =>
    api.post<{ message: string; data: Kepengurusan }>('/admin/kepengurusan', data)

  const ubahPengurus = (id: number, data: PayloadPengurus) =>
    api.put<{ message: string; data: Kepengurusan }>(`/admin/kepengurusan/${id}`, data)

  const hapusPengurus = (id: number) =>
    api.hapus<{ message: string }>(`/admin/kepengurusan/${id}`)

  return {
    // publik
    daftarDivisi,
    strukturPublik,
    // anggota
    daftarPeriode,
    strukturPeriode,
    riwayatPeriode,
    // admin — divisi
    simpanDivisi,
    ubahDivisi,
    hapusDivisi,
    // admin — jabatan
    daftarJabatan,
    simpanJabatan,
    ubahJabatan,
    hapusJabatan,
    // admin — pengurus
    daftarPengurus,
    simpanPengurus,
    ubahPengurus,
    hapusPengurus,
  }
}
