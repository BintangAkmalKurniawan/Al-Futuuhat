import type { KontenQuill } from '~/types/api'

/**
 * Delta Quill dianggap kosong bila tidak ada teks dan tidak ada sisipan
 * (gambar/video). Delta kosong bawaan Quill hanya berisi satu baris baru.
 */
export function kontenKosong(konten: KontenQuill | null | undefined): boolean {
  const ops = (konten as { ops?: Array<Record<string, unknown>> } | null | undefined)?.ops

  if (!Array.isArray(ops) || ops.length === 0) return true

  return ops.every((op) => {
    const sisipan = op.insert

    if (typeof sisipan === 'string') return sisipan.trim() === ''

    return sisipan === null || sisipan === undefined
  })
}

/** Teks polos singkat dari HTML (untuk pratinjau daftar bila perlu). */
export function teksDariHtml(html: string | null | undefined): string {
  return (html ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
}

/**
 * Quill 2 menulis setiap spasi di `getSemanticHTML()` sebagai `&nbsp;`
 * (node_modules/quill/core/editor.js:298). Di dalam editor hal itu tidak
 * masalah karena `.ql-editor` memakai word-wrap: break-word, tetapi di luar
 * editor spasi tak-terpisah membuat paragraf menjadi satu "kata" panjang yang
 * melewati kolom baca. Kembalikan ke spasi biasa sebelum HTML disimpan atau
 * ditampilkan, termasuk spasi tak-terpisah (U+00A0) yang sudah telanjur
 * tersimpan di database.
 */
export function rapikanSpasiHtml(html: string | null | undefined): string {
  // String.fromCharCode(160) = U+00A0; ditulis begini agar tidak tampak
  // seperti spasi biasa saat kode dibaca.
  const spasiTakTerpisah = String.fromCharCode(160)

  return (html ?? '').replace(/&nbsp;/gi, ' ').replaceAll(spasiTakTerpisah, ' ')
}

/**
 * Perkiraan waktu baca (200 kata per menit) dari HTML isi materi.
 * Dipakai halaman baca; daftar memakai `menit_baca` dari API (DESIGN.md §9).
 */
export function menitBaca(html: string | null | undefined): number | null {
  const kata = teksDariHtml(html).split(/\s+/).filter(Boolean).length

  return kata === 0 ? null : Math.max(1, Math.ceil(kata / 200))
}