<template>
  <!-- Halaman satu program: penjelasan rinci + dokumentasi (KF-21, KP-21.5, KP-21.6). -->
  <div>
    <NuxtLink
      to="/program"
      class="inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Semua program
    </NuxtLink>

    <!-- Kerangka memuat berbentuk judul, sampul, dan paragraf. -->
    <div v-if="memuat" class="mt-8 max-w-baca animate-pulse space-y-4">
      <div class="h-5 w-20 rounded-full bg-permukaan" />
      <div class="h-9 w-3/4 rounded-full bg-permukaan" />
      <div class="h-4 w-1/2 rounded-full bg-permukaan" />
      <div class="aspect-[16/9] w-full rounded-lg bg-permukaan" />
    </div>

    <!-- Slug tidak dikenal: tampilan 404 yang sama dengan halaman lain. -->
    <AppEmptyState
      v-else-if="tidakDitemukan"
      title="Programnya tidak ketemu."
      description="Mungkin sudah dihapus atau tautannya keliru."
    >
      <template #action>
        <NuxtLink to="/program" class="btn btn-sekunder btn-sm">Lihat semua program</NuxtLink>
      </template>
    </AppEmptyState>

    <AppAlert v-else-if="galat" variant="bahaya" title="Program belum bisa dibuka" class="mt-8">
      {{ galat }}
    </AppAlert>

    <template v-else-if="program">
      <header class="mt-8 max-w-baca">
        <LencanaStatusProgram :status="program.status" />

        <h1 class="mt-4 font-serif text-judul-hp font-bold text-teks-kuat md:text-judul">
          {{ program.nama }}
        </h1>

        <p v-if="program.ringkasan" class="mt-3 font-serif text-dek text-teks-lembut">
          {{ program.ringkasan }}
        </p>

        <dl class="mt-6 space-y-2 text-sm">
          <div v-if="program.jadwal" class="flex items-start gap-2.5">
            <dt class="flex items-center gap-2 text-teks-redup">
              <CalendarDays class="h-4 w-4" aria-hidden="true" />
              <span class="sr-only">Jadwal</span>
            </dt>
            <dd class="text-teks">{{ program.jadwal }}</dd>
          </div>

          <div v-if="program.pic.length" class="flex items-start gap-2.5">
            <dt class="flex items-center gap-2 pt-0.5 text-teks-redup">
              <Users class="h-4 w-4" aria-hidden="true" />
              <span class="sr-only">Penanggung jawab</span>
            </dt>
            <dd class="flex flex-wrap items-center gap-x-4 gap-y-2">
              <span class="text-teks-redup">PIC</span>
              <span
                v-for="(orang, indeks) in program.pic"
                :key="indeks"
                class="inline-flex items-center gap-2 text-teks-kuat"
              >
                <AvatarInisial :nama="orang.nama" :id="indeks" ukuran="kecil" />
                {{ orang.nama }}
              </span>
            </dd>
          </div>
        </dl>

        <TombolBagikan :judul="program.nama" class="mt-6" />
      </header>

      <img
        v-if="program.sampul"
        :src="program.sampul"
        :alt="`Sampul program ${program.nama}`"
        class="mt-8 aspect-[16/9] w-full max-w-4xl rounded-lg border border-garis object-cover"
      />

      <AppTabs v-model="tab" :tabs="daftarTab" class="mt-10" />

      <section v-if="tab === 'tentang'" class="max-w-baca pt-8">
        <!-- Penjelasan ditulis admin dengan editor Quill dan sudah disanitasi backend. -->
        <article class="prosa" v-html="program.deskripsi" />
      </section>

      <section v-else class="pt-8">
        <GaleriDokumentasi
          :slug="program.slug"
          :jumlah-foto="program.jumlah_foto"
          :jumlah-video="program.jumlah_video"
        />
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, CalendarDays, Users } from '@lucide/vue'
import type { ProgramDetail } from '~/types/api'

definePageMeta({ layout: 'publik' })

const route = useRoute()
const router = useRouter()
const { detailPublik } = useProgram()

const program = ref<ProgramDetail | null>(null)
const memuat = ref(true)
const galat = ref('')
const tidakDitemukan = ref(false)

// Tab tersimpan di URL (?tab=dokumentasi) agar tautannya dapat dibagikan.
const tab = ref(route.query.tab === 'dokumentasi' ? 'dokumentasi' : 'tentang')

const daftarTab = computed(() => [
  { kunci: 'tentang', label: 'Tentang program' },
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
  tidakDitemukan.value = false

  try {
    const respons = await detailPublik(String(route.params.slug))
    program.value = respons.data
  } catch (galatServer) {
    program.value = null

    const status = (galatServer as { response?: { status?: number } }).response?.status

    if (status === 404) tidakDitemukan.value = true
    else galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

useHead(() => ({ title: program.value?.nama ?? 'Program' }))

onMounted(muat)
</script>
