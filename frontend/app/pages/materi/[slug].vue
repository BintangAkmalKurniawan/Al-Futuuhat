<template>
  <div>
    <NuxtLink
      to="/materi"
      class="inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
    >
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Arsip materi
    </NuxtLink>

    <!-- Kerangka memuat berbentuk seperti judul dan paragraf. -->
    <div v-if="memuat" class="mt-8 animate-pulse space-y-4">
      <div class="h-9 w-3/4 rounded-full bg-permukaan" />
      <div class="h-4 w-40 rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
    </div>

    <AppAlert v-else-if="galat" variant="bahaya" title="Materi belum bisa dibuka" class="mt-8">
      {{ galat }}
    </AppAlert>

    <template v-else-if="materi">
      <div class="mx-auto w-full max-w-baca">
        <ArticleHeader
          class="mt-8"
          :judul="versiTampil?.judul ?? materi.judul"
          :ringkasan="versiTampil ? versiTampil.ringkasan : materi.ringkasan"
          :penulis="materi.penulis"
          :meta="metaByline"
          :status="materi.status"
          :versi="materi.revisi?.nomor_versi"
          :kategori="versiTampil?.kategori ?? materi.kategori"
          ke-kategori="/materi"
        />

        <!-- Anggota dapat membaca materi yang belum terbit; pengunjung tidak. -->
        <AppAlert
          v-if="materi.status !== 'terbit'"
          variant="peringatan"
          :title="judulBannerAkses"
          class="mt-8"
        >
          {{ isiBannerAkses }}
        </AppAlert>

        <div class="mt-8">
          <ActionBar
            :jumlah-komentar="materi.jumlah_komentar ?? 0"
            :catatan="catatanVersi"
            :versi-publik="tautanVersiPublik"
            @buka-diskusi="bukaDiskusi"
            @buka-riwayat="bukaRiwayat"
            @buka-versi-publik="bukaVersiPublik"
          />
        </div>

        <!-- Isi versi lama tidak ikut terkirim di daftar riwayat, jadi baris ini
             menandai bahwa yang tampil bukan versi terbaru (KP-13.4). -->
        <div
          v-if="versiArsip"
          class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-garis bg-angkat px-4 py-3"
        >
          <p class="text-[13px] text-teks-kuat">
            {{ membacaVersiPublik ? 'Kamu sedang membaca versi yang tayang untuk umum' : `Kamu sedang membaca versi lama (v${versiArsip.nomor_versi})` }}
            <span class="text-teks-lembut">· {{ formatTanggal(versiArsip.dibuat_pada) }}</span>
          </p>

          <button
            type="button"
            class="ml-auto inline-flex items-center gap-2 text-[13px] text-aksen transition-colors duration-200 hover:underline"
            @click="kembaliKeTerbaru"
          >
            <Undo2 class="h-4 w-4" aria-hidden="true" />
            {{ teksKembali }}
          </button>
        </div>

        <!-- Ukuran tulisan isi materi dapat diatur pembaca (KF-22). -->
        <div class="flex flex-wrap justify-end gap-2 pt-6">
          <PemutarSuara :judul="versiTampil?.judul ?? materi.judul" :html="versiTampil?.konten_html" />
          <PengaturUkuranTeks />
        </div>

        <div id="isi-materi" class="pt-4" :style="gayaArtikel">
          <IsiMateri :html="versiTampil?.konten_html" :catatan="versiTampil?.catatan_revisi" />
        </div>
      </div>

      <!-- Diskusi dan riwayat versi tampil sebagai laci kanan, bukan bagian
           halaman (DESIGN.md §6.2 no. 09–10). -->
      <PanelDiskusi
        v-model:open="panelDiskusi"
        :materi-id="materi.id"
        :materi-penulis-id="materi.penulis?.id ?? null"
      />

      <RiwayatVersi
        v-model:open="panelRiwayat"
        :daftar="materi.riwayat ?? []"
        :aktif-id="versiTampil?.id ?? null"
        :memuat-id="idArsipSedangDimuat"
        @buka-versi="bukaVersi"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, Undo2 } from '@lucide/vue'
import type { MateriDetail, Revisi } from '~/types/api'

// Halaman baca anggota: laci riwayat versi, laci diskusi, dan label status internal.
definePageMeta({ layout: 'anggota', middleware: ['auth'] })

const route = useRoute()
const { detail, versi } = useMateri()
const { tampilkan } = usePesanToast()

const materi = ref<MateriDetail | null>(null)
const memuat = ref(true)
const galat = ref('')
const panelRiwayat = ref(false)
const panelDiskusi = ref(false)

// Versi lama yang isinya sedang dibaca (KP-13.4); null = revisi terakhir.
const versiArsip = ref<Revisi | null>(null)
const { gaya: gayaArtikel } = useUkuranArtikel()
const idArsipSedangDimuat = ref<number | null>(null)

// Isi yang tampil di kolom baca: versi arsip bila ada, jika tidak revisi terakhir.
const versiTampil = computed(() => versiArsip.value ?? materi.value?.revisi ?? null)

// KP-16.4: tampilkan keterangan bila versi publik berbeda dari revisi terakhir.
// `versi_publik` hanya terisi selama materi terbit (Draf 2.3).
const versiPublikBerbeda = computed(() => {
  const publik = materi.value?.versi_publik
  const terakhir = materi.value?.revisi

  return Boolean(publik && terakhir && publik.id !== terakhir.id)
})

// Tautan "Versi publik: vN" di ActionBar; disembunyikan saat sedang membaca versi lain.
const tautanVersiPublik = computed(() =>
  versiPublikBerbeda.value && !versiArsip.value ? (materi.value?.versi_publik?.nomor_versi ?? null) : null,
)

// Sedang membaca versi publik (bukan arsip lain dari riwayat).
const membacaVersiPublik = computed(
  () => Boolean(versiArsip.value && versiArsip.value.id === materi.value?.versi_publik?.id),
)

const teksKembali = computed(() => {
  const terakhir = materi.value?.revisi
  if (!terakhir) return 'Kembali ke versi terbaru'

  return 'Kembali ke versi terbaru'
})

// Materi yang pernah terbit lalu ditarik dari publik (KF-20).
const sudahDitarik = computed(
  () => materi.value?.status === 'draf' && Boolean(materi.value?.diterbitkan_pada),
)

// Banner untuk materi yang belum tayang: pesannya dipecah per keadaan (Panduan Bahasa UI).
const judulBannerAkses = computed(() => {
  if (sudahDitarik.value) return 'Materi ini sudah diturunkan dari halaman umum'
  if (materi.value?.status === 'diajukan') return 'Materi ini lagi dicek admin'

  return 'Materi ini belum tayang untuk umum'
})

const isiBannerAkses = computed(() => {
  if (sudahDitarik.value) return 'Sekarang hanya anggota yang bisa membacanya.'
  if (materi.value?.status === 'diajukan') {
    return 'Saat ini hanya anggota yang bisa membacanya. Kalau disetujui, materi ini akan tayang untuk umum.'
  }
  if (materi.value?.status === 'ditolak') {
    return 'Admin belum menyetujuinya, jadi saat ini hanya anggota yang bisa membacanya.'
  }

  return 'Saat ini hanya anggota yang bisa membacanya.'
})

const menungguPenarikan = computed(() => materi.value?.penarikan?.status === 'diajukan')

const metaByline = computed(() => {
  const bagian = [
    materi.value?.diterbitkan_pada
      ? `Tayang sejak ${formatTanggal(materi.value.diterbitkan_pada)}`
      : `Diperbarui ${formatTanggal(materi.value?.revisi?.dibuat_pada)}`,
  ]

  const menit = menitBaca(materi.value?.revisi?.konten_html)

  if (menit) bagian.push(`${menit} menit baca`)
  // Nomor versi tidak ditampilkan di sini; cukup di Riwayat perubahan (Panduan Bahasa UI).

  return bagian.join(' · ')
})

// Catatan kanan ActionBar (KP-16.4, DESIGN.md §5). Saat membaca arsip, baris
// arsip di atas isi yang menjelaskan, jadi catatan ini disembunyikan.
const catatanVersi = computed(() => {
  if (versiArsip.value) return null

  const bagian: string[] = []

  if (versiPublikBerbeda.value) bagian.push('Kamu sedang membaca perubahan yang belum tayang')
  if (menungguPenarikan.value) bagian.push('Lagi dicek admin untuk diturunkan')

  return bagian.length ? bagian.join(' · ') : null
})

/** Baca versi publik di kolom baca (KP-16.4), memakai mode baca arsip yang sama. */
function bukaVersiPublik() {
  const id = materi.value?.versi_publik?.id
  if (id) bukaVersi(id)
}

// Hanya satu laci terbuka pada satu waktu, supaya lapisan gelapnya tidak menumpuk.
function bukaDiskusi() {
  panelRiwayat.value = false
  panelDiskusi.value = true
}

function bukaRiwayat() {
  panelDiskusi.value = false
  panelRiwayat.value = true
}

/**
 * Buka isi satu versi (KP-13.4). Laci baru ditutup setelah isinya berhasil
 * diambil, supaya pengguna dapat mencoba lagi bila pengambilannya gagal.
 */
async function bukaVersi(id: number) {
  if (!materi.value) return

  idArsipSedangDimuat.value = id

  try {
    const respons = await versi(materi.value.id, id)
    versiArsip.value = respons.data
    panelRiwayat.value = false

    // Isi versi menggantikan artikel di kolom baca; antar pembaca ke atas isi.
    await nextTick()
    document.getElementById('isi-materi')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer, 'Isi versi ini belum bisa dibuka.'), 'bahaya')
  } finally {
    idArsipSedangDimuat.value = null
  }
}

/** Kembali membaca revisi terakhir (KP-16.4). */
function kembaliKeTerbaru() {
  versiArsip.value = null
}

async function muat() {
  memuat.value = true
  galat.value = ''
  versiArsip.value = null

  try {
    const respons = await detail(String(route.params.slug))
    materi.value = respons.data
  } catch (galatServer) {
    materi.value = null
    galat.value = pesanGalat(galatServer, 'Materinya tidak ketemu. Mungkin sudah dihapus.')
  } finally {
    memuat.value = false
  }
}

useHead(() => ({ title: materi.value?.judul ?? 'Materi' }))

onMounted(muat)
</script>
