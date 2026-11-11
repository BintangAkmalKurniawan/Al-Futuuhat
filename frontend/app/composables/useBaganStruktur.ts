import type { PengurusPublik, StrukturPublik } from '~/types/api'

/** Satu kotak pada bagan (bentuk data PrimeVue OrganizationChart; DESIGN.md §9.3). */
export interface SimpulBagan {
  /** Wajib unik; dipakai PrimeVue untuk mengenali tiap simpul. */
  key: string
  /** Cocok dengan nama slot di BaganStruktur.vue. */
  type: 'inti' | 'divisi' | 'anggota'
  /** Kelas tambahan pada kotak, mis. `bagan-inti` dan `bagan-anggota`. */
  styleClass?: string
  data: {
    /** Dipakai kotak pengurus inti dan kotak daftar anggota divisi. */
    pengurus?: PengurusPublik[]
    /** Dipakai kotak divisi. */
    nama?: string
    jumlah?: number
    koordinator?: PengurusPublik
  }
  children?: SimpulBagan[]
}

/**
 * Menyusun data bagan struktur organisasi (DESIGN.md §9.3):
 * akar = kotak Pengurus inti, cabang = satu kotak per divisi,
 * daun = satu kotak berisi daftar anggota divisi lainnya.
 *
 * Anggota divisi sengaja digabung dalam satu kotak agar membuka divisi
 * tidak membuat bagan makin lebar (DESIGN.md §9.4).
 */
export function useBaganStruktur(struktur: Ref<StrukturPublik | null>) {
  const simpul = computed<SimpulBagan | null>(() => {
    const data = struktur.value
    if (!data) return null

    return {
      key: 'inti',
      type: 'inti',
      styleClass: 'bagan-inti',
      data: { pengurus: data.inti },
      children: data.divisi.map((divisi) => {
        // API sudah mengurutkan pengurus menurut jabatan.urutan,
        // jadi orang pertama adalah koordinator divisi.
        const [koordinator, ...lainnya] = divisi.pengurus

        return {
          key: `divisi-${divisi.id}`,
          type: 'divisi',
          data: {
            nama: divisi.nama_divisi,
            jumlah: divisi.pengurus.length,
            koordinator,
          },
          // Divisi tanpa anggota lain tidak punya cabang, sehingga tombol
          // buka-tutupnya otomatis tidak tampil (DESIGN.md §9.4).
          children: lainnya.length
            ? [
                {
                  key: `anggota-${divisi.id}`,
                  type: 'anggota',
                  styleClass: 'bagan-anggota',
                  data: { pengurus: lainnya },
                },
              ]
            : [],
        }
      }),
    }
  })

  // Peta key → true berarti cabang itu sedang tertutup (prop collapsedKeys PrimeVue).
  const ciut = ref<Record<string, boolean>>({})

  function tutupSemua() {
    ciut.value = Object.fromEntries(
      (simpul.value?.children ?? []).map((anak) => [anak.key, true]),
    )
  }

  function bukaSemua() {
    ciut.value = {}
  }

  // Semua divisi tertutup saat pertama tampil dan setiap periode berganti (§9.4).
  watch(simpul, tutupSemua, { immediate: true })

  return { simpul, ciut, bukaSemua, tutupSemua }
}