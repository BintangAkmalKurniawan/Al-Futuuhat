<template>
  <!-- Bilah aksi di antara garis, tinggi ±48 px (DESIGN.md §5).
       Di ponsel bilah ini menempel di bawah layar (§6.7), tepat di atas toolbar
       navigasi anggota (toolbar: bottom 12 px + tinggi 64 px, lalu jarak 8 px).
       Di tablet tidak ada toolbar, jadi bilah kembali ke dasar layar.
       Admin memakai layout admin tanpa toolbar bawah, jadi selalu di dasar layar. -->
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

    <!-- KP-16.4 (Draf 2.3): "Versi publik: vN" dapat diklik untuk membaca versi publik. -->
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
    /** Catatan kanan, mis. "Anda membaca v3 (draf revisi) · Menunggu penarikan". */
    catatan?: string | null
    /** true = riwayat versi boleh dibuka (anggota & admin, KP-17.4). */
    bisaRiwayat?: boolean
    /** Nomor versi publik bila berbeda dari yang sedang dibaca; null = tautan disembunyikan. */
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
