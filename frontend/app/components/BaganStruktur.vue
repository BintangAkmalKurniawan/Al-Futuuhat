<template>
  <section>
    <!-- Kepala bagan: judul + pemilih periode berbentuk pil (DESIGN.md §6.1, §6.2).
         Draf 2.5: pemilih periode hanya untuk anggota (/struktur); beranda
         pengunjung selalu menampilkan periode saat ini (KP-01.2, KP-01.3). -->
    <div class="flex flex-wrap items-end gap-4">
      <div class="min-w-0 flex-1">
        <h1 v-if="varian === 'halaman'" class="font-serif text-halaman font-bold text-teks-kuat">
          {{ judul }}
        </h1>
        <h2 v-else class="text-[15px] font-semibold text-teks-kuat">{{ judul }}</h2>

        <p v-if="varian === 'halaman'" class="mt-3 text-sm text-teks-lembut">{{ deskripsi }}</p>
      </div>

      <label v-if="!publik && periode.length > 0" class="relative inline-flex shrink-0 items-center">
        <span class="sr-only">Pilih periode kepengurusan</span>
        <select
          v-model="periodeId"
          class="h-9 appearance-none rounded-full border border-garis-kuat bg-transparent pr-9 pl-4 text-sm text-teks"
          @change="pilihDariPilihan"
        >
          <option v-for="item in periode" :key="item.id" :value="item.id">
            {{ item.nama_periode }}{{ item.is_aktif ? ' (saat ini)' : '' }}
          </option>
        </select>
        <ChevronDown
          class="pointer-events-none absolute right-3 h-4 w-4 text-teks-lembut"
          aria-hidden="true"
        />
      </label>
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Struktur belum bisa dimuat" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk bagan. -->
    <div v-else-if="memuat || memuatStruktur" class="mt-6 animate-pulse">
      <div class="h-44 w-full rounded-xl bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="periode.length === 0"
      title="Struktur organisasi belum ada."
      description="Nanti akan muncul di sini setelah pengurus disusun."
    />

    <AppEmptyState
      v-else-if="kosong"
      title="Belum ada susunan pengurus untuk periode ini."
      description="Susunan pengurus akan tampil setelah admin menambahkannya."
    />

    <div v-else-if="simpul" class="bagan-wrap mt-6">
      <!-- Petunjuk dan tombol buka/tutup; hanya di halaman /struktur (§9.4). -->
      <div v-if="tampilTools" class="bagan-tools">
        <span class="bagan-hint">
          <Network class="h-4 w-4 shrink-0" aria-hidden="true" />
          Klik tombol di bawah kotak divisi untuk membuka atau menutup anggotanya.
        </span>
        <span class="flex-1" />
        <AppButton variant="hantu" size="sm" @click="bukaSemua">Buka semua</AppButton>
        <AppButton variant="hantu" size="sm" @click="tutupSemua">Tutup semua</AppButton>
      </div>

      <div ref="gulir" class="bagan-scroll">
        <OrganizationChart
          v-model:collapsed-keys="ciut"
          :value="simpul"
          collapsible
          :pt="{ nodeToggleButton: { 'aria-label': 'Buka atau tutup anggota divisi' } }"
        >
          <!-- Kotak pengurus inti (akar bagan). -->
          <template #inti="{ node }">
            <div class="w-[270px]">
              <p
                class="mb-2.5 text-[11px] font-medium tracking-[.08em] text-aksen uppercase"
              >
                Pengurus inti
              </p>

              <p v-if="!node.data.pengurus?.length" class="text-[13px] text-teks-redup">
                Belum ada pengurus inti.
              </p>

              <div
                v-for="orang in node.data.pengurus"
                :key="orang.nama"
                class="flex items-center gap-2.5 py-1.5"
              >
                <AvatarInisial :nama="orang.nama" ukuran="mini" />
                <div class="min-w-0">
                  <p class="text-[13px] leading-tight font-medium text-teks-kuat">
                    {{ orang.nama }}
                  </p>
                  <p class="text-xs text-teks-redup">{{ orang.jabatan }}</p>
                </div>
              </div>
            </div>
          </template>

          <!-- Kotak divisi (cabang). -->
          <template #divisi="{ node }">
            <div class="w-[164px]">
              <p class="font-serif text-[17px] font-bold tracking-[-.01em] text-teks-kuat">
                {{ node.data.nama }}
              </p>

              <p v-if="node.data.jumlah" class="mt-0.5 text-xs text-teks-redup">
                {{ node.data.jumlah }} orang
              </p>
              <p v-else class="mt-0.5 text-xs text-teks-redup">Belum ada pengurus</p>

              <div
                v-if="node.data.koordinator"
                class="mt-2.5 flex items-center gap-2 border-t border-garis pt-2.5"
              >
                <AvatarInisial :nama="node.data.koordinator.nama" ukuran="kecil" />
                <div class="min-w-0">
                  <p class="text-[12.5px] leading-tight font-medium text-teks">
                    {{ node.data.koordinator.nama }}
                  </p>
                  <p class="text-[11px] text-teks-redup">Koordinator</p>
                </div>
              </div>
            </div>
          </template>

          <!-- Kotak daftar anggota divisi (daun). -->
          <template #anggota="{ node }">
            <div class="w-[164px]">
              <div
                v-for="(orang, urutan) in node.data.pengurus"
                :key="orang.nama"
                class="flex items-center gap-2 py-1.5"
                :class="{ 'border-t border-garis-kuat': urutan > 0 }"
              >
                <AvatarInisial :nama="orang.nama" ukuran="mini" />
                <div class="min-w-0">
                  <p class="text-[12.5px] leading-tight font-medium text-teks">{{ orang.nama }}</p>
                  <p class="text-[11px] text-teks-redup">{{ orang.jabatan }}</p>
                </div>
              </div>
            </div>
          </template>
        </OrganizationChart>
      </div>

      <div class="bagan-legend">
        <span>Periode {{ namaPeriode }}</span>
        <span aria-hidden="true">·</span>
        <span>Urutan sesuai jabatan</span>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ChevronDown, Network } from '@lucide/vue'
// Komponen PrimeVue diimpor manual karena autoImport dimatikan (DESIGN.md §9.2).
import OrganizationChart from 'primevue/organizationchart'
import type { Periode, StrukturPublik } from '~/types/api'

interface Props {
  /**
   * `halaman` untuk /struktur (anggota, dapat memilih periode lama),
   * `ringkas` untuk beranda (pengunjung, hanya periode saat ini).
   */
  varian?: 'halaman' | 'ringkas'
  /** Tombol "Buka semua"/"Tutup semua" hanya tampil di /struktur (§9.4). */
  tampilTools?: boolean
  judul?: string
  deskripsi?: string
}

const props = withDefaults(defineProps<Props>(), {
  varian: 'halaman',
  tampilTools: false,
  judul: 'Struktur kepengurusan',
  deskripsi: 'Bagan pengurus per periode. Pengurus inti di puncak, divisi di bawahnya.',
})

// Halaman memakai periode terpilih untuk menampilkan riwayatnya (KP-07.8).
const emit = defineEmits<{ (e: 'periode', periode: Periode): void }>()

const { daftarPeriode, strukturPeriode, strukturPublik } = useStruktur()

// Beranda = pengunjung: hanya periode saat ini lewat endpoint publik.
const publik = computed(() => props.varian === 'ringkas')

const route = useRoute()

const periode = ref<Periode[]>([])
const periodeId = ref<number | null>(null)
const struktur = ref<StrukturPublik | null>(null)

const memuat = ref(true)
const memuatStruktur = ref(false)
const galat = ref('')

// Penanda agar klik periode yang beruntun tidak menimpa hasil terbaru.
let permintaanTerakhir = 0

const { simpul, ciut, bukaSemua, tutupSemua } = useBaganStruktur(struktur)

const gulir = ref<HTMLElement | null>(null)

const kosong = computed(
  () =>
    struktur.value !== null &&
    struktur.value.inti.length === 0 &&
    struktur.value.divisi.every((grup) => grup.pengurus.length === 0),
)

const namaPeriode = computed(
  () => periode.value.find((item) => item.id === periodeId.value)?.nama_periode ?? '',
)

/** Ponsel: bagan tetap utuh dan digulir mendatar, posisi awal di tengah (§9.4). */
async function tengahkanGulir() {
  await nextTick()
  const el = gulir.value
  if (el) el.scrollLeft = (el.scrollWidth - el.clientWidth) / 2
}

async function muatPeriode() {
  memuat.value = true
  galat.value = ''

  if (publik.value) {
    await muatPublik()
    return
  }

  try {
    const respons = await daftarPeriode()
    periode.value = respons.data

    // Tautan "Pratinjau bagan" dari admin dapat memilih periode lewat ?periode=.
    const diminta = Number(route.query.periode)
    const dariTautan = respons.data.find((item) => item.id === diminta)

    // Selain itu: periode aktif tampil terpilih, bila tidak ada pakai yang pertama.
    const awal = dariTautan ?? respons.data.find((item) => item.is_aktif) ?? respons.data[0]
    if (awal) await pilihPeriode(awal.id)
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

/** Beranda: satu permintaan, struktur periode saat ini saja. */
async function muatPublik() {
  try {
    const respons = await strukturPublik()

    struktur.value = respons.data
    periode.value = respons.data ? [respons.data.periode] : []
    periodeId.value = respons.data?.periode.id ?? null
    await tengahkanGulir()
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

async function pilihPeriode(id: number) {
  const nomor = ++permintaanTerakhir

  periodeId.value = id
  memuatStruktur.value = true
  galat.value = ''

  try {
    const respons = await strukturPeriode(id)
    if (nomor !== permintaanTerakhir) return

    struktur.value = respons.data
    emit('periode', respons.data.periode)
    await tengahkanGulir()
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) {
      struktur.value = null
      galat.value = pesanGalat(galatServer)
    }
  } finally {
    if (nomor === permintaanTerakhir) memuatStruktur.value = false
  }
}

// Memilih periode lain lewat pemilih periode. Tanpa watcher agar permintaan
// tidak terkirim dua kali saat halaman pertama dimuat.
function pilihDariPilihan() {
  if (periodeId.value !== null) pilihPeriode(periodeId.value).catch(() => {})
}

onMounted(muatPeriode)
</script>