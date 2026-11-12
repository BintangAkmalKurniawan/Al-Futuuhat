import type { GalatApi } from '~/types/api'

// Data galat
export function dataGalat(galat: unknown): GalatApi | null {
  const error = galat as { data?: unknown; response?: { _data?: unknown } }
  const data = error?.data ?? error?.response?._data

  if (data !== null && typeof data === 'object') {
    return data as GalatApi
  }

  return null
}

// Pesan galat
export function pesanGalat(galat: unknown, bawaan = 'Ada kendala sebentar. Silakan coba lagi.'): string {
  return dataGalat(galat)?.message || bawaan
}

// Galat validasi
export function galatValidasi(galat: unknown): Record<string, string[]> {
  return dataGalat(galat)?.errors ?? {}
}

// Galat satu isian
export function galatIsian(galat: unknown, isian: string): string {
  return galatValidasi(galat)[isian]?.[0] ?? ''
}