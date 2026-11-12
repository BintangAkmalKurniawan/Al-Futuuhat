<template>
  <!-- Badan editor -->
  <form novalidate class="mx-auto w-full max-w-baca" @submit.prevent="kirim">
    <!-- Chip status materi -->
    <div v-if="status" class="flex flex-wrap items-center gap-3">
      <StatusChip :status="status" :versi="versi" />
    </div>

    <div class="pb-6">
      <AppAlert v-if="galatUmum" variant="bahaya" title="Belum berhasil disimpan" class="mt-6">
        {{ galatUmum }}
      </AppAlert>

      <!-- Pemberitahuan dari halaman induk -->
      <slot name="pemberitahuan" />

      <div class="pt-6 lg:pt-8">
        <label for="judul-materi" class="sr-only">Judul materi</label>
        <textarea
          id="judul-materi"
          ref="judulEl"
          v-model="judul"
          rows="1"
          placeholder="Judul"
          :aria-invalid="galatJudul ? 'true' : undefined"
          aria-describedby="judul-materi-galat"
          class="block w-full resize-none overflow-hidden bg-transparent font-serif text-judul-hp font-bold tracking-[-0.022em] text-teks-kuat placeholder:text-teks-redup focus:outline-none disabled:cursor-not-allowed disabled:text-teks-lembut lg:text-judul"
        />
        <p v-if="galatJudul" id="judul-materi-galat" class="pesan-galat" role="alert">
          {{ galatJudul }}
        </p>

        <label for="ringkasan-materi" class="sr-only">Ringkasan</label>
        <textarea
          id="ringkasan-materi"
          ref="ringkasanEl"
          v-model="ringkasan"
          rows="2"
          placeholder="Tulis ringkasan singkat (boleh dikosongkan)..."
          :aria-invalid="galatRingkasan ? 'true' : undefined"
          aria-describedby="ringkasan-materi-galat"
          class="mt-4 block w-full resize-none overflow-hidden bg-transparent font-serif text-dek text-teks-lembut placeholder:text-teks-redup focus:outline-none disabled:cursor-not-allowed disabled:text-teks-redup"
        />
        <p v-if="galatRingkasan" id="ringkasan-materi-galat" class="pesan-galat" role="alert">
          {{ galatRingkasan }}
        </p>
      </div>

      <!-- Sampul artikel -->
      <div class="mt-6">
        <IsianSampul
          v-model:path="sampulPath"
          v-model:url="sampulUrl"
          :error="galatSampul"
        />
      </div>

      <!-- Kategori materi -->
      <div class="mt-6">
        <PemilihKategori
          v-model="terpilihKategori"
          :daftar="daftarKategori"
          :memuat="memuatKategori"
          :galat-muat="galatMuatKategori"
          :error="galatKategori"
          @muat-ulang="muatKategori"
        />
      </div>

      <!-- Petunjuk visibilitas materi -->
      <p class="mt-6 text-right text-[13px] text-teks-redup">
        Begitu disimpan, semua anggota bisa langsung membacanya
      </p>

      <div class="mt-4 border-t border-garis" />

      <div class="pt-6">
        <ClientOnly>
          <EditorQuill
            v-model:konten="konten"
            v-model:html="html"
            placeholder="Tulis isi materi di sini..."
            :error="galatEditor"
          />

          <template #fallback>
            <p class="py-16 text-center text-[13px] text-teks-redup">Menyiapkan editor...</p>
          </template>
        </ClientOnly>
      </div>

      <div v-if="tampilkanCatatan" class="mt-8 border-t border-garis pt-6">
        <AppInput
          v-model="catatan"
          label="Catatan perubahan"
          placeholder="Misalnya: memperbaiki bagian pendahuluan"
          hint="Opsional. Hanya kamu dan admin yang bisa melihatnya."
          :error="galatCatatan"
        />
      </div>
    </div>

    <div class="decide">
      <span class="text-[13px] text-teks-redup">{{ teksStatus }}</span>
      <span class="tumbuh" />
      <slot name="aksi" />
    </div>
  </form>
</template>

<script setup lang="ts">
import type { Kategori, KontenQuill, PayloadMateri, StatusMateri, StatusRevisi } from '~/types/api'

interface GalatIsian {
  judul?: string
  ringkasan?: string
  sampul?: string
  kategori?: string
  konten?: string
  catatan_revisi?: string
}

interface Props {
  judulAwal?: string
  ringkasanAwal?: string | null
  sampulAwalPath?: string | null
  sampulAwalUrl?: string | null
  kategoriAwal?: number[]
  kontenAwal?: KontenQuill | null
  htmlAwal?: string | null
  catatanAwal?: string | null
  tampilkanCatatan?: boolean
  status?: StatusMateri | StatusRevisi | null
  versi?: number | null
  keterangan?: string
  memuat?: boolean
  galatUmum?: string
  galatIsian?: GalatIsian
}

const props = withDefaults(defineProps<Props>(), {
  judulAwal: '',
  ringkasanAwal: null,
  sampulAwalPath: null,
  sampulAwalUrl: null,
  kategoriAwal: () => [],
  kontenAwal: null,
  htmlAwal: null,
  catatanAwal: null,
  tampilkanCatatan: false,
  status: null,
  versi: null,
  keterangan: '',
  memuat: false,
  galatUmum: '',
})

const emit = defineEmits<{ simpan: [payload: PayloadMateri] }>()

const judul = ref(props.judulAwal)
const ringkasan = ref(props.ringkasanAwal ?? '')
const catatan = ref(props.catatanAwal ?? '')
const sampulPath = ref<string | null>(props.sampulAwalPath)
const sampulUrl = ref<string | null>(props.sampulAwalUrl)
const konten = ref<KontenQuill>(props.kontenAwal ?? { ops: [] })
const html = ref(props.htmlAwal ?? '')
const terpilihKategori = ref<number[]>([...props.kategoriAwal])

const galatJudulKlien = ref('')
const galatKategoriKlien = ref('')
const galatEditorKlien = ref('')

const galatJudul = computed(() => props.galatIsian?.judul || galatJudulKlien.value)
const galatRingkasan = computed(() => props.galatIsian?.ringkasan ?? '')
const galatSampul = computed(() => props.galatIsian?.sampul ?? '')
const galatKategori = computed(() => props.galatIsian?.kategori || galatKategoriKlien.value)
const galatEditor = computed(() => props.galatIsian?.konten || galatEditorKlien.value)
const galatCatatan = computed(() => props.galatIsian?.catatan_revisi ?? '')

const { daftar: daftarKategoriApi } = useKategori()

const daftarKategori = ref<Kategori[]>([])
const memuatKategori = ref(true)
const galatMuatKategori = ref('')

async function muatKategori() {
  memuatKategori.value = true
  galatMuatKategori.value = ''

  try {
    daftarKategori.value = (await daftarKategoriApi()).data
  } catch (galatServer) {
    galatMuatKategori.value = pesanGalat(galatServer, 'Daftar kategori belum bisa dimuat.')
  } finally {
    memuatKategori.value = false
  }
}

onMounted(muatKategori)

const kotor = ref(false)

watch(
  [judul, ringkasan, sampulPath, terpilihKategori, konten, html, catatan],
  () => {
    kotor.value = true
  },
  { deep: true },
)

const statusSimpan = computed<'tersimpan' | 'menyimpan' | 'belum'>(() => {
  if (props.memuat) return 'menyimpan'
  return kotor.value ? 'belum' : 'tersimpan'
})

const teksStatus = computed(() => {
  const teks = {
    tersimpan: 'Sudah disimpan',
    menyimpan: 'Menyimpan...',
    belum: 'Ada perubahan yang belum disimpan',
  }

  const keadaan = teks[statusSimpan.value]

  return props.keterangan ? `${props.keterangan} · ${keadaan}` : keadaan
})

// Tandai tersimpan
function tandaiTersimpan() {
  kotor.value = false
}

const judulEl = ref<HTMLTextAreaElement | null>(null)
const ringkasanEl = ref<HTMLTextAreaElement | null>(null)

function sesuaikanTinggi(el: HTMLTextAreaElement | null) {
  if (!el) return

  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

function sesuaikanSemua() {
  sesuaikanTinggi(judulEl.value)
  sesuaikanTinggi(ringkasanEl.value)
}

watch([judul, ringkasan], () => nextTick(sesuaikanSemua))
onMounted(() => nextTick(sesuaikanSemua))

// Kirim

function kirim() {
  galatJudulKlien.value = ''
  galatKategoriKlien.value = ''
  galatEditorKlien.value = ''

  let sah = true

  if (judul.value.trim() === '') {
    galatJudulKlien.value = 'Judulnya belum diisi.'
    sah = false
  }

  if (terpilihKategori.value.length === 0) {
    galatKategoriKlien.value = 'Pilih minimal satu kategori ya.'
    sah = false
  }

  if (kontenKosong(konten.value)) {
    galatEditorKlien.value = 'Isi materinya masih kosong.'
    sah = false
  }

  if (!sah) return

  emit('simpan', {
    judul: judul.value.trim(),
    ringkasan: ringkasan.value.trim() || null,
    sampul: sampulPath.value,
    kategori: [...terpilihKategori.value],
    konten: konten.value,
    konten_html: rapikanSpasiHtml(html.value),
    catatan_revisi: props.tampilkanCatatan ? catatan.value.trim() || null : undefined,
  })
}

defineExpose({ kirim, tandaiTersimpan })
</script>