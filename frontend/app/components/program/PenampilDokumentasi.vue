<template>
  <!-- Penampil dokumentasi -->
  <AppModal v-model:open="terbuka" :title="judul" lebar>
    <template v-if="item && terbuka">
      <div class="overflow-hidden rounded-sm bg-kanvas">
        <img
          v-if="item.jenis === 'foto'"
          :src="item.url"
          :alt="item.keterangan || 'Foto dokumentasi program'"
          class="mx-auto max-h-[65vh] w-auto object-contain"
        />

        <!-- Preload="metadata" -->
        <video
          v-else-if="item.jenis === 'video'"
          :key="item.id"
          :src="item.url"
          controls
          preload="metadata"
          class="mx-auto max-h-[65vh] w-full"
        >
          Video ini belum bisa diputar di peramban yang dipakai.
        </video>

        <div v-else-if="item.embed" class="aspect-video w-full">
          <iframe
            :key="item.id"
            :src="item.embed"
            :title="item.keterangan || 'Video YouTube dokumentasi program'"
            class="h-full w-full"
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          />
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <p v-if="item.keterangan" class="text-teks">{{ item.keterangan }}</p>
          <p class="mt-0.5 text-[13px] text-teks-redup">
            Diunggah {{ formatTanggal(item.dibuat_pada) }}
          </p>
        </div>

        <a
          v-if="item.jenis === 'youtube'"
          :href="item.url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 text-[13px] text-aksen transition-colors duration-200 hover:text-teks-kuat"
        >
          Buka di YouTube
          <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
        </a>
      </div>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ExternalLink } from '@lucide/vue'
import type { DokumentasiProgram } from '~/types/api'

const props = defineProps<{ item: DokumentasiProgram | null }>()

const terbuka = defineModel<boolean>('open', { default: false })

const judul = computed(() => (props.item?.jenis === 'foto' ? 'Foto dokumentasi' : 'Video dokumentasi'))
</script>
