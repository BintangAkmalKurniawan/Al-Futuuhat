// Pesan toast
interface PesanToast {
  id: number
  teks: string
  jenis: 'sukses' | 'bahaya' | 'info'
}

const urutanTerakhir = { nilai: 0 }

export function usePesanToast() {
  const daftar = useState<PesanToast[]>('toast', () => [])

  function tampilkan(teks: string, jenis: PesanToast['jenis'] = 'info') {
    const id = ++urutanTerakhir.nilai

    daftar.value = [...daftar.value, { id, teks, jenis }]

    setTimeout(() => tutup(id), 4000)
  }

  function tutup(id: number) {
    daftar.value = daftar.value.filter((pesan) => pesan.id !== id)
  }

  return { daftar, tampilkan, tutup }
}
