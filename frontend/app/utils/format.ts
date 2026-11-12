// Format tanggal
export function formatTanggal(nilai: string | null | undefined): string {
  if (!nilai) return '—'

  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(nilai))
}

// Format tanggal dan jam
export function formatTanggalWaktu(nilai: string | null | undefined): string {
  if (!nilai) return '—'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(nilai))
}

// Inisial nama
export function inisial(nama: string | null | undefined): string {
  if (!nama) return '?'

  return nama
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((bagian) => bagian[0]?.toUpperCase() ?? '')
    .join('')
}

// Waktu relatif
export function formatWaktuRelatif(nilai: string | null | undefined): string {
  if (!nilai) return ''

  const selisihDetik = Math.round((Date.now() - new Date(nilai).getTime()) / 1000)
  const pembagi: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
  ]

  const format = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' })

  for (const [satuan, detik] of pembagi) {
    if (Math.abs(selisihDetik) >= detik) {
      return format.format(-Math.round(selisihDetik / detik), satuan)
    }
  }

  return 'baru saja'
}