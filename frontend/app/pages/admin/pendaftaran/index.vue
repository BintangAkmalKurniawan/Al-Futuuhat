<template>
  <!-- Verifikasi pendaftaran (KF-05, DESIGN.md §6.6). -->
  <div>
    <header class="pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Pemeriksaan pendaftaran</h1>
      <p class="mt-3 text-sm text-teks-lembut">
        Periksa pendaftar baru dan putuskan apakah akunnya disetujui.
      </p>
    </header>

    <!-- Bilah alat: filter segmen status (DESIGN.md §6.6 no. 18).
         Kotak cari menyusul setelah endpoint menerima parameter q. -->
    <div class="mt-6 mb-2 flex flex-wrap items-center gap-3">
      <FilterSegmen v-model="status" :opsi="opsiStatus" label="Filter status pendaftar" />
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk baris tabel. -->
    <div v-if="memuat" class="animate-pulse space-y-5 pt-6">
      <div v-for="n in 4" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="pendaftar.length === 0"
      class="pt-4"
      :title="status === 'menunggu' ? 'Tidak ada pendaftar menunggu.' : 'Tidak ada pendaftar ditolak.'"
      description="Data akan tampil di sini setelah ada pendaftaran baru."
    />

    <template v-else>
      <div class="mt-6 overflow-x-auto">
        <table class="tabel">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Mendaftar</th>
              <th>Status</th>
              <th class="kanan">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in pendaftar" :key="item.id">
              <td>
                <span class="orang">
                  <AvatarInisial :nama="item.nama" :id="item.id" ukuran="kecil" />
                  <span class="judul text-[15px]">{{ item.nama }}</span>
                </span>
              </td>
              <td>{{ item.email }}</td>
              <td>{{ formatTanggal(item.terdaftar_pada) }}</td>
              <td>
                <AppBadge :variant="varianStatusAkun(item.status)">
                  {{ labelStatusAkun(item.status) }}
                </AppBadge>
              </td>
              <td class="kanan">
                <AppButton size="sm" @click="navigateTo(`/admin/pendaftaran/${item.id}`)">
                  Periksa
                </AppButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pt-8">
        <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { MetaHalaman, User } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Verifikasi Pendaftaran' })

const { daftarPendaftar } = usePendaftaran()

const opsiStatus = [
  { nilai: 'menunggu', label: 'Menunggu' },
  { nilai: 'ditolak', label: 'Ditolak' },
]

const status = ref<'menunggu' | 'ditolak'>('menunggu')
const halaman = ref(1)
const pendaftar = ref<User[]>([])
const meta = ref<MetaHalaman | null>(null)
const memuat = ref(true)
const galat = ref('')

// Penanda agar respons lama tidak menimpa hasil permintaan terbaru.
let permintaanTerakhir = 0

async function muat() {
  const nomor = ++permintaanTerakhir
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarPendaftar(status.value, halaman.value)

    if (nomor !== permintaanTerakhir) return

    pendaftar.value = respons.data
    meta.value = respons.meta
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) {
      galat.value = pesanGalat(galatServer)
    }
  } finally {
    if (nomor === permintaanTerakhir) {
      memuat.value = false
    }
  }
}

watch(status, () => {
  halaman.value = 1
})

watch([status, halaman], muat)
onMounted(muat)
</script>