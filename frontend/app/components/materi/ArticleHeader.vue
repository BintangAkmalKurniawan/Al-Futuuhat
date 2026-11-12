<template>
  <!-- Kepala artikel -->
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
  meta?: string | null
  status?: StatusMateri | StatusRevisi | null
  versi?: number | null
  kategori?: Kategori[] | null
  keKategori?: string
}>()
</script>
