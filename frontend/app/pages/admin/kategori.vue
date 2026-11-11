<template>
  <!-- Kelola kategori materi (KF-19, KP-19.1). -->
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4 pb-8">
      <div>
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">Kategori materi</h1>
        <p class="mt-3 max-w-xl text-sm text-teks-lembut">
          Kategori dipakai untuk menyaring daftar materi di halaman arsip dan artikel. Setiap
          materi wajib memiliki minimal satu kategori.
        </p>
      </div>

      <AppButton size="sm" @click="bukaTambah">Tambah kategori</AppButton>
    </header>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-2">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk baris tabel. -->
    <div v-if="memuat" class="animate-pulse space-y-5 pt-2">
      <div v-for="n in 4" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="daftar.length === 0"
      title="Belum ada kategori."
      description="Tambahkan kategori pertama agar materi dapat dikelompokkan."
    >
      <template #action>
        <AppButton size="sm" @click="bukaTambah">Tambah kategori</AppButton>
      </template>
    </AppEmptyState>

    <div v-else class="mt-2 overflow-x-auto">
      <table class="tabel">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Alamat tautan</th>
            <th class="kanan">Jumlah materi</th>
            <th class="kanan">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in daftar" :key="item.id">
            <td>
              <span class="judul">{{ item.nama }}</span>
            </td>
            <td class="text-[13px] text-teks-redup">{{ item.slug }}</td>
            <td class="kanan">{{ item.jumlah_materi ?? 0 }} materi</td>
            <td class="kanan">
              <div class="flex justify-end gap-2">
                <AppButton variant="sekunder" size="sm" @click="bukaUbah(item)">Edit</AppButton>
                <AppButton variant="bahaya" size="sm" @click="mintaHapus(item)">Hapus</AppButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal tambah/ubah kategori -->
    <AppModal
      v-model:open="modalForm"
      :title="sedangEdit ? 'Ubah kategori' : 'Tambah kategori'"
      deskripsi="Alamat tautan kategori dibuat otomatis dari namanya."
    >
      <AppAlert v-if="galatFormUmum" variant="bahaya" title="Gagal menyimpan" class="mb-4">
        {{ galatFormUmum }}
      </AppAlert>

      <form id="form-kategori" novalidate class="space-y-4" @submit.prevent="simpanForm">
        <AppInput
          v-model="nama"
          label="Nama kategori"
          placeholder="Fiqih"
          :error="galatForm"
          required
        />
      </form>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatSimpan" @click="modalForm = false">
          Batal
        </AppButton>
        <AppButton type="submit" form="form-kategori" :loading="memuatSimpan">
          {{ sedangEdit ? 'Simpan perubahan' : 'Tambah kategori' }}
        </AppButton>
      </template>
    </AppModal>

    <!-- Konfirmasi hapus -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus kategori?"
      deskripsi="Kategori hanya dapat dihapus bila belum dipakai oleh materi mana pun."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ akanDihapus?.nama }}</p>

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
import type { Kategori } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Kategori Materi' })

const { daftarAdmin, simpan, ubah, hapus } = useKategori()
const { tampilkan } = usePesanToast()

const daftar = ref<Kategori[]>([])
const memuat = ref(true)
const galat = ref('')

// Modal tambah/ubah: `sedangEdit` null berarti menambah kategori baru.
const modalForm = ref(false)
const sedangEdit = ref<Kategori | null>(null)
const nama = ref('')
const galatForm = ref('')
const galatFormUmum = ref('')
const memuatSimpan = ref(false)

const modalHapus = ref(false)
const akanDihapus = ref<Kategori | null>(null)
const galatHapus = ref('')
const memuatHapus = ref(false)

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    daftar.value = (await daftarAdmin()).data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer, 'Gagal memuat kategori.')
  } finally {
    memuat.value = false
  }
}

function bukaTambah() {
  sedangEdit.value = null
  nama.value = ''
  galatForm.value = ''
  galatFormUmum.value = ''
  modalForm.value = true
}

function bukaUbah(item: Kategori) {
  sedangEdit.value = item
  nama.value = item.nama
  galatForm.value = ''
  galatFormUmum.value = ''
  modalForm.value = true
}

async function simpanForm() {
  galatForm.value = ''
  galatFormUmum.value = ''

  if (nama.value.trim() === '') {
    galatForm.value = 'Nama kategori wajib diisi.'
    return
  }

  memuatSimpan.value = true

  try {
    const respons = sedangEdit.value
      ? await ubah(sedangEdit.value.id, { nama: nama.value.trim() })
      : await simpan({ nama: nama.value.trim() })

    modalForm.value = false
    tampilkan(respons.message, 'sukses')
    await muat()
  } catch (galatServer) {
    // Galat 422 (mis. "Nama kategori sudah ada.") tampil di bawah isian nama.
    galatForm.value = galatIsian(galatServer, 'nama')

    if (!galatForm.value) {
      galatFormUmum.value = pesanGalat(galatServer)
      tampilkan(galatFormUmum.value, 'bahaya')
    }
  } finally {
    memuatSimpan.value = false
  }
}

function mintaHapus(item: Kategori) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  memuatHapus.value = true
  galatHapus.value = ''

  try {
    const respons = await hapus(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')
    await muat()
  } catch (galatServer) {
    // Backend menolak kategori yang masih dipakai materi (KP-19.1).
    galatHapus.value = galatIsian(galatServer, 'kategori') || pesanGalat(galatServer)
  } finally {
    memuatHapus.value = false
  }
}

onMounted(muat)
</script>
