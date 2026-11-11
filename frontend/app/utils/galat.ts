import type { GalatApi } from '~/types/api'

/**
 * Mengambil isi respons galat Laravel dari FetchError Nuxt/ofetch.
 * Mengembalikan null bila galat bukan berasal dari respons HTTP.
 */
export function dataGalat(galat: unknown): GalatApi | null {
  const error = galat as { data?: unknown; response?: { _data?: unknown } }
  const data = error?.data ?? error?.response?._data

  if (data !== null && typeof data === 'object') {
    return data as GalatApi
  }

  return null
}

/** Pesan galat yang layak ditampilkan ke pengguna. */
export function pesanGalat(galat: unknown, bawaan = 'Ada kendala sebentar. Silakan coba lagi.'): string {
  return dataGalat(galat)?.message || bawaan
}

/** Daftar galat validasi 422 per nama isian. */
export function galatValidasi(galat: unknown): Record<string, string[]> {
  return dataGalat(galat)?.errors ?? {}
}

/** Pesan galat pertama untuk satu isian (kosong bila tidak ada). */
export function galatIsian(galat: unknown, isian: string): string {
  return galatValidasi(galat)[isian]?.[0] ?? ''
}