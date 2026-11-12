<template>
  <!-- Tentang kami -->
  <div class="mx-auto w-full max-w-baca">
    <header class="pb-8">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Tentang kami</h1>
      <p class="mt-3 font-serif text-dek text-teks-lembut">
        Mengenal Al-Futuuhat lebih dekat: cerita, visi, dan misi kami.
      </p>
    </header>

    <div v-if="memuat" class="animate-pulse space-y-4">
      <div class="h-3 w-full rounded-full bg-permukaan" />
      <div class="h-3 w-5/6 rounded-full bg-permukaan" />
      <div class="h-3 w-4/6 rounded-full bg-permukaan" />
    </div>

    <template v-else>
      <section class="border-t border-garis pt-10">
        <h2 class="text-[15px] text-teks-lembut">Cerita Al-Futuuhat</h2>
        <CeritaAlFutuuhat class="mt-6" :judul="pengaturan?.cerita_judul" :isi="ceritaIsi" />
      </section>

      <!-- Visi & misi -->
      <section v-if="pengaturan?.visi" class="mt-14 border-t border-garis pt-10">
        <h2 class="text-[15px] text-teks-lembut">Visi</h2>
        <p class="mt-6 font-serif text-subjudul leading-snug font-bold whitespace-pre-line text-teks-kuat">
          {{ pengaturan.visi }}
        </p>
      </section>

      <section v-if="daftarMisi.length" class="mt-14 border-t border-garis pt-10">
        <h2 class="text-[15px] text-teks-lembut">Misi</h2>
        <ol class="mt-6 space-y-4">
          <li v-for="(butir, i) in daftarMisi" :key="i" class="flex gap-4 font-serif text-badan text-teks">
            <span class="w-6 shrink-0 text-aksen">{{ i + 1 }}.</span>
            <span>{{ butir }}</span>
          </li>
        </ol>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { PengaturanBeranda } from '~/types/api'

definePageMeta({ layout: 'publik' })
useHead({ title: 'Tentang Kami' })

const { publik } = useBeranda()

const pengaturan = ref<PengaturanBeranda | null>(null)
const memuat = ref(true)

const ceritaIsi = computed(() => rapikanSpasiHtml(pengaturan.value?.cerita_isi ?? ''))

const daftarMisi = computed(() =>
  (pengaturan.value?.misi ?? '')
    .split('\n')
    .map((baris) => baris.trim())
    .filter(Boolean),
)

onMounted(async () => {
  try {
    pengaturan.value = (await publik()).data
  } catch {
    pengaturan.value = null
  } finally {
    memuat.value = false
  }
})
</script>
