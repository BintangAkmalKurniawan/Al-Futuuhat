/**
 * Pesan singkat yang muncul di pojok kanan bawah (DESIGN.md §5 Toast).
 * Dipakai untuk kabar baik ("Materi disimpan.") dan galat umum.
 *
 * Namanya sengaja "usePesanToast", bukan "useToast": @primevue/nuxt-module ikut
 * mendaftarkan useToast milik PrimeVue ke auto-import Nuxt, sehingga nama
 * generik akan tertimpa dan memicu galat "No PrimeVue Toast provided!".
 */
interface PesanToast {
  id: number
  teks: string
  jenis: 'sukses' | 'bahaya' | 'info'
}

const urutanTerakhir = { nilai: 0 }

export function usePesanToast() {
  // useState harus dipanggil di dalam fungsi (saat setup), bukan di level modul;
  // dengan kunci 'toast' semua pemanggil berbagi daftar yang sama.
  const daftar = useState<PesanToast[]>('toast', () => [])

  function tampilkan(teks: string, jenis: PesanToast['jenis'] = 'info') {
    const id = ++urutanTerakhir.nilai

    daftar.value = [...daftar.value, { id, teks, jenis }]

    // Hilang sendiri setelah 4 detik.
    setTimeout(() => tutup(id), 4000)
  }

  function tutup(id: number) {
    daftar.value = daftar.value.filter((pesan) => pesan.id !== id)
  }

  return { daftar, tampilkan, tutup }
}
