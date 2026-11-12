<template>
  <!-- Antrean verifikasi -->
  <div>
    <header class="pb-7">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Pemeriksaan materi</h1>
      <p class="mt-3 text-sm text-teks-lembut">{{ deskripsiTab }}</p>
    </header>

    <AppTabs v-model="tab" :tabs="daftarTab" class="mb-6" />

    <AppAlert v-if="galat" variant="bahaya" title="Daftar pemeriksaan tidak dapat dimuat" class="mb-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat -->
    <div v-if="memuat" class="animate-pulse space-y-5 pt-6">
      <div v-for="n in 4" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <!-- Publikasi -->
    <template v-else-if="tab === 'publikasi'">
      <AppEmptyState
        v-if="antreanPublikasi.length === 0"
        title="Tidak ada materi yang menunggu pemeriksaan."
        description="Materi yang diajukan untuk tayang kepada umum akan muncul di sini."
      />

      <template v-else>
        <div class="overflow-x-auto">
          <table class="tabel">
            <thead>
              <tr>
                <th>Materi</th>
                <th>Penulis</th>
                <th>Versi</th>
                <th>Diajukan</th>
                <th class="kanan"><span class="sr-only">Aksi</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in antreanPublikasi" :key="item.id">
                <td>
                  <span class="judul">{{ item.judul ?? item.materi?.judul ?? 'Materi' }}</span>
                  <span class="sub">{{ keteranganPublikasi(item) }}</span>
                </td>
                <td>
                  <span class="orang">
                    <AvatarInisial :nama="item.materi?.penulis?.nama" :id="item.materi?.penulis?.id" ukuran="kecil" />
                    {{ item.materi?.penulis?.nama ?? '—' }}
                  </span>
                </td>
                <td>v{{ item.nomor_versi }}</td>
                <td>{{ formatTanggal(item.dibuat_pada) }}</td>
                <td class="kanan">
                  <AppButton size="sm" @click="navigateTo(`/admin/verifikasi-materi/${item.id}`)">
                    Tinjau
                  </AppButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pt-8">
          <AppPagination v-if="metaPublikasi" :meta="metaPublikasi" @ganti-halaman="halamanPublikasi = $event" />
        </div>
      </template>
    </template>

    <!-- Penarikan -->
    <template v-else>
      <AppEmptyState
        v-if="antreanPenarikan.length === 0"
        title="Tidak ada permintaan penurunan."
        description="Materi yang diminta penulisnya untuk diturunkan dari halaman umum akan muncul di sini."
      />

      <template v-else>
        <div class="overflow-x-auto">
          <table class="tabel">
            <thead>
              <tr>
                <th>Materi</th>
                <th>Penulis</th>
                <th>Alasan</th>
                <th>Diajukan</th>
                <th class="kanan"><span class="sr-only">Aksi</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in antreanPenarikan" :key="item.id">
                <td>
                  <NuxtLink :to="`/materi/${item.materi?.slug}`" class="judul hover:text-aksen">
                    {{ item.materi?.judul ?? 'Materi' }}
                  </NuxtLink>
                  <span class="sub">Masih tayang untuk umum</span>
                </td>
                <td>
                  <span class="orang">
                    <AvatarInisial :nama="item.materi?.penulis?.nama" :id="item.materi?.penulis?.id" ukuran="kecil" />
                    {{ item.materi?.penulis?.nama ?? '—' }}
                  </span>
                </td>
                <td class="max-w-xs">
                  <span class="line-clamp-2 text-teks" :title="item.alasan">{{ item.alasan }}</span>
                </td>
                <td>{{ formatTanggal(item.diajukan_pada) }}</td>
                <td class="kanan whitespace-nowrap">
                  <div class="inline-flex gap-2">
                    <AppButton variant="bahaya" size="sm" @click="mintaKeputusan(item, 'ditolak')">Tolak</AppButton>
                    <AppButton size="sm" @click="mintaKeputusan(item, 'disetujui')">Setujui</AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pt-8">
          <AppPagination v-if="metaPenarikan" :meta="metaPenarikan" @ganti-halaman="halamanPenarikan = $event" />
        </div>
      </template>
    </template>

    <!-- Konfirmasi keputusan penarikan -->
    <AppModal v-model:open="modalKeputusan" :title="judulKeputusan" :deskripsi="deskripsiKeputusan">
      <p class="text-sm text-teks-kuat">{{ dipilih?.materi?.judul }}</p>
      <p v-if="dipilih?.alasan" class="mt-1 text-sm text-teks-lembut">Alasan penulis: “{{ dipilih.alasan }}”</p>

      <AppTextarea
        v-if="keputusan === 'ditolak'"
        v-model="catatan"
        class="mt-4"
        label="Catatan untuk penulis"
        placeholder="Tuliskan alasan penolakan..."
        :rows="4"
        :error="galatCatatan"
        required
      />

      <template #footer>
        <AppButton variant="hantu" :disabled="memproses" @click="modalKeputusan = false">Batal</AppButton>
        <AppButton :variant="keputusan === 'ditolak' ? 'bahaya' : 'utama'" :loading="memproses" @click="putuskanSekarang">
          {{ keputusan === 'ditolak' ? 'Tolak permintaan' : 'Setujui permintaan' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import type { KeputusanVerifikasi, MetaHalaman, Penarikan, Revisi } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Verifikasi Publikasi' })

const route = useRoute()
const router = useRouter()
const { antrean, antreanPenarikan: ambilPenarikan, putuskanPenarikan } = useVerifikasi()
const { tampilkan } = usePesanToast()

const tab = ref(route.query.tab === 'penarikan' ? 'penarikan' : 'publikasi')

const antreanPublikasi = ref<Revisi[]>([])
const metaPublikasi = ref<MetaHalaman | null>(null)
const halamanPublikasi = ref(1)

const antreanPenarikan = ref<Penarikan[]>([])
const metaPenarikan = ref<MetaHalaman | null>(null)
const halamanPenarikan = ref(1)

const memuat = ref(true)
const galat = ref('')

const modalKeputusan = ref(false)
const dipilih = ref<Penarikan | null>(null)
const keputusan = ref<KeputusanVerifikasi>('disetujui')
const catatan = ref('')
const galatCatatan = ref('')
const memproses = ref(false)

const daftarTab = computed(() => [
  { kunci: 'publikasi', label: 'Minta tayang', jumlah: metaPublikasi.value?.total },
  { kunci: 'penarikan', label: 'Minta diturunkan', jumlah: metaPenarikan.value?.total },
])

const deskripsiTab = computed(() =>
  tab.value === 'publikasi'
    ? 'Materi yang diajukan penulis untuk tayang kepada umum. Pengajuan terlama berada di atas.'
    : 'Materi yang sedang tayang dan diminta penulisnya untuk diturunkan. Permintaan terlama berada di atas.',
)

const judulKeputusan = computed(() =>
  keputusan.value === 'ditolak' ? 'Tolak permintaan penurunan?' : 'Setujui permintaan penurunan?',
)

const deskripsiKeputusan = computed(() =>
  keputusan.value === 'ditolak'
    ? 'Materi tetap tayang untuk umum. Catatan Anda hanya dapat dibaca oleh penulis.'
    : 'Materi akan diturunkan dari halaman umum. Penulis dapat mengajukan versi barunya kemudian.',
)

// Subteks baris publikasi
function keteranganPublikasi(item: Revisi): string {
  return item.materi?.diterbitkan_pada ? 'Pernah tayang, kemudian diturunkan' : 'Pertama kali diajukan'
}

let permintaanTerakhir = 0

// Muat antrean
async function muat() {
  const nomor = ++permintaanTerakhir
  memuat.value = true
  galat.value = ''

  try {
    const [publikasi, penarikan] = await Promise.all([
      antrean(halamanPublikasi.value),
      ambilPenarikan(halamanPenarikan.value),
    ])

    if (nomor !== permintaanTerakhir) return

    antreanPublikasi.value = publikasi.data
    metaPublikasi.value = publikasi.meta
    antreanPenarikan.value = penarikan.data
    metaPenarikan.value = penarikan.meta
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) galat.value = pesanGalat(galatServer)
  } finally {
    if (nomor === permintaanTerakhir) memuat.value = false
  }
}

function mintaKeputusan(item: Penarikan, pilihan: KeputusanVerifikasi) {
  dipilih.value = item
  keputusan.value = pilihan
  catatan.value = ''
  galatCatatan.value = ''
  modalKeputusan.value = true
}

async function putuskanSekarang() {
  if (!dipilih.value || memproses.value) return

  if (keputusan.value === 'ditolak' && !catatan.value.trim()) {
    galatCatatan.value = 'Mohon isi catatan untuk penulis.'
    return
  }

  memproses.value = true
  galatCatatan.value = ''

  try {
    const respons = await putuskanPenarikan(
      dipilih.value.id,
      keputusan.value,
      keputusan.value === 'ditolak' ? catatan.value.trim() : undefined,
    )

    modalKeputusan.value = false
    tampilkan(respons.message, 'sukses')
    await muat()
  } catch (galatServer) {
    const pesan = galatIsian(galatServer, 'catatan') || pesanGalat(galatServer)

    if (keputusan.value === 'ditolak') galatCatatan.value = pesan
    else tampilkan(pesan, 'bahaya')
  } finally {
    memproses.value = false
  }
}

watch(tab, (nilai) => {
  router.replace({ query: nilai === 'penarikan' ? { tab: 'penarikan' } : {} })
})

watch([halamanPublikasi, halamanPenarikan], muat)
onMounted(muat)
</script>
