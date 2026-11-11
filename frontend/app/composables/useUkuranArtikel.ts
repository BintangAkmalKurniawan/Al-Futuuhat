/**
 * Ukuran tulisan isi artikel yang dapat diatur pembaca (tombol A− / A+).
 * Pilihan disimpan di localStorage agar tetap dipakai di artikel lain;
 * bila localStorage tidak tersedia, ukuran bawaan tetap dipakai.
 */
const KUNCI = 'ukuran-artikel'
const LANGKAH = 2 // px per klik
const MIN = -4
const MAKS = 8

function batasi(nilai: number) {
  return Math.min(MAKS, Math.max(MIN, nilai))
}

export function useUkuranArtikel() {
  // Selisih dari ukuran bawaan (20px desktop, 18px ponsel), mis. +2 → 22px.
  const selisih = useState('ukuran-artikel', () => {
    try {
      const tersimpan = Number(localStorage.getItem(KUNCI) ?? 0)
      return Number.isFinite(tersimpan) ? batasi(tersimpan) : 0
    } catch {
      return 0
    }
  })

  function simpan(nilai: number) {
    selisih.value = batasi(nilai)

    try {
      localStorage.setItem(KUNCI, String(selisih.value))
    } catch {
      // Abaikan: ukuran tetap berlaku sampai halaman dimuat ulang.
    }
  }

  // Menimpa token ukuran .prosa (main.css) hanya di dalam pembungkus artikel.
  const gaya = computed(() => ({
    '--text-badan': `${20 + selisih.value}px`,
    '--text-badan-hp': `${18 + selisih.value}px`,
  }))

  return {
    gaya,
    perkecil: () => simpan(selisih.value - LANGKAH),
    perbesar: () => simpan(selisih.value + LANGKAH),
    bisaPerkecil: computed(() => selisih.value > MIN),
    bisaPerbesar: computed(() => selisih.value < MAKS),
  }
}
