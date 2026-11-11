<template>
  <!-- Status program (KP-21.1): aktif = masih berjalan, selesai = sudah berhenti. -->
  <AppBadge :variant="status === 'aktif' ? 'sukses' : 'netral'">
    {{ status === 'aktif' ? 'Berjalan' : 'Selesai' }}
  </AppBadge>
</template>

<script setup lang="ts">
import type { StatusProgram } from '~/types/api'

defineProps<{ status: StatusProgram }>()
</script>
