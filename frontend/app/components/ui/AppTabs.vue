<template>
  <!-- Tab berupa teks dengan garis bawah tipis pada tab aktif (DESIGN.md §5).
       Selalu satu baris; bila tidak muat (ponsel), baris tab dapat digeser mendatar
       seperti pil kategori. Garis dasar dibuat dengan bayangan inset (bukan border)
       agar garis tab aktif tetap terlihat di dalam wadah yang dapat digulir. -->
  <div
    ref="wadah"
    class="relative flex gap-6 overflow-x-auto shadow-[inset_0_-1px_0_var(--color-garis)] [scrollbar-width:none]"
    role="tablist"
  >
    <button
      v-for="tab in tabs"
      :key="tab.kunci"
      type="button"
      role="tab"
      :aria-selected="model === tab.kunci"
      class="shrink-0 border-b pb-2.5 text-sm whitespace-nowrap transition-colors duration-200"
      :class="
        model === tab.kunci
          ? 'border-teks-kuat font-medium text-teks-kuat'
          : 'border-transparent text-teks-lembut hover:text-teks-kuat'
      "
      @click="model = tab.kunci"
    >
      {{ tab.label }}<span v-if="tab.jumlah !== undefined" class="ml-1.5 text-teks-redup">{{ tab.jumlah }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
interface Tab {
  kunci: string
  label: string
  /** Angka jumlah di sebelah label (opsional). */
  jumlah?: number
}

defineProps<{ tabs: Tab[] }>()

const model = defineModel<string>({ required: true })

const wadah = ref<HTMLElement | null>(null)

// Geser baris tab (hanya mendatar, halaman tidak ikut bergulir) agar tab yang aktif
// selalu terlihat, mis. saat halaman dibuka dari URL ?tab=terbit di ponsel.
function tampilkanTabAktif() {
  const baris = wadah.value
  const aktif = baris?.querySelector<HTMLElement>('[aria-selected="true"]')
  if (!baris || !aktif) return

  const kiri = aktif.offsetLeft
  const kanan = kiri + aktif.offsetWidth
  const terlihat = kiri >= baris.scrollLeft && kanan <= baris.scrollLeft + baris.clientWidth

  if (!terlihat) baris.scrollTo({ left: kiri - 24, behavior: 'smooth' })
}

watch(model, () => nextTick(tampilkanTabAktif))
onMounted(tampilkanTabAktif)
</script>
