import type { PengurusPublik, StrukturPublik } from '~/types/api'

// Satu kotak pada bagan
export interface SimpulBagan {
  key: string
  type: 'inti' | 'divisi' | 'anggota'
  styleClass?: string
  data: {
    pengurus?: PengurusPublik[]
    nama?: string
    jumlah?: number
    koordinator?: PengurusPublik
  }
  children?: SimpulBagan[]
}

// Data bagan struktur
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
        const [koordinator, ...lainnya] = divisi.pengurus

        return {
          key: `divisi-${divisi.id}`,
          type: 'divisi',
          data: {
            nama: divisi.nama_divisi,
            jumlah: divisi.pengurus.length,
            koordinator,
          },
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

  const ciut = ref<Record<string, boolean>>({})

  function tutupSemua() {
    ciut.value = Object.fromEntries(
      (simpul.value?.children ?? []).map((anak) => [anak.key, true]),
    )
  }

  function bukaSemua() {
    ciut.value = {}
  }

  watch(simpul, tutupSemua, { immediate: true })

  return { simpul, ciut, bukaSemua, tutupSemua }
}