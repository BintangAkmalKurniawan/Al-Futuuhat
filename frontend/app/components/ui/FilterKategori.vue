<template>
  <!-- Penyaring kategori: pil "Semua" + satu pil per kategori (KF-19, KP-19.4).
       Di sidebar: membungkus ke baris berikutnya dan diringkas bila kategorinya
       banyak, dengan tombol "Selengkapnya" dan kotak cari. Di ponsel (`gulir`):
       satu baris yang dapat digulir mendatar, semua pil tampil tanpa peringkas. -->
  <div role="group" aria-label="Saring menurut kategori">
    <!-- Kotak cari pil: hanya di sidebar, dan baru muncul setelah daftar dibuka. -->
    <div v-if="bisaDiringkas && terbuka" class="relative mb-2">
      <Search
        class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-teks-redup"
        aria-hidden="true"
      />
      <input
        v-model="cari"
        type="search"
        placeholder="Cari kategori"
        aria-label="Cari kategori"
        class="h-9 w-full rounded-full bg-permukaan pr-3 pl-9 text-[13px] text-teks ring-1 ring-garis transition-colors duration-200 placeholder:text-teks-redup focus:ring-aksen focus:outline-none"
      />
    </div>

    <div :class="kelasAkar">
      <button
        type="button"
        :aria-pressed="model === ''"
        :class="kelasPil(model === '')"
        @click="model = ''"
      >
        Semua
      </button>

      <button
        v-for="item in terlihat"
        :key="item.id"
        type="button"
        :aria-pressed="model === item.slug"
        :class="kelasPil(model === item.slug)"
        @click="model = item.slug"
      >
        {{ item.nama }}
      </button>
    </div>

    <p v-if="bisaDiringkas && terbuka && terlihat.length === 0" class="mt-2 text-[13px] text-teks-redup">
      Kategori tidak ditemukan.
    </p>

    <!-- Peringkas daftar: muncul sendiri saat jumlah kategori melewati `ringkas`. -->
    <button
      v-if="bisaDiringkas"
      type="button"
      :aria-expanded="terbuka"
      class="mt-2 inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
      @click="alihkan"
    >
      {{ terbuka ? 'Sembunyikan' : `Selengkapnya (${sisa})` }}
      <component :is="terbuka ? ChevronUp : ChevronDown" class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ChevronDown, ChevronUp, Search } from '@lucide/vue'
import type { Kategori } from '~/types/api'

const props = withDefaults(
  defineProps<{
    daftar: Kategori[]
    /** Satu baris yang dapat digulir mendatar (dipakai di ponsel), tanpa peringkas. */
    gulir?: boolean
    /** Jumlah pil yang tampil sebelum tombol "Selengkapnya". */
    ringkas?: number
  }>(),
  { gulir: false, ringkas: 8 },
)

/** Slug kategori terpilih; teks kosong berarti semua kategori. */
const model = defineModel<string>({ required: true })

const terbuka = ref(false)
const cari = ref('')

// Peringkas hanya untuk pil yang membungkus; baris gulir ponsel sudah muat semua.
const bisaDiringkas = computed(() => !props.gulir && props.daftar.length > props.ringkas)

const hasilCari = computed(() => {
  const kata = cari.value.trim().toLowerCase()

  return kata ? props.daftar.filter((item) => item.nama.toLowerCase().includes(kata)) : props.daftar
})

/** Pil sebelum peringkas dibuka: hanya `ringkas` pil pertama. */
const dasar = computed(() =>
  terbuka.value ? hasilCari.value : hasilCari.value.slice(0, props.ringkas),
)

/** Pil yang ditampilkan; pil yang sedang dipilih selalu ikut tampil (KP-19.4). */
const terlihat = computed(() => {
  const terpilih = hasilCari.value.find((item) => item.slug === model.value)

  return terpilih && !dasar.value.includes(terpilih) ? [...dasar.value, terpilih] : dasar.value
})

/** Jumlah pil yang masih tersembunyi, dipakai sebagai angka pada tombol. */
const sisa = computed(() => Math.max(hasilCari.value.length - dasar.value.length, 0))

/** Buka-tutup daftar penuh; kotak cari dikosongkan saat ditutup. */
function alihkan() {
  terbuka.value = !terbuka.value

  if (!terbuka.value) cari.value = ''
}

const kelasAkar = computed(() =>
  props.gulir ? 'flex flex-nowrap gap-2 overflow-x-auto pb-1' : 'flex flex-wrap gap-2',
)

// Tampilan pil mengikuti `.seg button` di main.css, tetapi tiap pilihan
// berdiri sendiri supaya jumlah kategori berapa pun tetap rapi membungkus.
function kelasPil(aktif: boolean) {
  return [
    'inline-flex h-8 shrink-0 items-center rounded-full px-3.5 text-[13px]',
    'transition-colors duration-200',
    aktif ? 'bg-angkat font-medium text-teks-kuat' : 'bg-permukaan text-teks-lembut hover:text-teks-kuat',
  ].join(' ')
}
</script>
