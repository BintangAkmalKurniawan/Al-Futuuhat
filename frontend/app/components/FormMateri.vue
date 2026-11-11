<template>
  <!-- Badan editor (DESIGN.md §6.4): judul dan ringkasan tanpa kotak, garis,
       lalu isi Quill. Header halaman berasal dari layout (hanya navigasi);
       status materi, keterangan simpan, dan tombol aksi diletakkan di badan ini. -->
  <form novalidate class="mx-auto w-full max-w-baca" @submit.prevent="kirim">
    <!-- Chip status materi; tidak tampil untuk materi baru yang belum berstatus. -->
    <div v-if="status" class="flex flex-wrap items-center gap-3">
      <StatusChip :status="status" :versi="versi" />
    </div>

    <div class="pb-6">
      <AppAlert v-if="galatUmum" variant="bahaya" title="Belum berhasil disimpan" class="mt-6">
        {{ galatUmum }}
      </AppAlert>

      <!-- Pemberitahuan dari halaman induk, mis. "Materi ini sedang terbit" (F15). -->
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

      <!-- Sampul artikel (KF-18); disimpan per versi, selalu dapat diubah (KP-18.4). -->
      <div class="mt-6">
        <IsianSampul
          v-model:path="sampulPath"
          v-model:url="sampulUrl"
          :error="galatSampul"
        />
      </div>

      <!-- Kategori materi (KF-19); disimpan per versi, selalu dapat diubah (KP-19.3). -->
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

      <!-- Petunjuk visibilitas materi (KP-08.5). -->
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

    <!-- Bilah aksi melekat di bawah layar: keterangan simpan di kiri,
         tombol halaman (Simpan, Ajukan publikasi, ...) dari slot di kanan. -->
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
  /** Path sampul tersimpan (bukan URL) untuk dikirim balik saat menyimpan. */
  sampulAwalPath?: string | null
  /** URL sampul tersimpan, hanya untuk pratinjau. */
  sampulAwalUrl?: string | null
  /** Id kategori tersimpan (KP-19.2); kosong untuk materi baru. */
  kategoriAwal?: number[]
  kontenAwal?: KontenQuill | null
  htmlAwal?: string | null
  catatanAwal?: string | null
  tampilkanCatatan?: boolean
  /** Status yang tampil sebagai chip di atas judul. */
  status?: StatusMateri | StatusRevisi | null
  versi?: number | null
  /** Awalan keterangan simpan di bilah aksi bawah, mis. "Draf". */
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
// Sampul disimpan sebagai path; URL-nya hanya untuk pratinjau di isian sampul.
const sampulPath = ref<string | null>(props.sampulAwalPath)
const sampulUrl = ref<string | null>(props.sampulAwalUrl)
const konten = ref<KontenQuill>(props.kontenAwal ?? { ops: [] })
const html = ref(props.htmlAwal ?? '')
// Kategori terpilih (id); wajib minimal satu (KF-19, KP-19.2).
const terpilihKategori = ref<number[]>([...props.kategoriAwal])

// Galat dari validasi cepat di klien, digabung dengan galat 422 dari backend.
const galatJudulKlien = ref('')
const galatKategoriKlien = ref('')
const galatEditorKlien = ref('')

const galatJudul = computed(() => props.galatIsian?.judul || galatJudulKlien.value)
const galatRingkasan = computed(() => props.galatIsian?.ringkasan ?? '')
const galatSampul = computed(() => props.galatIsian?.sampul ?? '')
const galatKategori = computed(() => props.galatIsian?.kategori || galatKategoriKlien.value)
const galatEditor = computed(() => props.galatIsian?.konten || galatEditorKlien.value)
const galatCatatan = computed(() => props.galatIsian?.catatan_revisi ?? '')

/* ---------- Daftar kategori untuk pemilih ---------- */

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

/* ---------- Status simpan di header editor ---------- */

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

// Keterangan di bilah aksi bawah, mis. "Draf · Tersimpan" atau "Materi baru · Belum tersimpan".
const teksStatus = computed(() => {
  const teks = {
    tersimpan: 'Sudah disimpan',
    menyimpan: 'Menyimpan...',
    belum: 'Ada perubahan yang belum disimpan',
  }

  const keadaan = teks[statusSimpan.value]

  return props.keterangan ? `${props.keterangan} · ${keadaan}` : keadaan
})

/** Dipanggil halaman setelah simpan berhasil. */
function tandaiTersimpan() {
  kotor.value = false
}

/* ---------- Tinggi judul & ringkasan mengikuti isi ---------- */

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

/* ---------- Kirim ---------- */

function kirim() {
  galatJudulKlien.value = ''
  galatKategoriKlien.value = ''
  galatEditorKlien.value = ''

  let sah = true

  if (judul.value.trim() === '') {
    galatJudulKlien.value = 'Judulnya belum diisi.'
    sah = false
  }

  // Kategori wajib minimal satu; backend memeriksa ulang hal yang sama (KP-19.2).
  if (terpilihKategori.value.length === 0) {
    galatKategoriKlien.value = 'Pilih minimal satu kategori ya.'
    sah = false
  }

  // Isi kosong ditolak sebelum dikirim; backend tetap memeriksa ulang.
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
    // HTML dari Quill selalu memakai &nbsp;; rapikan sebelum disimpan supaya
    // teks tetap bisa dipotong per kata di halaman baca (lihat rapikanSpasiHtml).
    konten_html: rapikanSpasiHtml(html.value),
    catatan_revisi: props.tampilkanCatatan ? catatan.value.trim() || null : undefined,
  })
}

// Tombol aksi berasal dari slot halaman induk, jadi kirim() diekspos.
defineExpose({ kirim, tandaiTersimpan })
</script>