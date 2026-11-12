<template>
  <div>
    <div v-if="memuat" class="mx-auto w-full max-w-baca animate-pulse space-y-4 pt-2">
      <div class="h-9 w-3/4 rounded-full bg-permukaan" />
      <div class="h-4 w-52 rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
    </div>

    <div v-else-if="galat" class="mx-auto w-full max-w-baca">
      <AppAlert variant="bahaya" title="Materi belum bisa dimuat">{{ galat }}</AppAlert>
    </div>

    <template v-else-if="data">
      <!-- Revisi draf -->
      <FormMateri
        v-if="sedangUbah"
        ref="formRef"
        :judul-awal="data.revisi?.judul ?? data.materi.judul"
        :ringkasan-awal="data.revisi?.ringkasan ?? data.materi.ringkasan"
        :sampul-awal-path="data.sampul_path"
        :sampul-awal-url="data.revisi?.sampul ?? data.materi.sampul"
        :kategori-awal="data.kategori_ids"
        :konten-awal="data.revisi?.konten"
        :html-awal="data.revisi?.konten_html"
        :catatan-awal="data.revisi?.catatan_revisi"
        :status="data.revisi?.status ?? data.materi.status"
        :versi="data.revisi?.nomor_versi ?? null"
        :keterangan="keteranganSimpan"
        tampilkan-catatan
        :memuat="memuatSimpan"
        :galat-umum="galatUmum"
        :galat-isian="galatIsian"
        @simpan="kirim"
      >
        <!-- Materi sedang terbit -->
        <template v-if="sedangTerbit" #pemberitahuan>
          <AppAlert variant="aksen" title="Materi ini sedang tayang untuk umum" class="mt-6">
            Perubahan yang kamu buat aman tersimpan, tapi pengunjung masih melihat versi lama.
            <template v-if="menungguPenarikan">
              Permintaanmu untuk menurunkan materi ini lagi dicek admin. Begitu disetujui, kamu bisa
              langsung ajukan versi terbarumu.
            </template>
            <template v-else>
              Kalau mau versi barumu yang tayang, turunkan dulu materi ini dari halaman umum.
            </template>
          </AppAlert>
        </template>

        <template #aksi>
          <AppButton variant="sekunder" size="sm" :loading="memuatSimpan" @click="formRef?.kirim()">
            Simpan
          </AppButton>

          <AppButton v-if="bisaAjukan" size="sm" @click="modalAjukan = true">Ajukan supaya tayang</AppButton>

          <AppButton v-if="sedangTerbit && !menungguPenarikan" size="sm" @click="modalTarik = true">
            Turunkan dari halaman umum
          </AppButton>

          <AppButton
            v-if="menungguPenarikan"
            variant="sekunder"
            size="sm"
            :loading="memuatAksi"
            @click="batalTarikSekarang"
          >
            Batal turunkan
          </AppButton>

          <AppButton v-if="bisaHapus" variant="bahaya" size="sm" @click="modalHapus = true">
            Hapus materi
          </AppButton>
        </template>
      </FormMateri>

      <!-- Revisi bukan draf -->
      <template v-else>
        <div class="mx-auto w-full max-w-baca">
          <div class="flex flex-wrap items-center gap-3">
            <StatusChip
              :status="data.revisi?.status ?? data.materi.status"
              :versi="data.revisi?.nomor_versi ?? null"
            />
          </div>

          <div class="pb-6">
            <AppAlert v-if="data.revisi" variant="aksen" :title="judulKunci" class="mt-6">
              {{ pesanTidakDapatDiubah }}
            </AppAlert>

            <h1 class="pt-8 font-serif text-judul-hp font-bold text-teks-kuat lg:text-judul">
              {{ data.revisi?.judul ?? data.materi.judul }}
            </h1>

            <p v-if="data.revisi?.ringkasan" class="mt-4 font-serif text-dek text-teks-lembut">
              {{ data.revisi.ringkasan }}
            </p>

            <AppAlert v-if="data.revisi === null" variant="peringatan" title="Isi materi tidak ditemukan" class="mt-8">
              Coba buka lagi dari halaman Materi saya ya.
            </AppAlert>

            <template v-else>
              <NuxtLink
                :to="`/materi/${data.materi.slug}`"
                class="mt-6 inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
              >
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                Lihat halaman materi
              </NuxtLink>

              <div class="mt-10 border-t border-garis pt-8">
                <IsiMateri :html="data.revisi.konten_html" :catatan="data.revisi.catatan_revisi" />
              </div>
            </template>
          </div>

          <div class="decide">
            <span class="text-[13px] text-teks-redup">{{ teksSimpanHanyaBaca }}</span>
            <span class="tumbuh" />

            <AppButton
              v-if="data.revisi?.status === 'diajukan'"
              variant="sekunder"
              size="sm"
              :loading="memuatAksi"
              @click="batalAjukanSekarang"
            >
              Batalkan pengajuan
            </AppButton>

            <AppButton v-if="bisaBuatRevisi" size="sm" :loading="memuatAksi" @click="buatRevisiSekarang">
              Ubah
            </AppButton>

            <AppButton v-if="sedangTerbit && !menungguPenarikan" variant="sekunder" size="sm" @click="modalTarik = true">
              Turunkan dari halaman umum
            </AppButton>

            <AppButton v-if="menungguPenarikan" variant="sekunder" size="sm" :loading="memuatAksi" @click="batalTarikSekarang">
              Batal turunkan
            </AppButton>

            <AppButton v-if="bisaHapus" variant="bahaya" size="sm" @click="modalHapus = true">
              Hapus materi
            </AppButton>
          </div>
        </div>
      </template>

      <DialogAjukanPublikasi
        v-model:open="modalAjukan"
        :materi-id="data.materi.id"
        :id-revisi="data.revisi?.id ?? null"
        :nomor-revisi="data.revisi?.nomor_versi ?? null"
        :id-versi-publik="data.versi_publik_terakhir?.id ?? null"
        @diajukan="setelahAjukan"
      />

      <!-- Ajukan penarikan dari publik -->
      <ModalTarikMateri v-model:open="modalTarik" :materi-id="data.materi.id" @diajukan="setelahTarik" />
    </template>

    <!-- Konfirmasi hapus -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus materi ini?"
      deskripsi="Materi ini belum pernah tayang, jadi bisa dihapus. Setelah dihapus, materinya hilang untuk selamanya."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Belum berhasil dihapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ data?.materi.judul }}</p>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatHapus" @click="modalHapus = false">
          Batal
        </AppButton>
        <AppButton variant="bahaya" :loading="memuatHapus" @click="hapusSekarang">
          Ya, hapus
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft } from '@lucide/vue'
import type { DataEditor, PayloadMateri } from '~/types/api'

definePageMeta({ layout: 'anggota', middleware: ['auth', 'bukan-admin'] })
useHead({ title: 'Ubah Materi' })

const route = useRoute()
const { dataEditor, ubah, hapus, batalAjukan, buatRevisi, batalTarik } = useMateri()
const { tampilkan } = usePesanToast()
const id = route.params.id as string

const formRef = ref<{ kirim: () => void; tandaiTersimpan: () => void } | null>(null)

const data = ref<DataEditor | null>(null)
const memuat = ref(true)
const galat = ref('')

const memuatSimpan = ref(false)
const galatUmum = ref('')
const galatIsian = ref<{
  judul?: string
  ringkasan?: string
  sampul?: string
  kategori?: string
  konten?: string
  catatan_revisi?: string
}>({})

const modalHapus = ref(false)
const memuatHapus = ref(false)
const galatHapus = ref('')

const modalAjukan = ref(false)
const modalTarik = ref(false)
const memuatAksi = ref(false)

const sedangUbah = computed(() => data.value?.revisi?.status === 'draf')

const sedangTerbit = computed(() => data.value?.materi.status === 'terbit')

const menungguPenarikan = computed(() => data.value?.materi.penarikan?.status === 'diajukan')

const bisaAjukan = computed(() => sedangUbah.value && !sedangTerbit.value)

const bisaBuatRevisi = computed(
  () => data.value?.revisi?.status === 'disetujui' || data.value?.revisi?.status === 'ditolak',
)

const bisaHapus = computed(
  () => data.value?.materi.status === 'draf' && !data.value.materi.diterbitkan_pada,
)

const pesanTidakDapatDiubah = computed(() => {
  const revisi = data.value?.revisi
  if (!revisi) return ''

  if (revisi.status === 'diajukan') {
    return 'Selama dicek, isinya belum bisa diubah. Kamu tinggal tunggu kabar dari admin, nanti kami kasih notifikasi. Kalau masih mau mengubah sesuatu, batalkan dulu pengajuannya.'
  }

  if (revisi.status === 'disetujui') {
    return 'Mau memperbarui isinya? Tekan "Ubah". Perubahanmu tersimpan terpisah, jadi pengunjung tetap melihat versi ini.'
  }

  if (revisi.status === 'ditolak') {
    return 'Cek catatan dari admin di bawah, lalu tekan "Ubah" untuk memperbaikinya. Setelah itu kamu bisa ajukan lagi.'
  }

  return 'Isi materi ini belum bisa diubah.'
})

const judulKunci = computed(() => {
  const status = data.value?.revisi?.status

  if (status === 'diajukan') return 'Materi ini lagi dicek admin'
  if (status === 'disetujui') return 'Versi ini sudah tayang untuk umum'
  if (status === 'ditolak') return 'Materi ini belum disetujui admin'

  return 'Isi materi ini belum bisa diubah'
})

const keteranganSimpan = computed(() => {
  const status = data.value?.revisi?.status
  if (!status) return ''

  return status === 'disetujui' ? 'Tayang untuk umum' : labelStatusRevisi(status)
})

const teksSimpanHanyaBaca = computed(() => {
  const status = data.value?.revisi?.status

  if (status === 'diajukan') return 'Lagi dicek admin'
  if (status === 'disetujui') return menungguPenarikan.value ? 'Lagi dicek admin untuk diturunkan' : 'Sudah tayang'
  if (status === 'ditolak') return 'Perlu diperbaiki'

  return ''
})

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await dataEditor(id)
    data.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

async function kirim(payload: PayloadMateri) {
  memuatSimpan.value = true
  galatUmum.value = ''
  galatIsian.value = {}

  try {
    const respons = await ubah(id, payload)

    data.value = respons.data
    formRef.value?.tandaiTersimpan()
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatIsian.value = {
      judul: isian.judul?.[0],
      ringkasan: isian.ringkasan?.[0],
      sampul: isian.sampul?.[0],
      kategori: isian.kategori?.[0] ?? isian['kategori.0']?.[0],
      konten: isian.konten?.[0] ?? isian['konten.ops']?.[0] ?? isian.konten_html?.[0],
      catatan_revisi: isian.catatan_revisi?.[0],
    }

    if (!Object.values(galatIsian.value).some(Boolean)) {
      galatUmum.value = pesanGalat(galatServer)
      tampilkan(galatUmum.value, 'bahaya')
    }
  } finally {
    memuatSimpan.value = false
  }
}

function setelahAjukan(respons: { message: string; data: DataEditor }) {
  data.value = respons.data
  tampilkan(respons.message, 'sukses')
}

// Setelah tarik
async function setelahTarik(respons: { message: string }) {
  tampilkan(respons.message, 'sukses')
  await muat()
}

// Jalankan aksi status
async function jalankanAksi(aksi: () => Promise<{ message: string; data?: unknown }>, bawaan: string) {
  memuatAksi.value = true

  try {
    const respons = await aksi()
    tampilkan(respons.message, 'sukses')
    await muat()
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer, bawaan), 'bahaya')
  } finally {
    memuatAksi.value = false
  }
}

const batalAjukanSekarang = () => jalankanAksi(() => batalAjukan(id), 'Pengajuannya belum berhasil dibatalkan. Coba lagi ya.')
const buatRevisiSekarang = () => jalankanAksi(() => buatRevisi(id), 'Belum bisa membuka versi baru untuk diubah. Coba lagi ya.')
const batalTarikSekarang = () => jalankanAksi(() => batalTarik(id), 'Permintaan turun belum berhasil dibatalkan. Coba lagi ya.')

async function hapusSekarang() {
  galatHapus.value = ''
  memuatHapus.value = true

  try {
    await hapus(id)
    await navigateTo({ path: '/materi-saya', query: { tab: 'draf', sukses: 'dihapus' } })
  } catch (galatServer) {
    galatHapus.value = pesanGalat(galatServer)
  } finally {
    memuatHapus.value = false
  }
}

onMounted(muat)
</script>
