<template>
  <!-- Judul panel dasbor dengan tautan "Lihat semua" opsional (DESIGN.md §6.2–6.3). -->
  <div class="mt-2 mb-1 flex items-center justify-between gap-4">
    <h2 class="text-[16px] leading-[1.3] font-semibold text-teks-kuat">{{ judul }}</h2>

    <NuxtLink
      v-if="tautan && ke"
      :to="ke"
      class="text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      {{ tautan }}
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  judul: string
  /** Label tautan kanan, mis. "Lihat semua". */
  tautan?: string
  ke?: string
}>()
</script>
