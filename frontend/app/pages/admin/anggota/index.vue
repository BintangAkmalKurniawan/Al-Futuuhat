<template>
  <!-- Data anggota -->
  <div>
    <header class="pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Data anggota</h1>
      <p class="mt-3 text-sm text-teks-lembut">
        Cari anggota, ubah datanya, atau atur status akun.
      </p>
    </header>

    <!-- Bilah alat -->
    <div class="mt-6 mb-2 flex flex-wrap items-center gap-3">
      <FilterSegmen v-model="statusTerpilih" :opsi="opsiStatus" label="Filter status anggota" />

      <span class="flex-1" />

      <div class="w-full sm:w-[300px]">
        <SearchPill v-model="cari" placeholder="Cari nama atau email" />
      </div>
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat -->
    <div v-if="memuat" class="animate-pulse space-y-5 pt-8">
      <div v-for="n in 4" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="anggota.length === 0"
      class="pt-4"
      title="Anggota tidak ditemukan."
      description="Ubah kata kunci atau filter status untuk melihat data lain."
    />

    <template v-else>
      <div class="mt-8 overflow-x-auto">
        <table class="tabel">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Peran</th>
              <th>Status</th>
              <th>Bergabung</th>
              <th class="kanan">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in anggota" :key="item.id">
              <td>
                <span class="orang">
                  <AvatarInisial :nama="item.nama" :id="item.id" ukuran="kecil" />
                  <span class="judul text-[15px]">{{ item.nama }}</span>
                </span>
              </td>
              <td>{{ item.email }}</td>
              <td>
                <AppBadge :variant="item.role === 'admin' ? 'aksen' : 'netral'">
                  {{ item.role === 'admin' ? 'Admin' : 'Anggota' }}
                </AppBadge>
              </td>
              <td>
                <AppBadge :variant="varianStatusAkun(item.status)">
                  {{ labelStatusAkun(item.status) }}
                </AppBadge>
              </td>
              <td>{{ formatTanggal(tanggalBergabung(item)) }}</td>
              <td class="kanan">
                <AppButton
                  variant="sekunder"
                  size="sm"
                  @click="navigateTo(`/admin/anggota/${item.id}`)"
                >
                  Kelola
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
useHead({ title: 'Data Anggota' })

const { daftarAnggota } = useAnggota()

const opsiStatus = [
  { nilai: '', label: 'Semua' },
  { nilai: 'aktif', label: 'Aktif' },
  { nilai: 'nonaktif', label: 'Nonaktif' },
  { nilai: 'ditolak', label: 'Ditolak' },
]

const cari = ref('')
const statusTerpilih = ref('')
const halaman = ref(1)
const anggota = ref<User[]>([])
const meta = ref<MetaHalaman | null>(null)
const memuat = ref(true)
const galat = ref('')

// Tanggal bergabung
function tanggalBergabung(item: User) {
  return item.anggota?.tanggal_bergabung ?? item.terdaftar_pada ?? null
}

let permintaanTerakhir = 0
let penundaCari: ReturnType<typeof setTimeout> | undefined

async function muat() {
  const nomor = ++permintaanTerakhir
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarAnggota({
      q: cari.value.trim() || undefined,
      status: statusTerpilih.value || undefined,
      page: halaman.value,
    })

    if (nomor !== permintaanTerakhir) return

    anggota.value = respons.data
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

// Mulai dari halaman pertama
function mulaiDariAwal() {
  if (halaman.value !== 1) {
    halaman.value = 1
  } else {
    muat()
  }
}

watch(cari, () => {
  clearTimeout(penundaCari)
  penundaCari = setTimeout(mulaiDariAwal, 350)
})

watch(statusTerpilih, mulaiDariAwal)
watch(halaman, muat)
onMounted(muat)
onBeforeUnmount(() => clearTimeout(penundaCari))
</script>