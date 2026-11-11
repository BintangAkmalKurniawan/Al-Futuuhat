<template>
  <!-- Pil kategori materi (KF-19, KP-19.4). Bila `ke` diisi, tiap pil menjadi
       tautan ke daftar tersaring (mis. `/materi` → `/materi?kategori=fiqih`);
       bila kosong, pil hanya menjadi label. -->
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
