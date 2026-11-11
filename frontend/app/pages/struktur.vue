<template>
  <div>
    <!-- Anggota dapat memilih periode lama (KP-01.3); riwayat mengikuti periode terpilih. -->
    <BaganStruktur varian="halaman" tampil-tools @periode="periode = $event" />

    <section v-if="periode" class="mt-14 border-t border-garis pt-10">
      <h2 class="font-serif text-[26px] font-bold text-teks-kuat">Riwayat perubahan</h2>
      <p class="mt-2 mb-6 text-sm text-teks-lembut">
        Perubahan susunan pengurus periode {{ periode.nama_periode }} yang dilakukan admin.
      </p>

      <PanelRiwayatStruktur :periode-id="periode.id" />
    </section>
  </div>
</template>

<script setup lang="ts">
import type { Periode } from '~/types/api'

definePageMeta({ layout: 'anggota', middleware: ['auth'] })
useHead({ title: 'Struktur Organisasi' })

const periode = ref<Periode | null>(null)
</script>
