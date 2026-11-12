<template>
  <!-- Label status materi/revisi -->
  <span class="chip" :class="kelas">
    {{ teks }}
  </span>
</template>

<script setup lang="ts">
import type { StatusMateri, StatusRevisi } from '~/types/api'

interface Props {
  status: StatusMateri | StatusRevisi
  versi?: number | null
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

const teks = computed(() => props.label ?? LABEL[props.status] ?? props.status)
</script>
