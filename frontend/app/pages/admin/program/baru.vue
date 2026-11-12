<template>
  <!-- Tambah program -->
  <div class="mx-auto max-w-3xl">
    <NuxtLink
      to="/admin/program"
      class="inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Semua program
    </NuxtLink>

    <header class="pt-4 pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Tambah program</h1>
      <p class="mt-3 text-sm text-teks-lembut">
        Setelah program disimpan, Anda dapat mengunggah foto dan video dokumentasinya.
      </p>
    </header>

    <FormProgram @tersimpan="setelahSimpan" />
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft } from '@lucide/vue'
import type { ProgramAdmin } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Tambah Program' })

const { tampilkan } = usePesanToast()

// Setelah simpan
async function setelahSimpan(program: ProgramAdmin, pesan: string) {
  tampilkan(pesan, 'sukses')
  await navigateTo({ path: `/admin/program/${program.id}`, query: { tab: 'dokumentasi' } })
}
</script>
