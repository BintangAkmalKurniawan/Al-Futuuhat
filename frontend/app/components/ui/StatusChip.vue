<template>
  <!-- Label status materi/revisi (DESIGN.md §7). Warna tidak pernah sendiri:
       selalu ada teks agar tetap terbaca tanpa membedakan warna. -->
  <span class="chip" :class="kelas">
    {{ teks }}
  </span>
</template>

<script setup lang="ts">
import type { StatusMateri, StatusRevisi } from '~/types/api'

interface Props {
  status: StatusMateri | StatusRevisi
  /** Nomor versi revisi. Tidak lagi ditampilkan di chip (nomor versi hanya di Riwayat perubahan). */
  versi?: number | null
  /** Menimpa kata label, mis. "Perubahan belum tayang" untuk materi tayang yang punya draf baru. */
  label?: string
}

const props = defineProps<Props>()

const LABEL: Record<StatusMateri | StatusRevisi, string> = {
  draf: 'Belum tayang',
  diajukan: 'Lagi dicek admin',
  ditolak: 'Belum disetujui',
  disetujui: 'Pernah tayang',
  terbit: 'Tayang untuk umum',
}

const KELAS: Record<StatusMateri | StatusRevisi, string> = {
  draf: 'chip-draf',
  diajukan: 'chip-diajukan',
  ditolak: 'chip-ditolak',
  disetujui: 'chip-terbit',
  terbit: 'chip-terbit',
}

const kelas = computed(() => KELAS[props.status] ?? 'chip-draf')

// Panduan Bahasa UI: nomor versi tidak ditampilkan di chip, cukup di Riwayat perubahan.
const teks = computed(() => props.label ?? LABEL[props.status] ?? props.status)
</script>
