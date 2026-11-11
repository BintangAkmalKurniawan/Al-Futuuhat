<template>
  <!-- Tombol A− / A+ untuk ukuran tulisan isi artikel. -->
  <div class="inline-flex items-center gap-1 rounded-full bg-permukaan p-1" role="group" aria-label="Ukuran tulisan">
    <button
      type="button"
      class="grid h-8 w-8 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:bg-angkat hover:text-teks-kuat disabled:opacity-40"
      aria-label="Perkecil tulisan"
      :disabled="!bisaPerkecil"
      @click="perkecil"
    >
      <AArrowDown class="h-4 w-4" aria-hidden="true" />
    </button>
    <button
      type="button"
      class="grid h-8 w-8 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:bg-angkat hover:text-teks-kuat disabled:opacity-40"
      aria-label="Perbesar tulisan"
      :disabled="!bisaPerbesar"
      @click="perbesar"
    >
      <AArrowUp class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { AArrowDown, AArrowUp } from '@lucide/vue'

const { perkecil, perbesar, bisaPerkecil, bisaPerbesar } = useUkuranArtikel()
</script>
