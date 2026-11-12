<template>
  <!-- Pil kategori materi -->
  <div class="flex flex-wrap items-center gap-2">
    <template v-for="item in daftar" :key="item.id">
      <NuxtLink
        v-if="ke"
        :to="`${ke}?kategori=${item.slug}`"
        :aria-label="`Saring materi berkategori ${item.nama}`"
        :class="kelas"
      >
        {{ item.nama }}
      </NuxtLink>

      <span v-else :class="kelas">{{ item.nama }}</span>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { Kategori } from '~/types/api'

withDefaults(defineProps<{ daftar: Kategori[]; ke?: string }>(), { ke: '' })

const kelas = [
  'inline-flex items-center rounded-full border border-garis px-2.5 py-[2px]',
  'text-[12px] leading-5 text-teks-lembut',
  'transition-colors duration-200 hover:border-garis-kuat hover:text-teks-kuat',
].join(' ')
</script>
