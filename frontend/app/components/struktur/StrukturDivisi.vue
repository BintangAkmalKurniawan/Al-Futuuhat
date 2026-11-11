<template>
  <!-- Kelola divisi (KF-07). Deskripsi divisi tampil di halaman beranda. -->
  <div>
    <div class="flex flex-wrap items-start justify-between gap-4 border-t border-garis pt-6">
      <p class="max-w-xl text-[13px] text-teks-lembut">
        Divisi menjadi wadah kegiatan anggota. Deskripsi divisi tampil di halaman beranda.
      </p>
      <AppButton size="sm" @click="bukaTambah">Tambah divisi</AppButton>
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk kartu. -->
    <div v-if="memuat" class="mt-6 grid animate-pulse gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-32 rounded-lg bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="divisi.length === 0"
      title="Belum ada divisi."
      description="Tambahkan divisi pertama agar dapat dipakai pada susunan pengurus."
    >
      <template #action>
        <AppButton size="sm" @click="bukaTambah">Tambah divisi</AppButton>
      </template>
    </AppEmptyState>

    <div v-else class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="item in divisi"
        :key="item.id"
        class="flex flex-col rounded-lg bg-permukaan p-5 ring-1 ring-garis"
      >
        <h3 class="font-serif text-kartu font-bold text-teks-kuat">{{ item.nama_divisi }}</h3>
        <p class="mt-2 flex-1 text-sm leading-relaxed text-teks-lembut">
          {{ item.deskripsi || 'Belum ada deskripsi.' }}
        </p>

        <div class="mt-5 flex justify-end gap-2">
          <AppButton variant="sekunder" size="sm" @click="bukaEdit(item)">Edit</AppButton>
          <AppButton variant="bahaya" size="sm" @click="mintaHapus(item)">Hapus</AppButton>
        </div>
      </article>
    </div>

    <!-- Modal tambah/ubah divisi -->
    <AppModal
      v-model:open="modalForm"
      :title="sedangEdit ? 'Ubah divisi' : 'Tambah divisi'"
    >
      <AppAlert v-if="galatFormUmum" variant="bahaya" title="Gagal menyimpan" class="mb-4">
        {{ galatFormUmum }}
      </AppAlert>

      <form id="form-divisi" novalidate class="space-y-4" @submit.prevent="simpan">
        <AppInput
          v-model="form.nama_divisi"
          label="Nama divisi"
          placeholder="Divisi Pendidikan"
          :error="galatIsian.nama_divisi"
          required
        />

        <AppTextarea
          v-model="form.deskripsi"
          label="Deskripsi"
          placeholder="Tugas dan bidang kerja divisi..."
          hint="Opsional. Tampil di halaman beranda."
          :error="galatIsian.deskripsi"
        />
      </form>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatSimpan" @click="modalForm = false">
          Batal
        </AppButton>
        <AppButton type="submit" form="form-divisi" :loading="memuatSimpan">
          {{ sedangEdit ? 'Simpan perubahan' : 'Tambah divisi' }}
        </AppButton>
      </template>
    </AppModal>

    <!-- Konfirmasi hapus -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus divisi?"
      deskripsi="Divisi hanya dapat dihapus bila belum dipakai pada susunan pengurus."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ akanDihapus?.nama_divisi }}</p>

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
import type { Divisi } from '~/types/api'

const { daftarDivisi, simpanDivisi, ubahDivisi, hapusDivisi } = useStruktur()
const { tampilkan } = usePesanToast()

const divisi = ref<Divisi[]>([])
const memuat = ref(true)
const galat = ref('')

const modalForm = ref(false)
const sedangEdit = ref<Divisi | null>(null)
const galatForm = reactive<Record<string, string[]>>({})
const galatFormUmum = ref('')
const memuatSimpan = ref(false)

const form = reactive({ nama_divisi: '', deskripsi: '' })

const galatIsian = computed(() => ({
  nama_divisi: galatForm.nama_divisi?.[0] ?? '',
  deskripsi: galatForm.deskripsi?.[0] ?? '',
}))

const modalHapus = ref(false)
const akanDihapus = ref<Divisi | null>(null)
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
    const respons = await daftarDivisi()
    divisi.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

function bukaTambah() {
  sedangEdit.value = null
  form.nama_divisi = ''
  form.deskripsi = ''
  kosongkanGalat()
  modalForm.value = true
}

function bukaEdit(item: Divisi) {
  sedangEdit.value = item
  form.nama_divisi = item.nama_divisi
  form.deskripsi = item.deskripsi ?? ''
  kosongkanGalat()
  modalForm.value = true
}

async function simpan() {
  kosongkanGalat()
  memuatSimpan.value = true

  const payload = {
    nama_divisi: form.nama_divisi.trim(),
    deskripsi: form.deskripsi.trim() || null,
  }

  try {
    const respons = sedangEdit.value
      ? await ubahDivisi(sedangEdit.value.id, payload)
      : await simpanDivisi(payload)

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

function mintaHapus(item: Divisi) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  galatHapus.value = ''
  memuatHapus.value = true

  try {
    const respons = await hapusDivisi(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')

    await muat()
  } catch (galatServer) {
    galatHapus.value = galatIsian(galatServer, 'divisi') || pesanGalat(galatServer)
  } finally {
    memuatHapus.value = false
  }
}

onMounted(muat)
</script>