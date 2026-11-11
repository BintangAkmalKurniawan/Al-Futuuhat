<template>
  <!-- Halaman baca publik: hanya isi versi publik, tanpa riwayat & forum (KP-17.4). -->
  <div class="mx-auto w-full max-w-baca">
    <NuxtLink
      to="/publikasi"
      class="inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Semua artikel
    </NuxtLink>

    <!-- Kerangka memuat berbentuk seperti judul dan paragraf. -->
    <div v-if="memuat" class="mt-8 animate-pulse space-y-4">
      <div class="h-9 w-3/4 rounded-full bg-permukaan" />
      <div class="h-4 w-40 rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
      <div class="h-3 w-2/3 rounded-full bg-permukaan" />
    </div>

    <AppAlert v-else-if="galat" variant="bahaya" title="Tulisan belum bisa dibuka" class="mt-8">
      {{ galat }}
    </AppAlert>

    <template v-else-if="materi">
      <ArticleHeader
        class="mt-8"
        :judul="materi.judul"
        :ringkasan="materi.ringkasan"
        :penulis="materi.penulis"
        :meta="metaByline"
        :kategori="materi.kategori"
        ke-kategori="/publikasi"
      />

      <!-- Bagikan artikel (KF-22) dan atur ukuran tulisan. -->
      <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
        <TombolBagikan :judul="materi.judul" />
        <div class="flex flex-wrap items-center gap-2">
          <PemutarSuara :judul="materi.judul" :html="materi.konten_html" />
          <PengaturUkuranTeks />
        </div>
      </div>

      <div class="mt-6 border-t border-garis pt-10" :style="gayaArtikel">
        <IsiMateri :html="materi.konten_html" />
      </div>

      <!-- Penutup: penulis, keterangan peninjauan, lalu ajakan bergabung
           sebagai pengganti forum (DESIGN.md §6.1 no. 03). -->
      <footer class="mt-14 border-t border-garis pt-7">
        <div class="flex flex-wrap items-center gap-3.5">
          <AvatarInisial :nama="materi.penulis?.nama" ukuran="besar" />

          <div class="min-w-0">
            <p class="text-[13px] text-teks-redup">Ditulis oleh</p>
            <p class="text-teks-kuat">{{ materi.penulis?.nama || 'Tanpa penulis' }}</p>
          </div>

          <p class="ml-auto text-[13px] text-teks-redup">
            Ditinjau dan disetujui pengurus Al-Futuuhat
          </p>
        </div>

        <AppAlert variant="info" class="mt-7">
          <p class="text-teks-kuat">Diskusi tulisan ini ada di ruang anggota.</p>
          <p class="mt-0.5">
            Yuk, gabung jadi anggota supaya bisa ikut menulis dan berdiskusi.
            <NuxtLink
              to="/daftar"
              class="text-aksen transition-colors duration-200 hover:text-teks-kuat"
            >
              Daftar →
            </NuxtLink>
          </p>
        </AppAlert>
      </footer>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft } from '@lucide/vue'
import type { MateriPublikDetail } from '~/types/api'

definePageMeta({ layout: 'publik' })

const route = useRoute()
const { publikDetail } = useMateri()
const { gaya: gayaArtikel } = useUkuranArtikel()

const materi = ref<MateriPublikDetail | null>(null)
const memuat = ref(true)
const galat = ref('')

const metaByline = computed(() => {
  const bagian = [formatTanggal(materi.value?.diterbitkan_pada)]
  const menit = menitBaca(materi.value?.konten_html)

  if (menit) bagian.push(`${menit} menit baca`)

  return bagian.join(' · ')
})

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await publikDetail(String(route.params.slug))
    materi.value = respons.data
  } catch (galatServer) {
    materi.value = null
    galat.value = pesanGalat(galatServer, 'Tulisannya tidak ketemu. Mungkin sudah dihapus.')
  } finally {
    memuat.value = false
  }
}

useHead(() => ({ title: materi.value?.judul ?? 'Publikasi' }))

onMounted(muat)
</script>
