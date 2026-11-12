const KUNCI = 'ukuran-artikel'
const LANGKAH = 2
const MIN = -4
const MAKS = 8

function batasi(nilai: number) {
  return Math.min(MAKS, Math.max(MIN, nilai))
}

export function useUkuranArtikel() {
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
    }
  }

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
