<template>
  <!-- Judul panel -->
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
  tautan?: string
  ke?: string
}>()
</script>
