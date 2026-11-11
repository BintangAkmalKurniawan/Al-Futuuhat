<template>
  <dialog
    ref="dialog"
    :aria-labelledby="idJudul"
    class="m-auto w-[calc(100%_-_2rem)] rounded-xl border border-garis bg-angkat p-0 text-teks shadow-[0_18px_48px_rgba(0,0,0,.55)] backdrop:bg-black/60"
    :class="lebar ? 'max-w-5xl' : 'max-w-md'"
    @cancel.prevent="tutup"
    @click="klikLatar"
  >
    <div class="flex max-h-[85vh] flex-col">
      <header class="flex items-start justify-between gap-4 px-6 pt-6">
        <div>
          <h2 :id="idJudul" class="text-lg text-teks-kuat">{{ title }}</h2>
          <p v-if="deskripsi" class="mt-1 text-sm text-teks-lembut">{{ deskripsi }}</p>
        </div>

        <button
          type="button"
          class="-m-1 rounded-full p-1 text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
          aria-label="Tutup dialog"
          @click="tutup"
        >
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
      </header>

      <div class="overflow-y-auto px-6 py-4 text-sm">
        <slot />
      </div>

      <footer v-if="$slots.footer" class="flex flex-wrap justify-end gap-2 px-6 pb-6">
        <slot name="footer" />
      </footer>
    </div>
  </dialog>
</template>

<script setup lang="ts">
import { X } from '@lucide/vue'

interface Props {
  title: string
  deskripsi?: string
  /** Dialog lebar (±1000 px), mis. untuk perbandingan versi (KP-10.7). */
  lebar?: boolean
}

defineProps<Props>()

// v-model:open="..." dari halaman pemakai.
const terbuka = defineModel<boolean>('open', { default: false })

const dialog = ref<HTMLDialogElement | null>(null)
const idJudul = useId()

function tutup() {
  terbuka.value = false
}

// Klik pada area gelap di luar kotak dialog ikut menutup.
function klikLatar(event: MouseEvent) {
  if (event.target === dialog.value) tutup()
}

// showModal() memberi jebakan fokus dan tombol Escape secara bawaan.
watch(terbuka, (nilai) => {
  const elemen = dialog.value
  if (!elemen) return

  if (nilai && !elemen.open) elemen.showModal()
  else if (!nilai && elemen.open) elemen.close()
})

onMounted(() => {
  if (terbuka.value) dialog.value?.showModal()
})
</script>
