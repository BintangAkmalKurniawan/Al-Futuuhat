<template>
  <!-- Kepala artikel: judul, dek, byline, dan status (DESIGN.md §5). -->
  <header>
    <div class="flex items-start justify-between gap-6">
      <h1 class="font-serif text-judul-hp font-bold text-teks-kuat lg:text-judul">
        {{ judul }}
      </h1>

      <StatusChip v-if="status" :status="status" :versi="versi" class="mt-1 shrink-0" />
    </div>

    <p v-if="ringkasan" class="mt-4 font-serif text-dek text-teks-lembut">
      {{ ringkasan }}
    </p>

    <!-- Kategori artikel, di bawah dek dan di atas byline (KF-19, KP-19.4).
         Pilnya menuju daftar tersaring bila alamatnya diberikan halaman. -->
    <ChipKategori
      v-if="kategori?.length"
      :daftar="kategori"
      :ke="keKategori"
      class="mt-5"
    />

    <div class="mt-7 flex items-center gap-3">
      <AvatarInisial :nama="penulis?.nama" :id="penulis?.id" ukuran="besar" />

      <div class="min-w-0 text-[13px]">
        <p class="text-teks-kuat">{{ penulis?.nama ?? 'Anonim' }}</p>
        <p v-if="meta" class="mt-0.5 text-teks-lembut">{{ meta }}</p>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import type { Kategori, StatusMateri, StatusRevisi } from '~/types/api'

defineProps<{
  judul: string
  ringkasan?: string | null
  penulis?: { id?: number; nama: string } | null
  /** Meta byline, mis. "Diperbarui 9 Nov 2026 · 5 menit baca · v3". */
  meta?: string | null
  status?: StatusMateri | StatusRevisi | null
  versi?: number | null
  /** Kategori artikel (KF-19); kosong berarti bagian ini tidak tampil. */
  kategori?: Kategori[] | null
  /** Awalan tautan penyaring kategori, mis. `/materi` atau `/publikasi`. */
  keKategori?: string
}>()
</script>
