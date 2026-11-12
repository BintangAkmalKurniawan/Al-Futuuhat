<template>
  <!-- Keadaan kosong -->
  <div class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center">
    <p class="font-serif text-kartu text-teks-lembut">{{ title }}</p>
    <p v-if="description" class="max-w-md text-sm text-teks-redup">{{ description }}</p>

    <div v-if="$slots.action" class="mt-3">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>
