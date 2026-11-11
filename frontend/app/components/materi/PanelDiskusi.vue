<template>
  <!-- Laci diskusi di sisi kanan (DESIGN.md §6.2 no. 09). Pola lacinya sama
       dengan RiwayatVersi, hanya lebih lebar karena memuat komposer dan balasan. -->
  <div v-if="open" class="fixed inset-0 z-40" role="dialog" aria-modal="true" :aria-labelledby="idJudul">
    <div class="absolute inset-0 bg-black/60" @click="tutup" />

    <aside
      class="absolute top-0 right-0 flex h-full w-[min(560px,100%)] flex-col border-l border-garis bg-permukaan"
    >
      <header class="flex items-center justify-between gap-4 border-b border-garis px-6 py-4">
        <h2 :id="idJudul" class="text-sm font-medium text-teks-kuat">Diskusi</h2>

        <button
          ref="tombolTutup"
          type="button"
          class="-m-1 rounded-full p-1 text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
          aria-label="Tutup diskusi"
          @click="tutup"
        >
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
      </header>

      <div class="flex-1 overflow-y-auto px-6 py-5">
        <KomentarForum :materi-id="materiId" :materi-penulis-id="materiPenulisId" dalam-panel />
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { X } from '@lucide/vue'

defineProps<{
  materiId: number
  /** Penulis materi; komentator dengannya diberi label "Penulis". */
  materiPenulisId?: number | null
}>()

const open = defineModel<boolean>('open', { default: false })

const idJudul = useId()
const tombolTutup = ref<HTMLButtonElement | null>(null)

function tutup() {
  open.value = false
}

// Tombol Escape ikut menutup laci.
function tekanTombol(event: KeyboardEvent) {
  if (event.key === 'Escape' && open.value) tutup()
}

// Fokus dipindah ke tombol tutup begitu laci terbuka, supaya penekanan Tab
// berikutnya tidak berjalan di halaman yang sedang tertutup lapisan gelap.
watch(open, async (terbuka) => {
  if (!terbuka) return

  await nextTick()
  tombolTutup.value?.focus()
})

onMounted(() => document.addEventListener('keydown', tekanTombol))
onBeforeUnmount(() => document.removeEventListener('keydown', tekanTombol))
</script>
