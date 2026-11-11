<template>
  <!-- Halaman galat: route tidak ada atau materi belum terbit (KP-17.3, DESIGN.md §6.4 no. 27). -->
  <div class="flex min-h-screen flex-col items-center bg-kanvas px-6 py-28 text-center text-teks">
    <p
      class="font-serif text-[120px] leading-none font-semibold tracking-[-0.04em] text-teks-kuat"
      aria-hidden="true"
    >
      {{ kode }}
    </p>

    <h1 class="mt-3 font-serif text-judul-hp font-semibold text-teks-kuat">
      {{ judul }}
    </h1>

    <p class="mt-3 mb-7 max-w-[420px] text-[15px] leading-relaxed text-teks-lembut">
      {{ pesan }}
    </p>

    <AppButton @click="kePublikasi">Lihat publikasi</AppButton>
  </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()

const kode = computed(() => props.error.statusCode || 500)

const judul = computed(() =>
  kode.value === 404 ? 'Halaman tidak ditemukan' : 'Terjadi kesalahan',
)

const pesan = computed(() =>
  kode.value === 404
    ? 'Tulisan yang Anda cari mungkin belum diterbitkan untuk umum atau alamatnya salah.'
    : 'Permintaan tidak dapat diproses saat ini. Silakan coba lagi sebentar lagi.',
)

// clearError() menutup halaman galat lalu berpindah ke daftar publikasi.
function kePublikasi() {
  clearError({ redirect: '/publikasi' })
}
</script>
