<template>
  <!-- Kelola jabatan (KF-07). Jabatan dipakai saat menyusun pengurus. -->
  <div>
    <div class="flex flex-wrap items-start justify-between gap-4 border-t border-garis pt-6">
      <p class="max-w-xl text-[13px] text-teks-lembut">
        Jabatan dipakai saat menyusun pengurus. Urutan kecil tampil lebih dulu.
      </p>
      <AppButton size="sm" @click="bukaTambah">Tambah jabatan</AppButton>
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk baris tabel. -->
    <div v-if="memuat" class="animate-pulse space-y-5 pt-6">
      <div v-for="n in 3" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="jabatan.length === 0"
      title="Belum ada jabatan."
      description="Tambahkan jabatan seperti Ketua, Sekretaris, atau Bendahara."
    >
      <template #action>
        <AppButton size="sm" @click="bukaTambah">Tambah jabatan</AppButton>
      </template>
    </AppEmptyState>

    <div v-else class="mt-6 overflow-x-auto">
      <table class="tabel">
        <thead>
          <tr>
            <th class="w-24">Urutan</th>
            <th>Nama jabatan</th>
            <th class="kanan">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in jabatan" :key="item.id">
            <td>{{ item.urutan }}</td>
            <td><span class="judul">{{ item.nama_jabatan }}</span></td>
            <td>
              <div class="flex flex-wrap justify-end gap-2">
                <AppButton variant="sekunder" size="sm" @click="bukaEdit(item)">Edit</AppButton>
                <AppButton variant="bahaya" size="sm" @click="mintaHapus(item)">Hapus</AppButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal tambah/ubah jabatan -->
    <AppModal
      v-model:open="modalForm"
      :title="sedangEdit ? 'Ubah jabatan' : 'Tambah jabatan'"
    >
      <AppAlert v-if="galatFormUmum" variant="bahaya" title="Gagal menyimpan" class="mb-4">
        {{ galatFormUmum }}
      </AppAlert>

      <form id="form-jabatan" novalidate class="space-y-4" @submit.prevent="simpan">
        <AppInput
          v-model="form.nama_jabatan"
          label="Nama jabatan"
          placeholder="Ketua"
          :error="galatIsian.nama_jabatan"
          required
        />

        <AppInput
          v-model="form.urutan"
          label="Urutan"
          type="number"
          placeholder="1"
          hint="Angka kecil tampil lebih dulu."
          :error="galatIsian.urutan"
          required
        />
      </form>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatSimpan" @click="modalForm = false">
          Batal
        </AppButton>
        <AppButton type="submit" form="form-jabatan" :loading="memuatSimpan">
          {{ sedangEdit ? 'Simpan perubahan' : 'Tambah jabatan' }}
        </AppButton>
      </template>
    </AppModal>

    <!-- Konfirmasi hapus -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus jabatan?"
      deskripsi="Jabatan hanya dapat dihapus bila belum dipakai pada susunan pengurus."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ akanDihapus?.nama_jabatan }}</p>

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
import type { Jabatan } from '~/types/api'

const { daftarJabatan, simpanJabatan, ubahJabatan, hapusJabatan } = useStruktur()
const { tampilkan } = usePesanToast()

const jabatan = ref<Jabatan[]>([])
const memuat = ref(true)
const galat = ref('')

const modalForm = ref(false)
const sedangEdit = ref<Jabatan | null>(null)
const galatForm = reactive<Record<string, string[]>>({})
const galatFormUmum = ref('')
const memuatSimpan = ref(false)

const form = reactive({ nama_jabatan: '', urutan: '' })

const galatIsian = computed(() => ({
  nama_jabatan: galatForm.nama_jabatan?.[0] ?? '',
  urutan: galatForm.urutan?.[0] ?? '',
}))

const modalHapus = ref(false)
const akanDihapus = ref<Jabatan | null>(null)
const galatHapus = ref('')
const memuatHapus = ref(false)

function kosongkanGalat() {
  for (const kunci of Object.keys(galatForm)) delete galatForm[kunci]
  galatFormUmum.value = ''
}

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarJabatan()
    jabatan.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

function bukaTambah() {
  sedangEdit.value = null
  form.nama_jabatan = ''
  form.urutan = ''
  kosongkanGalat()
  modalForm.value = true
}

function bukaEdit(item: Jabatan) {
  sedangEdit.value = item
  form.nama_jabatan = item.nama_jabatan
  form.urutan = String(item.urutan)
  kosongkanGalat()
  modalForm.value = true
}

async function simpan() {
  kosongkanGalat()
  memuatSimpan.value = true

  const payload = {
    nama_jabatan: form.nama_jabatan.trim(),
    urutan: Number(form.urutan),
  }

  try {
    const respons = sedangEdit.value
      ? await ubahJabatan(sedangEdit.value.id, payload)
      : await simpanJabatan(payload)

    modalForm.value = false
    tampilkan(respons.message, 'sukses')

    await muat()
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galatForm[kunci] = daftarPesan
    }

    if (Object.keys(isian).length === 0) {
      galatFormUmum.value = pesanGalat(galatServer)
    }
  } finally {
    memuatSimpan.value = false
  }
}

function mintaHapus(item: Jabatan) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  galatHapus.value = ''
  memuatHapus.value = true

  try {
    const respons = await hapusJabatan(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')

    await muat()
  } catch (galatServer) {
    galatHapus.value = galatIsian(galatServer, 'jabatan') || pesanGalat(galatServer)
  } finally {
    memuatHapus.value = false
  }
}

onMounted(muat)
</script>