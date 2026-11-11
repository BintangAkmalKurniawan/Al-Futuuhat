import type { Komentar, ResponsHalaman } from '~/types/api'

/**
 * Forum diskusi materi (F9): daftar, balasan, kirim komentar/balasan, dan hapus.
 */
export function useKomentar() {
  const api = useApi()

  const daftar = (materiId: number | string, page = 1) =>
    api.get<ResponsHalaman<Komentar>>(`/materi/${materiId}/komentar`, {
      query: { page },
    })

  /**
   * Balasan satu komentar induk (KP-14.5). Daftar komentar tidak lagi memuat
   * balasan, jadi permintaan ini dikirim saat pembaca membukanya.
   */
  const balasan = (komentarId: number | string) =>
    api.get<ResponsHalaman<Komentar>>(`/komentar/${komentarId}/balasan`)

  /**
   * Komentar dikirim sebagai multipart karena gambar opsional.
   * Minimal salah satu dari teks atau gambar harus ada.
   */
  const kirim = (
    materiId: number | string,
    data: { isi?: string; gambar?: File | null; parent_id?: number | null },
  ) => {
    const formulir = new FormData()

    if (data.isi) formulir.append('isi', data.isi)
    if (data.gambar) formulir.append('gambar', data.gambar)
    if (data.parent_id) formulir.append('parent_id', String(data.parent_id))

    return api.post<{ message: string; data: Komentar }>(
      `/materi/${materiId}/komentar`,
      formulir,
    )
  }

  const hapus = (id: number) => api.hapus<{ message: string }>(`/komentar/${id}`)

  return { daftar, balasan, kirim, hapus }
}