import type { KontenQuill } from '~/types/api'

// Cek konten kosong
export function kontenKosong(konten: KontenQuill | null | undefined): boolean {
  const ops = (konten as { ops?: Array<Record<string, unknown>> } | null | undefined)?.ops

  if (!Array.isArray(ops) || ops.length === 0) return true

  return ops.every((op) => {
    const sisipan = op.insert

    if (typeof sisipan === 'string') return sisipan.trim() === ''

    return sisipan === null || sisipan === undefined
  })
}

// Teks dari HTML
export function teksDariHtml(html: string | null | undefined): string {
  return (html ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
}

// Rapikan spasi HTML
export function rapikanSpasiHtml(html: string | null | undefined): string {
  const spasiTakTerpisah = String.fromCharCode(160)

  return (html ?? '').replace(/&nbsp;/gi, ' ').replaceAll(spasiTakTerpisah, ' ')
}

// Perkiraan waktu baca
export function menitBaca(html: string | null | undefined): number | null {
  const kata = teksDariHtml(html).split(/\s+/).filter(Boolean).length

  return kata === 0 ? null : Math.max(1, Math.ceil(kata / 200))
}