<template>
  <div class="mx-auto max-w-baca py-10 text-center">
    <span
      class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-terbit/12 text-terbit"
      aria-hidden="true"
    >
      <Check class="h-6 w-6" />
    </span>

    <h1 class="mt-6 font-serif text-halaman font-bold text-teks-kuat">Pendaftaran terkirim</h1>

    <p class="mx-auto mt-4 max-w-md font-serif text-dek text-teks-lembut">
      Terima kasih sudah mendaftar! 🎉 Cek emailmu dan klik tautan konfirmasinya. Setelah itu,
      pendaftaranmu lanjut dicek admin.
    </p>

    <p class="mt-4 text-sm text-teks-lembut">
      Belum dapat email?
      <NuxtLink to="/verifikasi-email" class="text-aksen hover:underline">Kirim ulang</NuxtLink>
    </p>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <AppButton @click="navigateTo('/masuk')">Ke halaman masuk</AppButton>
      <AppButton variant="sekunder" @click="navigateTo('/')">Kembali ke beranda</AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Check } from '@lucide/vue'

definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Pendaftaran Terkirim' })
</script>
