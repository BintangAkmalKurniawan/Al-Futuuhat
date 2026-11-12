<template>
  <div
    :class="isAdmin ? 'bottom-0' : 'bottom-[84px] md:bottom-0'"
    class="sticky z-20 flex h-12 items-center gap-5 border-y border-garis bg-kanvas/95 backdrop-blur lg:static"
  >
    <button
      v-if="jumlahKomentar !== null"
      type="button"
      class="inline-flex items-center gap-2 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
      @click="emit('buka-diskusi')"
    >
      <MessageCircle class="h-4 w-4" aria-hidden="true" />
      {{ jumlahKomentar }} komentar
    </button>

    <button
      v-if="bisaRiwayat"
      type="button"
      class="inline-flex items-center gap-2 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
      @click="emit('buka-riwayat')"
    >
      <History class="h-4 w-4" aria-hidden="true" />
      Riwayat perubahan
    </button>

    <!-- "Versi publik -->
    <button
      v-if="versiPublik"
      type="button"
      class="inline-flex items-center gap-2 text-[13px] text-aksen transition-colors duration-200 hover:underline"
      @click="emit('buka-versi-publik')"
    >
      <Globe class="h-4 w-4" aria-hidden="true" />
      Lihat versi yang sedang tayang
    </button>

    <p v-if="catatan" class="ml-auto hidden text-[13px] text-teks-redup lg:block">
      {{ catatan }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { Globe, History, MessageCircle } from '@lucide/vue'

const { isAdmin } = useAuth()

withDefaults(
  defineProps<{
    jumlahKomentar?: number | null
    catatan?: string | null
    bisaRiwayat?: boolean
    versiPublik?: number | null
  }>(),
  {
    jumlahKomentar: null,
    catatan: null,
    bisaRiwayat: true,
    versiPublik: null,
  },
)

const emit = defineEmits<{
  (e: 'buka-diskusi'): void
  (e: 'buka-riwayat'): void
  (e: 'buka-versi-publik'): void
}>()
</script>
