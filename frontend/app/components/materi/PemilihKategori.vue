<template>
  <!-- Pemilih kategori -->
  <div>
    <p class="text-xs font-medium tracking-wider text-teks-redup uppercase">Kategori</p>

    <!-- Kotak cari -->
    <div v-if="!memuat && bisaDiringkas" class="relative mt-2">
      <Search
        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-teks-redup"
        aria-hidden="true"
      />
      <input
        v-model="cari"
        type="search"
        placeholder="Cari kategori"
        aria-label="Cari kategori"
        class="h-10 w-full rounded-full bg-permukaan pr-3 pl-9 text-sm text-teks ring-1 ring-garis transition-colors duration-200 placeholder:text-teks-redup focus:ring-aksen focus:outline-none"
        @keydown.enter.prevent
      />
    </div>

    <!-- Kerangka memuat -->
    <div v-if="memuat" class="mt-2 flex animate-pulse flex-wrap gap-2">
      <div v-for="n in 4" :key="n" class="h-8 w-20 rounded-full bg-permukaan" />
    </div>

    <template v-else>
      <div class="mt-2 flex flex-wrap gap-2">
        <button
          v-for="item in terlihat"
          :key="item.id"
          type="button"
          :aria-pressed="terpilih.includes(item.id)"
          :disabled="nonaktif"
          :class="kelasPil(terpilih.includes(item.id))"
          @click="toggle(item.id)"
        >
          {{ item.nama }}
        </button>
      </div>

      <p v-if="bisaDiringkas && terlihat.length === 0" class="mt-2 text-[13px] text-teks-redup">
        Kategorinya tidak ketemu.
      </p>

      <!-- Peringkas daftar pil -->
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

      <p v-if="galatMuat" class="pesan-galat" role="alert">
        {{ galatMuat }}
        <button type="button" class="ml-2 underline" @click="emit('muat-ulang')">Coba lagi</button>
      </p>
    </template>

    <p class="mt-2 text-[13px] text-teks-redup">
      {{
        nonaktif
          ? 'Kategori belum bisa diubah saat ini.'
          : 'Pilih minimal satu kategori. Kategori dipakai untuk menyaring daftar materi.'
      }}
    </p>

    <p v-if="error" class="pesan-galat" role="alert">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { ChevronDown, ChevronUp, Search } from '@lucide/vue'
import type { Kategori } from '~/types/api'

const props = withDefaults(
  defineProps<{
    daftar: Kategori[]
    nonaktif?: boolean
    memuat?: boolean
    galatMuat?: string
    error?: string
    ringkas?: number
  }>(),
  { nonaktif: false, memuat: false, galatMuat: '', error: '', ringkas: 8 },
)

const emit = defineEmits<{ (e: 'muat-ulang'): void }>()

const terpilih = defineModel<number[]>({ required: true })

const terbuka = ref(false)
const cari = ref('')

const bisaDiringkas = computed(() => props.daftar.length > props.ringkas)

const hasilCari = computed(() => {
  const kata = cari.value.trim().toLowerCase()

  return kata ? props.daftar.filter((item) => item.nama.toLowerCase().includes(kata)) : props.daftar
})

const dasar = computed(() =>
  terbuka.value ? hasilCari.value : hasilCari.value.slice(0, props.ringkas),
)

const terlihat = computed(() => {
  const terpilihBelumTampil = props.daftar.filter(
    (item) => terpilih.value.includes(item.id) && !dasar.value.includes(item),
  )

  return [...dasar.value, ...terpilihBelumTampil]
})

const sisa = computed(() => Math.max(hasilCari.value.length - dasar.value.length, 0))

// Buka-tutup daftar penuh
function alihkan() {
  terbuka.value = !terbuka.value

  if (!terbuka.value) cari.value = ''
}

function toggle(id: number) {
  if (props.nonaktif) return

  terpilih.value = terpilih.value.includes(id)
    ? terpilih.value.filter((nilai) => nilai !== id)
    : [...terpilih.value, id]
}

function kelasPil(aktif: boolean) {
  return [
    'inline-flex h-8 items-center rounded-full border px-3.5 text-[13px]',
    'transition-colors duration-200 disabled:cursor-not-allowed',
    aktif
      ? 'border-garis-kuat bg-angkat font-medium text-teks-kuat'
      : 'border-garis bg-permukaan text-teks-lembut hover:text-teks-kuat disabled:hover:text-teks-lembut',
  ].join(' ')
}
</script>
