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

// Materi milik anggota
export function useMateri() {
  const api = useApi()

  const materiSaya = (params: { q?: string; status?: string; page?: number } = {}) =>
    api.get<ResponsHalaman<MateriRingkas>>('/materi-saya', { query: params })

  const arsip = (params: {
    q?: string
    penulis?: string
    status?: string
    kategori?: string
    page?: number
  } = {}) => api.get<ResponsHalaman<MateriRingkas>>('/materi', { query: params })

  // Halaman baca anggota
  const detail = (slug: string) => api.get<ResponsData<MateriDetail>>(`/materi/${slug}`)

  // Isi satu versi
  const versi = (materiId: number | string, revisiId: number | string) =>
    api.get<ResponsData<Revisi>>(`/materi/${materiId}/riwayat/${revisiId}`)

  // Publikasi untuk pengunjung
  const publikArsip = (params: { q?: string; kategori?: string; page?: number } = {}) =>
    api.get<ResponsHalaman<MateriPublikRingkas>>('/publik/materi', { query: params })

  const publikDetail = (slug: string) =>
    api.get<ResponsData<MateriPublikDetail>>(`/publik/materi/${slug}`)

  const ajukan = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/ajukan`)

  // Batal ajukan
  const batalAjukan = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/batal-ajukan`)

  const buatRevisi = (id: number | string) =>
    api.post<{ message: string; data: DataEditor }>(`/materi/${id}/revisi`)

  // Ajukan penarikan
  const tarik = (id: number | string, alasan: string) =>
    api.post<{ message: string; data: Penarikan }>(`/materi/${id}/tarik`, { alasan })

  // Batal tarik
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

  // Unggah gambar Quill
  const unggahGambar = (gambar: File) => {
    const data = new FormData()
    data.append('gambar', gambar)

    return api.post<ResponsUnggahGambar>('/unggah-gambar', data)
  }

  // Unggah gambar sampul materi
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