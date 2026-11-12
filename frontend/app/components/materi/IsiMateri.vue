<template>
  <div>
    <article v-if="isiRapi" class="prosa" v-html="isiRapi" />
    <p v-else class="text-sm text-teks-lembut">Materi ini belum ada isinya.</p>

    <div v-if="catatan" class="mt-8 border-t border-garis pt-4">
      <p class="text-xs tracking-wider text-teks-redup uppercase">Catatan perubahan</p>
      <p class="mt-1 font-serif text-sm whitespace-pre-line text-teks">{{ catatan }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  html: string | null | undefined
  catatan?: string | null
}>()

const isiRapi = computed(() => rapikanSpasiHtml(props.html))
</script>