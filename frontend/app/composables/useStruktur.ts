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

interface PayloadPengurus {
  divisi_id: number | null
  jabatan_id: number
  anggota_id: number
}

// Struktur organisasi
export function useStruktur() {
  const api = useApi()

  // Publik
  const daftarDivisi = () => api.get<{ data: Divisi[] }>('/divisi')

  // Struktur periode saat ini
  const strukturPublik = () => api.get<{ data: StrukturPublik | null }>('/publik/struktur')

  // Anggota
  const daftarPeriode = () => api.get<{ data: Periode[] }>('/periode')

  const strukturPeriode = (id: number | string) =>
    api.get<{ data: StrukturPublik }>(`/periode/${id}/struktur`)

  // Riwayat periode
  const riwayatPeriode = (id: number | string) =>
    api.get<{ data: RiwayatStruktur }>(`/periode/${id}/riwayat`)

  // Admin
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

  // Daftar pengurus
  const daftarPengurus = () =>
    api.get<{ data: Kepengurusan[]; periode: Periode | null }>('/admin/kepengurusan')

  const simpanPengurus = (data: PayloadPengurus) =>
    api.post<{ message: string; data: Kepengurusan }>('/admin/kepengurusan', data)

  const ubahPengurus = (id: number, data: PayloadPengurus) =>
    api.put<{ message: string; data: Kepengurusan }>(`/admin/kepengurusan/${id}`, data)

  const hapusPengurus = (id: number) =>
    api.hapus<{ message: string }>(`/admin/kepengurusan/${id}`)

  return {
    daftarDivisi,
    strukturPublik,
    daftarPeriode,
    strukturPeriode,
    riwayatPeriode,
    simpanDivisi,
    ubahDivisi,
    hapusDivisi,
    daftarJabatan,
    simpanJabatan,
    ubahJabatan,
    hapusJabatan,
    daftarPengurus,
    simpanPengurus,
    ubahPengurus,
    hapusPengurus,
  }
}
