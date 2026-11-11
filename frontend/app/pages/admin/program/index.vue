<template>
  <!-- Daftar program untuk admin (KF-21, KP-21.1, KP-21.4). -->
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4 pb-8">
      <div>
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">Program</h1>
        <p class="mt-3 max-w-xl text-sm text-teks-lembut">
          Kegiatan rutin Al-Futuuhat beserta PIC dan dokumentasinya. Program tampil di halaman
          Program untuk pengunjung dan anggota.
        </p>
      </div>

      <NuxtLink to="/admin/program/baru" class="btn btn-primer btn-sm">Tambah program</NuxtLink>
    </header>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data">{{ galat }}</AppAlert>

    <!-- Kerangka memuat berbentuk baris tabel. -->
    <div v-else-if="memuat" class="animate-pulse space-y-5 pt-2">
      <div v-for="n in 4" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="daftar.length === 0"
      title="Belum ada program."
      description="Tambahkan program pertama agar tampil di halaman Program."
    >
      <template #action>
        <NuxtLink to="/admin/program/baru" class="btn btn-primer btn-sm">Tambah program</NuxtLink>
      </template>
    </AppEmptyState>

    <div v-else class="overflow-x-auto">
      <table class="tabel">
        <thead>
          <tr>
            <th>Program</th>
            <th>PIC</th>
            <th>Status</th>
            <th class="kanan">Dokumentasi</th>
            <th class="kanan">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in daftar" :key="item.id">
            <td>
              <NuxtLink :to="`/admin/program/${item.id}`" class="judul hover:text-aksen">
                {{ item.nama }}
              </NuxtLink>
              <span v-if="item.jadwal" class="sub">{{ item.jadwal }}</span>
            </td>
            <td class="text-sm">{{ namaPic(item) || '—' }}</td>
            <td><LencanaStatusProgram :status="item.status" /></td>
            <td class="kanan text-sm">
              {{ item.jumlah_foto ?? 0 }} foto · {{ item.jumlah_video ?? 0 }} video
            </td>
            <td class="kanan">
              <div class="flex justify-end gap-2">
                <NuxtLink :to="`/admin/program/${item.id}`" class="btn btn-sekunder btn-sm">
                  Kelola
                </NuxtLink>
                <AppButton variant="bahaya" size="sm" @click="mintaHapus(item)">Hapus</AppButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AppModal
      v-model:open="modalHapus"
      title="Hapus program?"
      deskripsi="Seluruh foto dan video program ini ikut terhapus dan tidak dapat dikembalikan."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">{{ galatHapus }}</AppAlert>
      <p v-else class="text-sm text-teks-lembut">
        {{ akanDihapus?.nama }} —
        {{ akanDihapus?.jumlah_foto ?? 0 }} foto dan {{ akanDihapus?.jumlah_video ?? 0 }} video
      </p>

      <template #footer>
        <AppButton variant="hantu" :disabled="menghapus" @click="modalHapus = false">Batal</AppButton>
        <AppButton variant="bahaya" :loading="menghapus" @click="hapusSekarang">Ya, hapus</AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import type { ProgramAdmin } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Program' })

const { daftarAdmin, hapus } = useProgram()
const { tampilkan } = usePesanToast()

const daftar = ref<ProgramAdmin[]>([])
const memuat = ref(true)
const galat = ref('')

const modalHapus = ref(false)
const akanDihapus = ref<ProgramAdmin | null>(null)
const galatHapus = ref('')
const menghapus = ref(false)

function namaPic(item: ProgramAdmin) {
  return item.pic
    .map((orang) => orang.nama)
    .filter(Boolean)
    .join(', ')
}

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarAdmin()
    daftar.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

function mintaHapus(item: ProgramAdmin) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  menghapus.value = true
  galatHapus.value = ''

  try {
    const respons = await hapus(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')
    await muat()
  } catch (galatServer) {
    galatHapus.value = pesanGalat(galatServer)
  } finally {
    menghapus.value = false
  }
}

onMounted(muat)
</script>
