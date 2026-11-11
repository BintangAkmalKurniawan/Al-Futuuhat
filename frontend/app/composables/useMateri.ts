import type {
  DataEditor,
  MateriDetail,
  MateriPublikDetail,
  MateriPublikRingkas,
  MateriRingkas,
  PayloadMateri,
  Penarikan,
  ResponsData,
  ResponsHalaman,
  ResponsUnggahGambar,
  ResponsUnggahSampul,
  Revisi,
} from '~/types/api'

/**
 * Materi milik anggota: daftar per status, editor, simpan/ubah/hapus (F5),
 * serta arsip dan halaman baca (F8).
 */
export function useMateri() {
  const api = useApi()

  const materiSaya = (params: { q?: string; status?: string; page?: number } = {}) =>
    api.get<ResponsHalaman<MateriRingkas>>('/materi-saya', { query: params })

  /** Arsip seluruh materi anggota (termasuk draf), dengan filter status & kategori. */
  const arsip = (params: {
    q?: string
    penulis?: string
    status?: string
    /** Slug kategori, mis. `fiqih` (KP-19.4). */
    kategori?: string
    page?: number
  } = {}) => api.get<ResponsHalaman<MateriRingkas>>('/materi', { query: params })

  /** Halaman baca anggota: revisi terakhir + riwayat + forum. */
  const detail = (slug: string) => api.get<ResponsData<MateriDetail>>(`/materi/${slug}`)

  /**
   * Isi satu versi dari riwayat (KP-13.4). Daftar riwayat hanya memuat
   * ringkasan, jadi isi versi diambil satu per satu saat versinya dibuka.
   */
  const versi = (materiId: number | string, revisiId: number | string) =>
    api.get<ResponsData<Revisi>>(`/materi/${materiId}/riwayat/${revisiId}`)

  /** Publikasi untuk pengunjung: hanya materi terbit dan versi publiknya. */
  const publikArsip = (params: { q?: string; kategori?: string; page?: number } = {}) =>
    api.get<ResponsHalaman<MateriPublikRingkas>>('/publik/materi', { query: params })

  const publikDetail = (slug: string) =>
    api.get<ResponsData<MateriPublikDetail>>(`/publik/materi/${slug}`)

  const ajukan = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/ajukan`)

  /** Batalkan pengajuan publikasi yang belum diputuskan admin (KP-10.6). */
  const batalAjukan = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/batal-ajukan`)

  const buatRevisi = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/revisi`)

  /** Ajukan penarikan materi terbit dari publik, alasan wajib (KP-20.1). */
  const tarik = (id: number | string, alasan: string) =>
    api.post<{ message: string; data: Penarikan }>(`/materi/${id}/tarik`, { alasan })

  /** Batalkan pengajuan penarikan yang belum diputuskan (KP-20.3). */
  const batalTarik = (id: number | string) =>
    api.post<{ message: string; data: Penarikan }>(`/materi/${id}/batal-tarik`)

  const dataEditor = (id: number | string) =>
    api.get<ResponsData<DataEditor>>(`/materi/${id}/edit`)

  const simpan = (data: PayloadMateri) =>
    api.post<{ message: string; data: DataEditor }>('/materi', data)

  const ubah = (id: number | string, data: PayloadMateri) =>
    api.put<{ message: string; data: DataEditor }>(`/materi/${id}`, data)

  const hapus = (id: number | string) =>
    api.hapus<{ message: string }>(`/materi/${id}`)

  /** Unggah gambar Quill; backend mengembalikan { url }. */
  const unggahGambar = (gambar: File) => {
    const data = new FormData()
    data.append('gambar', gambar)

    return api.post<ResponsUnggahGambar>('/unggah-gambar', data)
  }

  /** Unggah gambar sampul materi; backend mengembalikan { path, url } (KF-18). */
  const unggahSampul = (sampul: File) => {
    const data = new FormData()
    data.append('sampul', sampul)

    return api.post<ResponsUnggahSampul>('/unggah-sampul', data)
  }

  return {
    materiSaya,
    arsip,
    detail,
    versi,
    publikArsip,
    publikDetail,
    ajukan,
    batalAjukan,
    buatRevisi,
    tarik,
    batalTarik,
    dataEditor,
    simpan,
    ubah,
    hapus,
    unggahGambar,
    unggahSampul,
  }
}