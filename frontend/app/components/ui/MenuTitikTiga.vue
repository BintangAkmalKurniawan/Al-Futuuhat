<template>
  <!-- Menu titik tiga -->
  <div class="relative">
    <button
      type="button"
      class="grid h-9 w-9 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:bg-permukaan hover:text-teks-kuat"
      :aria-expanded="terbuka"
      aria-haspopup="menu"
      :aria-label="ariaLabel"
      @click.stop="terbuka = !terbuka"
    >
      <EllipsisVertical class="h-4 w-4" aria-hidden="true" />
    </button>

    <div
      v-if="terbuka"
      role="menu"
      class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-lg border border-garis bg-angkat py-1 shadow-[0_14px_40px_rgba(0,0,0,.5)]"
    >
      <button
        v-for="aksi in item"
        :key="aksi.label"
        type="button"
        role="menuitem"
        class="block w-full px-4 py-2 text-left text-sm transition-colors duration-200 hover:bg-permukaan"
        :class="aksi.bahaya ? 'text-bahaya' : 'text-teks'"
        @click="pilih(aksi)"
      >
        {{ aksi.label }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EllipsisVertical } from '@lucide/vue'

interface ItemMenu {
  label: string
  bahaya?: boolean
  aksi: () => void
}

withDefaults(
  defineProps<{
    item: ItemMenu[]
    ariaLabel?: string
  }>(),
  { ariaLabel: 'Aksi lain' },
)

const terbuka = ref(false)

function pilih(aksi: ItemMenu) {
  terbuka.value = false
  aksi.aksi()
}

// Tutup saat klik di luar
function tutupDiLuar(event: MouseEvent) {
  if (!terbuka.value) return
  if (!(event.target as HTMLElement).closest('[aria-haspopup="menu"]')) terbuka.value = false
}

onMounted(() => document.addEventListener('click', tutupDiLuar))
onBeforeUnmount(() => document.removeEventListener('click', tutupDiLuar))
</script>
