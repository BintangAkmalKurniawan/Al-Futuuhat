<template>
  <!-- Sampul bawaan -->
  <div class="relative flex h-full w-full flex-col justify-between overflow-hidden p-5" :class="latar" aria-hidden="true">
    <span class="relative text-[11px] font-medium tracking-[.08em] text-aksen uppercase">
      {{ kategori || 'Artikel' }}
    </span>

    <img :src="logo" alt="" class="relative h-6 w-auto self-start opacity-70" />

    <span
      class="pointer-events-none absolute -right-2 -bottom-10 font-serif text-[180px] leading-none font-bold text-teks-kuat/10 select-none"
    >
      {{ huruf }}
    </span>
  </div>
</template>

<script setup lang="ts">
import logo from '~/assets/logo.png'

const props = defineProps<{
  judul: string
  kategori?: string | null
  id: number
}>()

const huruf = computed(() => props.judul.trim().charAt(0).toUpperCase() || 'A')

const VARIASI = [
  'bg-gradient-to-br from-aksen/25 via-angkat to-permukaan',
  'bg-gradient-to-tr from-aksen/20 via-permukaan to-angkat',
  'bg-gradient-to-bl from-aksen/25 via-angkat to-kanvas',
  'bg-gradient-to-tl from-aksen/15 via-angkat to-permukaan',
]

const latar = computed(() => VARIASI[props.id % VARIASI.length])
</script>
