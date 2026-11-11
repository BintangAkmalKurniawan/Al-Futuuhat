<template>
  <!-- Kotak cari berbentuk pil (DESIGN.md §5). Dengan `realtime`, daftar
       disaring saat mengetik (dijeda singkat); Enter tetap langsung mencari. -->
  <form class="relative" role="search" @submit.prevent="cariSekarang">
    <Search
      class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-teks-redup"
      aria-hidden="true"
    />

    <input
      v-model="model"
      type="search"
      :placeholder="placeholder"
      :aria-label="placeholder"
      class="h-10 w-full rounded-full bg-permukaan pr-3 pl-9 text-sm text-teks ring-1 ring-garis transition-colors duration-200 placeholder:text-teks-redup focus:ring-aksen focus:outline-none"
      @input="ketikRealtime"
    />
  </form>
</template>

<script setup lang="ts">
import { Search } from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    placeholder?: string
    /** Saring saat mengetik, bukan hanya saat Enter. */
    realtime?: boolean
    /** Jeda (ms) sebelum pencarian realtime dikirim. */
    jeda?: number
  }>(),
  {
    placeholder: 'Cari materi',
    realtime: false,
    jeda: 350,
  },
)

const model = defineModel<string>({ default: '' })

const emit = defineEmits<{ (e: 'cari', kata: string): void }>()

let pewaktu: ReturnType<typeof setTimeout> | null = null

function batalkanPewaktu() {
  if (pewaktu === null) return

  clearTimeout(pewaktu)
  pewaktu = null
}

function kirim() {
  emit('cari', model.value.trim())
}

/** Enter: langsung menyaring tanpa menunggu jeda realtime. */
function cariSekarang() {
  batalkanPewaktu()
  kirim()
}

// Satu permintaan per jeda, bukan satu per ketukan, agar API tidak dibanjiri.
// Dipicu oleh ketikan di kotak INI saja (bukan perubahan nilai dari luar), sehingga
// halaman boleh memasang dua kotak (ponsel + sidebar) tanpa pencarian terkirim dua kali.
function ketikRealtime() {
  if (!props.realtime) return

  batalkanPewaktu()
  pewaktu = setTimeout(kirim, props.jeda)
}

onBeforeUnmount(batalkanPewaktu)
</script>