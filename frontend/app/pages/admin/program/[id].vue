<template>
  <!-- Kelola satu program -->
  <div class="mx-auto max-w-4xl">
    <NuxtLink
      to="/admin/program"
      class="inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Semua program
    </NuxtLink>

    <div v-if="memuat" class="animate-pulse space-y-4 pt-6">
      <div class="h-8 w-1/2 rounded-full bg-permukaan" />
      <div class="h-11 rounded-lg bg-permukaan" />
      <div class="h-56 rounded-lg bg-permukaan" />
    </div>

    <AppAlert v-else-if="galat" variant="bahaya" title="Program tidak dapat dibuka" class="mt-6">
      {{ galat }}
    </AppAlert>

    <template v-else-if="program">
      <header class="flex flex-wrap items-end justify-between gap-4 pt-4 pb-6">
        <div class="min-w-0">
          <h1 class="font-serif text-halaman font-bold text-teks-kuat">{{ program.nama }}</h1>
          <div class="mt-3 flex items-center gap-3">
            <LencanaStatusProgram :status="program.status" />
            <span class="text-[13px] text-teks-redup">/program/{{ program.slug }}</span>
          </div>
        </div>

        <NuxtLink
          :to="`/program/${program.slug}`"
          target="_blank"
          class="inline-flex items-center gap-1.5 text-[13px] text-aksen transition-colors duration-200 hover:text-teks-kuat"
        >
          Lihat halaman publik
          <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
        </NuxtLink>
      </header>

      <AppTabs v-model="tab" :tabs="daftarTab" />

      <div class="pt-8">
        <FormProgram v-if="tab === 'informasi'" :key="kunciForm" :program="program" @tersimpan="setelahSimpan" />

        <div v-else class="space-y-10">
          <UnggahDokumentasi :program-id="program.id" @terunggah="setelahUnggah" />

          <GaleriDokumentasi
            ref="galeri"
            :slug="program.slug"
            :jumlah-foto="program.jumlah_foto"
            :jumlah-video="program.jumlah_video"
            bisa-hapus
            @berubah="muatJumlah"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, ExternalLink } from '@lucide/vue'
import type { ProgramAdmin } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })

const route = useRoute()
const router = useRouter()
const { detailAdmin } = useProgram()
const { tampilkan } = usePesanToast()

const program = ref<ProgramAdmin | null>(null)
const memuat = ref(true)
const galat = ref('')
const kunciForm = ref(0)
const galeri = ref<{ muatUlang: () => Promise<void> } | null>(null)

const tab = ref(route.query.tab === 'dokumentasi' ? 'dokumentasi' : 'informasi')

const daftarTab = computed(() => [
  { kunci: 'informasi', label: 'Informasi' },
  {
    kunci: 'dokumentasi',
    label: 'Dokumentasi',
    jumlah: (program.value?.jumlah_foto ?? 0) + (program.value?.jumlah_video ?? 0),
  },
])

watch(tab, (nilai) => {
  router.replace({ query: nilai === 'dokumentasi' ? { tab: 'dokumentasi' } : {} })
})

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await detailAdmin(String(route.params.id))
    program.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer, 'Program tidak ditemukan.')
  } finally {
    memuat.value = false
  }
}

// Muat jumlah dokumentasi
async function muatJumlah() {
  try {
    const respons = await detailAdmin(String(route.params.id))

    if (program.value) {
      program.value.jumlah_foto = respons.data.jumlah_foto
      program.value.jumlah_video = respons.data.jumlah_video
    }
  } catch {
  }
}

async function setelahUnggah() {
  await Promise.all([galeri.value?.muatUlang(), muatJumlah()])
}

function setelahSimpan(baru: ProgramAdmin, pesan: string) {
  program.value = { ...program.value, ...baru }
  kunciForm.value++
  tampilkan(pesan, 'sukses')
}

useHead(() => ({ title: program.value?.nama ?? 'Kelola Program' }))

onMounted(muat)
</script>
