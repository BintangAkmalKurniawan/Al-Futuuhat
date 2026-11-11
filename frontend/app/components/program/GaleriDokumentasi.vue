<template>
  <!-- Galeri dokumentasi satu program (KP-21.6): penyaring Semua/Foto/Video,
       grid gambar kecil, paginasi, dan penampil. Dipakai halaman publik dan admin. -->
  <div>
    <FilterSegmen v-model="jenis" :opsi="opsiJenis" label="Saring dokumentasi" />

    <AppAlert v-if="galat" variant="bahaya" title="Dokumentasi belum bisa dimuat" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk grid gambar. -->
    <div v-else-if="memuat" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <div v-for="n in 8" :key="n" class="aspect-[4/3] animate-pulse rounded-sm bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="daftar.length === 0"
      :title="jenis === '' ? 'Belum ada dokumentasi.' : `Belum ada ${jenis === 'foto' ? 'foto' : 'video'}.`"
      :description="bisaHapus ? 'Unggah foto atau video kegiatan pada formulir di atas.' : undefined"
    />

    <template v-else>
      <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <li v-for="item in daftar" :key="item.id" class="group relative">
          <button
            type="button"
            class="relative block aspect-[4/3] w-full overflow-hidden rounded-sm bg-permukaan ring-1 ring-garis transition-colors duration-200 hover:ring-garis-kuat"
            :aria-label="labelButir(item)"
            @click="buka(item)"
          >
            <img
              v-if="item.thumbnail"
              :src="item.thumbnail"
              alt=""
              loading="lazy"
              class="h-full w-full object-cover transition-transform duration-300 ease-halus group-hover:scale-[1.02]"
            />
            <!-- Video MP4 tidak punya gambar kecil dari server: peramban diminta
                 menampilkan bingkai di detik 0,5 (#t=0.5). preload="metadata"
                 membuat peramban hanya mengunduh bagian awal berkas, bukan seluruhnya. -->
            <video
              v-else-if="item.jenis === 'video'"
              :src="`${item.url}#t=0.5`"
              preload="metadata"
              muted
              playsinline
              tabindex="-1"
              aria-hidden="true"
              class="pointer-events-none h-full w-full object-cover transition-transform duration-300 ease-halus group-hover:scale-[1.02]"
            />
            <span v-else class="grid h-full w-full place-items-center text-teks-redup">
              <Film class="h-7 w-7" aria-hidden="true" />
            </span>

            <!-- Tanda putar untuk video MP4 dan YouTube. -->
            <span
              v-if="item.jenis !== 'foto'"
              class="absolute inset-0 grid place-items-center bg-kanvas/25"
              aria-hidden="true"
            >
              <span class="grid h-11 w-11 place-items-center rounded-full bg-kanvas/80 text-teks-kuat">
                <Play class="ml-0.5 h-5 w-5" />
              </span>
            </span>
          </button>

          <p v-if="item.keterangan" class="mt-2 line-clamp-2 text-[13px] text-teks-lembut">
            {{ item.keterangan }}
          </p>

          <!-- Khusus admin: hapus satu dokumentasi (KP-21.4). -->
          <button
            v-if="bisaHapus"
            type="button"
            class="absolute top-2 right-2 grid h-8 w-8 place-items-center rounded-full bg-kanvas/85 text-bahaya transition-colors duration-200 hover:bg-angkat"
            :aria-label="`Hapus ${labelButir(item).toLowerCase()}`"
            @click="mintaHapus(item)"
          >
            <Trash2 class="h-4 w-4" aria-hidden="true" />
          </button>
        </li>
      </ul>

      <div class="pt-8">
        <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
      </div>
    </template>

    <PenampilDokumentasi v-model:open="penampilTerbuka" :item="terpilih" />

    <AppModal
      v-if="bisaHapus"
      v-model:open="modalHapus"
      title="Hapus dokumentasi?"
      deskripsi="Foto atau video ini akan terhapus dan tidak dapat dikembalikan."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">{{ galatHapus }}</AppAlert>
      <p v-else class="text-sm text-teks-lembut">
        {{ akanDihapus?.keterangan || (akanDihapus ? labelButir(akanDihapus) : '') }}
      </p>

      <template #footer>
        <AppButton variant="hantu" :disabled="menghapus" @click="modalHapus = false">Batal</AppButton>
        <AppButton variant="bahaya" :loading="menghapus" @click="hapusSekarang">Ya, hapus</AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { Film, Play, Trash2 } from '@lucide/vue'
import type { DokumentasiProgram, MetaHalaman } from '~/types/api'

const props = withDefaults(
  defineProps<{
    slug: string
    jumlahFoto?: number
    jumlahVideo?: number
    /** Admin: tampilkan tombol hapus pada setiap butir. */
    bisaHapus?: boolean
  }>(),
  { jumlahFoto: undefined, jumlahVideo: undefined, bisaHapus: false },
)

const emit = defineEmits<{ (e: 'berubah'): void }>()

const { daftarDokumentasi, hapusDokumentasi } = useProgram()
const { tampilkan } = usePesanToast()

const jenis = ref('')
const halaman = ref(1)
const daftar = ref<DokumentasiProgram[]>([])
const meta = ref<MetaHalaman | null>(null)
const memuat = ref(true)
const galat = ref('')

const penampilTerbuka = ref(false)
const terpilih = ref<DokumentasiProgram | null>(null)

const modalHapus = ref(false)
const akanDihapus = ref<DokumentasiProgram | null>(null)
const galatHapus = ref('')
const menghapus = ref(false)

const opsiJenis = computed(() => [
  { nilai: '', label: 'Semua' },
  { nilai: 'foto', label: 'Foto', jumlah: props.jumlahFoto },
  { nilai: 'video', label: 'Video', jumlah: props.jumlahVideo },
])

function labelButir(item: DokumentasiProgram) {
  if (item.jenis === 'foto') return 'Lihat foto'
  return item.jenis === 'youtube' ? 'Putar video YouTube' : 'Putar video'
}

let nomorPermintaan = 0

async function muat() {
  const nomor = ++nomorPermintaan
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarDokumentasi(props.slug, {
      jenis: jenis.value || undefined,
      page: halaman.value,
    })

    if (nomor !== nomorPermintaan) return

    // Halaman terakhir kosong setelah butir terakhirnya dihapus: mundur satu halaman.
    if (respons.data.length === 0 && halaman.value > 1) {
      halaman.value -= 1
      return
    }

    daftar.value = respons.data
    meta.value = respons.meta
  } catch (galatServer) {
    if (nomor === nomorPermintaan) galat.value = pesanGalat(galatServer)
  } finally {
    if (nomor === nomorPermintaan) memuat.value = false
  }
}

function buka(item: DokumentasiProgram) {
  terpilih.value = item
  penampilTerbuka.value = true
}

function mintaHapus(item: DokumentasiProgram) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  menghapus.value = true
  galatHapus.value = ''

  try {
    const respons = await hapusDokumentasi(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')
    emit('berubah')
    await muat()
  } catch (galatServer) {
    galatHapus.value = pesanGalat(galatServer)
  } finally {
    menghapus.value = false
  }
}

// Ganti penyaring → kembali ke halaman 1. Satu perubahan = satu muat ulang.
watch(jenis, () => {
  if (halaman.value === 1) muat()
  else halaman.value = 1
})
watch(halaman, muat)

onMounted(muat)

/** Dipanggil halaman admin setelah unggahan baru selesai. */
async function muatUlang() {
  if (halaman.value === 1) await muat()
  else halaman.value = 1
}

defineExpose({ muatUlang })
</script>
