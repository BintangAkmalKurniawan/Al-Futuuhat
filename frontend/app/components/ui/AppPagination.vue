<template>
  <nav
    v-if="meta.last_page > 1"
    class="flex flex-wrap items-center justify-between gap-3"
    aria-label="Navigasi halaman"
  >
    <p class="text-[13px] text-teks-lembut">
      Halaman {{ meta.current_page }} dari {{ meta.last_page }} · {{ meta.total }} data
    </p>

    <div class="flex items-center gap-1">
      <button
        v-for="halaman in daftarHalaman"
        :key="halaman"
        type="button"
        class="grid h-9 w-9 place-items-center rounded-full text-sm transition-colors duration-200"
        :class="
          halaman === meta.current_page
            ? 'bg-permukaan font-medium text-teks-kuat'
            : 'text-teks-lembut hover:bg-permukaan hover:text-teks-kuat'
        "
        :aria-current="halaman === meta.current_page ? 'page' : undefined"
        :aria-label="`Halaman ${halaman}`"
        @click="emit('ganti-halaman', halaman)"
      >
        {{ halaman }}
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import type { MetaHalaman } from '~/types/api'

const props = defineProps<{ meta: MetaHalaman }>()

const emit = defineEmits<{ (e: 'ganti-halaman', halaman: number): void }>()

const daftarHalaman = computed(() => {
  const total = props.meta.last_page
  const aktif = props.meta.current_page
  const mulai = Math.max(1, Math.min(aktif - 2, total - 4))
  const akhir = Math.min(total, mulai + 4)

  return Array.from({ length: akhir - mulai + 1 }, (_, i) => mulai + i)
})
</script>
