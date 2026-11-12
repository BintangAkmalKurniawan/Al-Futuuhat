<template>
  <!-- Wadah pesan singkat -->
  <div
    class="pointer-events-none fixed right-4 bottom-4 z-50 flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-2"
    aria-live="polite"
  >
    <div
      v-for="pesan in daftar"
      :key="pesan.id"
      class="pointer-events-auto flex items-start gap-3 rounded-lg border border-garis bg-angkat px-4 py-3 text-sm text-teks shadow-[0_14px_40px_rgba(0,0,0,.5)]"
    >
      <component
        :is="ikon[pesan.jenis]"
        class="mt-0.5 h-4 w-4 shrink-0"
        :class="kelasIkon[pesan.jenis]"
        aria-hidden="true"
      />
      <p class="flex-1">{{ pesan.teks }}</p>

      <button
        type="button"
        class="-m-1 rounded-full p-1 text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
        aria-label="Tutup pesan"
        @click="tutup(pesan.id)"
      >
        <X class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CircleCheck, Info, TriangleAlert, X } from '@lucide/vue'

const { daftar, tutup } = usePesanToast()

const ikon = { sukses: CircleCheck, bahaya: TriangleAlert, info: Info }

const kelasIkon = {
  sukses: 'text-terbit',
  bahaya: 'text-bahaya',
  info: 'text-aksen',
}
</script>
