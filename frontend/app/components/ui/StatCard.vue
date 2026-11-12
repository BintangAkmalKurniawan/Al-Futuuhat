<template>
  <!-- Kartu angka ringkasan -->
  <component
    :is="ke ? 'NuxtLink' : 'div'"
    :to="ke"
    class="block rounded-lg bg-permukaan px-5 py-4 ring-1 ring-garis"
    :class="ke ? 'transition-colors duration-200 hover:ring-garis-kuat' : ''"
  >
    <p class="text-[13px] text-teks-lembut">{{ label }}</p>
    <p class="mt-1 font-serif text-[34px] leading-none font-semibold text-teks-kuat">{{ nilai }}</p>
    <p v-if="keterangan" class="mt-2 text-[12px] text-teks-redup">{{ keterangan }}</p>
  </component>
</template>

<script setup lang="ts">
defineProps<{
  label: string
  nilai: number | string
  keterangan?: string
  ke?: string | Record<string, unknown>
}>()
</script>
