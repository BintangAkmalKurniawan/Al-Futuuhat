<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap',
      kelasVarian[variant],
    ]"
  >
    <slot />
  </span>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'netral' | 'info' | 'sukses' | 'peringatan' | 'bahaya' | 'aksen'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'netral',
})

// Warna lencana memakai token tema gelap; status materi tetap memakai
// StatusChip agar tidak ada dua gaya lencana yang bersaing.
const kelasVarian: Record<NonNullable<Props['variant']>, string> = {
  netral: 'bg-permukaan text-teks-lembut',
  info: 'bg-aksen/10 text-aksen',
  sukses: 'bg-terbit/12 text-terbit',
  peringatan: 'bg-diajukan/12 text-diajukan',
  bahaya: 'bg-ditolak/12 text-ditolak',
  // Lencana peran Admin: bergaris aksen (DESIGN.md §6.6 no. 20).
  aksen: 'border border-aksen/50 bg-permukaan text-aksen',
}
</script>
