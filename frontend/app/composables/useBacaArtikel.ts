// Dengarkan artikel
export type StatusBaca = 'diam' | 'membaca' | 'jeda'

interface Potongan {
  teks: string
  nada: number
  tempo: number
}

const NADA_DASAR = 0.9
const TEMPO_DASAR = 0.95

export function useBacaArtikel() {
  const didukung = import.meta.client && 'speechSynthesis' in window
  const status = ref<StatusBaca>('diam')
  const kecepatan = ref(1)

  let suara: SpeechSynthesisVoice | null = null
  let sesi = 0

  function pilihSuara() {
    if (!didukung) return

    const semua = window.speechSynthesis.getVoices()
    const indonesia = semua.filter((v) => v.lang.toLowerCase().startsWith('id'))

    suara =
      indonesia.find((v) => /ardi|andika|male|pria|laki/i.test(v.name)) ??
      indonesia[0] ??
      null
  }

  if (didukung) {
    pilihSuara()
    window.speechSynthesis.addEventListener('voiceschanged', pilihSuara)
  }

  // Pecah artikel menjadi kalimat
  function potong(judul: string, html: string): Potongan[] {
    const hasil: Potongan[] = []
    const tambah = (teks: string, nada: number, tempo: number) => {
      for (const kalimat of teks.split(/(?<=[.!?])\s+/)) {
        const bersih = kalimat.trim()
        if (bersih) hasil.push({ teks: bersih, nada, tempo })
      }
    }

    tambah(judul, NADA_DASAR - 0.05, TEMPO_DASAR - 0.05)

    const dokumen = new DOMParser().parseFromString(html, 'text/html')
    const blok = dokumen.body.querySelectorAll('h2, h3, p, li, blockquote')

    for (const el of Array.from(blok)) {
      if (el.tagName === 'P' && el.closest('blockquote')) continue

      const teks = el.textContent?.replace(/\s+/g, ' ').trim() ?? ''
      if (!teks) continue

      if (el.tagName === 'H2' || el.tagName === 'H3') tambah(teks, NADA_DASAR - 0.05, TEMPO_DASAR - 0.08)
      else if (el.tagName === 'BLOCKQUOTE') tambah(teks, NADA_DASAR, TEMPO_DASAR - 0.07)
      else tambah(teks, NADA_DASAR, TEMPO_DASAR)
    }

    return hasil
  }

  function putar(judul: string, html: string) {
    if (!didukung) return

    berhenti()
    const sesiIni = ++sesi
    const daftar = potong(judul, html)
    if (!daftar.length) return

    daftar.forEach((p, i) => {
      const ucapan = new SpeechSynthesisUtterance(p.teks)
      ucapan.lang = 'id-ID'
      if (suara) ucapan.voice = suara
      ucapan.pitch = p.nada
      ucapan.rate = p.tempo * kecepatan.value

      if (i === daftar.length - 1) {
        ucapan.onend = () => {
          if (sesiIni === sesi) status.value = 'diam'
        }
      }

      window.speechSynthesis.speak(ucapan)
    })

    status.value = 'membaca'
  }

  function jeda() {
    if (!didukung) return
    window.speechSynthesis.pause()
    status.value = 'jeda'
  }

  function lanjut() {
    if (!didukung) return
    window.speechSynthesis.resume()
    status.value = 'membaca'
  }

  function berhenti() {
    if (!didukung) return
    sesi++
    window.speechSynthesis.cancel()
    status.value = 'diam'
  }

  onBeforeUnmount(() => {
    berhenti()
    if (didukung) window.speechSynthesis.removeEventListener('voiceschanged', pilihSuara)
  })

  return { didukung, status, kecepatan, putar, jeda, lanjut, berhenti }
}
