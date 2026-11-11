<template>
  <!-- Daftar program kegiatan rutin untuk pengunjung dan anggota (KF-21, KP-21.5). -->
  <div>
    <header class="max-w-3xl pb-8">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Program kami</h1>
      <p class="mt-3 font-serif text-dek text-teks-lembut">
        Kegiatan rutin yang dijalankan Al-Futuuhat, lengkap dengan penanggung jawab dan
        dokumentasinya.
      </p>
    </header>

    <AppAlert v-if="galat" variant="bahaya" title="Program belum bisa dimuat" class="mb-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat: bentuknya sama dengan kartu program. -->
    <div v-else-if="memuat" class="grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="n in 6"
        :key="n"
        class="animate-pulse overflow-hidden rounded-lg bg-permukaan ring-1 ring-garis"
      >
        <div class="aspect-[16/10] w-full bg-angkat" />
        <div class="space-y-3 p-5">
          <div class="h-3 w-20 rounded-full bg-angkat" />
          <div class="h-5 w-3/4 rounded-full bg-angkat" />
          <div class="h-3 w-full rounded-full bg-angkat" />
        </div>
      </div>
    </div>

    <AppEmptyState
      v-else-if="daftar.length === 0"
      title="Belum ada program."
      description="Program kegiatan Al-Futuuhat akan tampil di sini."
    />

    <div v-else class="grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
      <KartuProgram v-for="item in daftar" :key="item.id" :program="item" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ProgramRingkas } from '~/types/api'

definePageMeta({ layout: 'publik' })
useHead({ title: 'Program' })

const { daftarPublik } = useProgram()

const daftar = ref<ProgramRingkas[]>([])
const memuat = ref(true)
const galat = ref('')

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarPublik()
    daftar.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

onMounted(muat)
</script>
