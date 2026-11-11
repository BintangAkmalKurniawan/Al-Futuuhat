<template>
  <!-- Tombol "Dengarkan" artikel (Web Speech API). Disembunyikan bila peramban
       tidak mendukung pembacaan suara. -->
  <div v-if="didukung" class="inline-flex items-center gap-1 rounded-full bg-permukaan p-1" role="group" aria-label="Dengarkan artikel">
    <button
      v-if="status === 'diam'"
      type="button"
      class="inline-flex h-8 items-center gap-2 rounded-full px-3 text-[13px] text-teks-lembut transition-colors duration-200 hover:bg-angkat hover:text-teks-kuat"
      @click="putar(judul, html)"
    >
      <Headphones class="h-4 w-4" aria-hidden="true" />
      Dengarkan
    </button>

    <template v-else>
      <button
        type="button"
        class="grid h-8 w-8 place-items-center rounded-full text-teks-kuat transition-colors duration-200 hover:bg-angkat"
        :aria-label="status === 'membaca' ? 'Jeda' : 'Lanjutkan'"
        @click="status === 'membaca' ? jeda() : lanjut()"
      >
        <component :is="status === 'membaca' ? Pause : Play" class="h-4 w-4" aria-hidden="true" />
      </button>
      <button
        type="button"
        class="grid h-8 w-8 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:bg-angkat hover:text-teks-kuat"
        aria-label="Berhenti"
        @click="berhenti"
      >
        <Square class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </template>

    <!-- Kecepatan baca; berlaku saat mulai mendengarkan. -->
    <button
      type="button"
      class="h-8 rounded-full px-2.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:bg-angkat hover:text-teks-kuat"
      :aria-label="`Kecepatan ${kecepatan} kali`"
      @click="gantiKecepatan"
    >
      {{ kecepatan }}×
    </button>
  </div>
</template>

<script setup lang="ts">
import { Headphones, Pause, Play, Square } from '@lucide/vue'

const props = defineProps<{ judul: string; html: string | null | undefined }>()

const { didukung, status, kecepatan, putar: putarArtikel, jeda, lanjut, berhenti } = useBacaArtikel()

const PILIHAN = [0.75, 1, 1.25, 1.5]

function putar(judul: string, html: string | null | undefined) {
  putarArtikel(judul, html ?? '')
}

/** Ganti kecepatan berurutan; bila sedang membaca, mulai ulang dengan kecepatan baru. */
function gantiKecepatan() {
  const i = PILIHAN.indexOf(kecepatan.value)
  kecepatan.value = PILIHAN[(i + 1) % PILIHAN.length]

  if (status.value !== 'diam') putar(props.judul, props.html)
}
</script>
