<template>
  <!-- Kelola susunan pengurus periode saat ini (KF-07).
       Draf 2.5: tanpa pemilih periode — periode dibuat otomatis per tahun (KP-07.1)
       dan setiap perubahan tercatat di riwayat (KP-07.7). -->
  <div>
    <div class="flex flex-wrap items-end gap-3 border-t border-garis pt-6">
      <div class="max-w-xl">
        <p class="text-sm text-teks-kuat">
          Periode saat ini:
          <span class="font-medium">{{ periodeAktif?.nama_periode ?? 'belum ada' }}</span>
        </p>
        <p class="mt-1 text-[13px] text-teks-lembut">
          Setiap tambah, ubah, dan hapus pengurus tercatat otomatis di tab Riwayat. Satu anggota
          hanya dapat menjabat sekali dalam satu periode.
        </p>
      </div>

      <div class="flex-1" />

      <AppButton variant="sekunder" :disabled="!periodeAktif" @click="pratinjauBagan">
        <Network class="h-4 w-4" aria-hidden="true" />
        Pratinjau bagan
      </AppButton>

      <AppButton :disabled="memuat" @click="bukaTambah">Tambah pengurus</AppButton>
    </div>

    <!-- Tahun sudah berganti: perubahan berikutnya memulai periode baru (KP-07.1). -->
    <AppAlert
      v-if="periodeAktif?.akan_berganti"
      variant="aksen"
      title="Tahun sudah berganti"
      class="mt-6"
    >
      Perubahan berikutnya otomatis memulai periode {{ tahunIni }} dengan menyalin susunan di
      bawah ini. Periode {{ periodeAktif.nama_periode }} lalu menjadi arsip yang hanya dapat dibaca.
    </AppAlert>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk baris tabel. -->
    <div v-else-if="memuat" class="animate-pulse space-y-5 pt-8">
      <div v-for="n in 3" :key="n" class="h-4 w-full rounded-full bg-permukaan" />
    </div>

    <AppEmptyState
      v-else-if="pengurus.length === 0"
      title="Belum ada pengurus."
      :description="
        periodeAktif
          ? 'Tambahkan pengurus agar struktur organisasi tampil di beranda.'
          : `Tambahkan pengurus pertama; periode ${tahunIni} dibuat otomatis.`
      "
    >
      <template #action>
        <AppButton size="sm" @click="bukaTambah">Tambah pengurus</AppButton>
      </template>
    </AppEmptyState>

    <div v-else class="mt-8 overflow-x-auto">
      <table class="tabel">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Divisi</th>
            <th>Jabatan</th>
            <th>Urutan</th>
            <th class="kanan">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in pengurus" :key="item.id">
            <td>
              <span class="orang">
                <AvatarInisial
                  :nama="item.nama || `Anggota #${item.anggota_id}`"
                  :id="item.anggota_id"
                  ukuran="kecil"
                />
                <span class="judul text-[15px]">
                  {{ item.nama || `Anggota #${item.anggota_id}` }}
                </span>
              </span>
            </td>
            <td>
              <!-- Tanpa divisi berarti pengurus inti (PRD §10). -->
              <span v-if="item.divisi">{{ item.divisi.nama_divisi }}</span>
              <span v-else class="text-teks-redup">— Pengurus inti</span>
            </td>
            <td>{{ item.jabatan?.nama_jabatan || '—' }}</td>
            <td>{{ item.jabatan?.urutan ?? '—' }}</td>
            <td>
              <div class="flex items-center justify-end gap-2">
                <AppButton variant="hantu" size="sm" @click="bukaEdit(item)">Ubah</AppButton>

                <button
                  type="button"
                  class="grid h-8 w-8 place-items-center rounded-full text-bahaya transition-colors duration-200 hover:bg-bahaya/10"
                  :aria-label="`Hapus ${item.nama || `anggota #${item.anggota_id}`} dari susunan pengurus`"
                  @click="mintaHapus(item)"
                >
                  <Trash2 class="h-4 w-4" aria-hidden="true" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal tambah/ubah pengurus -->
    <AppModal
      v-model:open="modalForm"
      :title="sedangEdit ? 'Ubah pengurus' : 'Tambah pengurus'"
      deskripsi="Pilih anggota aktif, jabatan, dan divisi. Kosongkan divisi untuk pengurus inti."
    >
      <AppAlert v-if="galatFormUmum" variant="bahaya" title="Gagal menyimpan" class="mb-4">
        {{ galatFormUmum }}
      </AppAlert>

      <form id="form-pengurus" novalidate class="space-y-4" @submit.prevent="simpan">
        <!-- Pemilih anggota: cari lalu pilih satu anggota aktif. -->
        <div>
          <p class="mb-1.5 text-xs font-medium tracking-wider text-teks-redup uppercase">
            Anggota<span class="text-bahaya" aria-hidden="true"> *</span>
          </p>

          <div
            v-if="anggotaTerpilih"
            class="flex items-center justify-between gap-3 rounded-md bg-permukaan px-3 py-2 ring-1 ring-garis"
          >
            <div class="min-w-0">
              <p class="truncate text-sm text-teks-kuat">{{ anggotaTerpilih.nama }}</p>
              <p v-if="anggotaTerpilih.email" class="truncate text-[13px] text-teks-redup">
                {{ anggotaTerpilih.email }}
              </p>
            </div>
            <AppButton variant="hantu" size="sm" @click="gantiAnggota">Ganti</AppButton>
          </div>

          <template v-else>
            <AppInput
              v-model="cariAnggota"
              type="search"
              placeholder="Cari nama atau email anggota aktif..."
            />

            <div class="mt-2 max-h-56 overflow-y-auto rounded-md ring-1 ring-garis">
              <p v-if="mencari" class="px-3 py-3 text-[13px] text-teks-redup">
                Mencari anggota...
              </p>
              <p v-else-if="hasilCari.length === 0" class="px-3 py-3 text-[13px] text-teks-redup">
                Tidak ada anggota aktif yang cocok.
              </p>

              <ul v-else class="divide-y divide-garis">
                <li v-for="item in hasilCari" :key="item.id">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left transition-colors duration-200 hover:bg-permukaan disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="idTerpakai.has(item.anggota?.id ?? -1)"
                    @click="pilihAnggota(item)"
                  >
                    <span class="min-w-0">
                      <span class="block truncate text-sm text-teks">{{ item.nama }}</span>
                      <span class="block truncate text-[13px] text-teks-redup">{{ item.email }}</span>
                    </span>
                    <span
                      v-if="idTerpakai.has(item.anggota?.id ?? -1)"
                      class="shrink-0 text-[11px] text-teks-redup"
                    >
                      Sudah pengurus
                    </span>
                  </button>
                </li>
              </ul>
            </div>

            <p v-if="cariAnggota === ''" class="mt-1.5 text-[13px] text-teks-redup">
              Menampilkan anggota aktif pertama. Ketik nama atau email untuk mencari.
            </p>
          </template>

          <p v-if="galatIsian.anggota_id" class="pesan-galat" role="alert">
            {{ galatIsian.anggota_id }}
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <AppSelect
            v-model="form.jabatan_id"
            label="Jabatan"
            placeholder="Pilih jabatan"
            :opsi="opsiJabatan"
            :error="galatIsian.jabatan_id"
            required
          />

          <AppSelect
            v-model="form.divisi_id"
            label="Divisi"
            :opsi="opsiDivisi"
            :error="galatIsian.divisi_id"
          />
        </div>
      </form>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatSimpan" @click="modalForm = false">
          Batal
        </AppButton>
        <AppButton type="submit" form="form-pengurus" :loading="memuatSimpan">
          {{ sedangEdit ? 'Simpan perubahan' : 'Tambah pengurus' }}
        </AppButton>
      </template>
    </AppModal>

    <!-- Konfirmasi hapus -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus pengurus?"
      deskripsi="Anggota tetap terdaftar; hanya jabatannya yang dihapus. Perubahan ini tercatat di riwayat."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Gagal menghapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">
        {{ akanDihapus?.nama || 'Anggota terpilih' }} —
        {{ akanDihapus?.jabatan?.nama_jabatan }}
      </p>

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
import { Network, Trash2 } from '@lucide/vue'
import type { Divisi, Jabatan, Kepengurusan, Periode, User } from '~/types/api'

const {
  daftarDivisi,
  daftarJabatan,
  daftarPengurus,
  simpanPengurus,
  ubahPengurus,
  hapusPengurus,
} = useStruktur()
const { daftarAnggota } = useAnggota()
const { tampilkan } = usePesanToast()

const tahunIni = new Date().getFullYear()

const periodeAktif = ref<Periode | null>(null)
const divisi = ref<Divisi[]>([])
const jabatan = ref<Jabatan[]>([])
const pengurus = ref<Kepengurusan[]>([])

const memuat = ref(true)
const galat = ref('')

const modalForm = ref(false)
const sedangEdit = ref<Kepengurusan | null>(null)
const memuatSimpan = ref(false)
const galatForm = reactive<Record<string, string[]>>({})
const galatFormUmum = ref('')

const form = reactive<{ jabatan_id: number | ''; divisi_id: number | '' }>({
  jabatan_id: '',
  divisi_id: '',
})

const anggotaTerpilih = ref<{ anggota_id: number; nama: string; email?: string } | null>(null)
const cariAnggota = ref('')
const hasilCari = ref<User[]>([])
const mencari = ref(false)

const modalHapus = ref(false)
const akanDihapus = ref<Kepengurusan | null>(null)
const galatHapus = ref('')
const memuatHapus = ref(false)

const opsiDivisi = computed(() => [
  { nilai: '', label: 'Pengurus inti (tanpa divisi)' },
  ...divisi.value.map((item) => ({ nilai: item.id, label: item.nama_divisi })),
])

const opsiJabatan = computed(() =>
  jabatan.value.map((item) => ({ nilai: item.id, label: item.nama_jabatan })),
)

const galatIsian = computed(() => ({
  anggota_id: galatForm.anggota_id?.[0] ?? '',
  jabatan_id: galatForm.jabatan_id?.[0] ?? '',
  divisi_id: galatForm.divisi_id?.[0] ?? '',
}))

// Anggota yang sudah menjabat pada periode ini tidak dapat dipilih lagi.
const idTerpakai = computed(() => {
  const terpakai = new Set<number>()

  for (const item of pengurus.value) {
    if (item.id !== sedangEdit.value?.id) terpakai.add(item.anggota_id)
  }

  return terpakai
})

let permintaanCari = 0
let penundaCari: ReturnType<typeof setTimeout> | undefined

async function muatAwal() {
  memuat.value = true
  galat.value = ''

  try {
    const [responsDivisi, responsJabatan] = await Promise.all([daftarDivisi(), daftarJabatan()])

    divisi.value = responsDivisi.data
    jabatan.value = responsJabatan.data

    await muatPengurus()
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

/** Pengurus + periode aktif. Dipanggil ulang setelah setiap perubahan, karena
 *  perubahan pertama di tahun baru dapat memulai periode baru. */
async function muatPengurus() {
  const respons = await daftarPengurus()

  pengurus.value = respons.data
  periodeAktif.value = respons.periode
}

async function cariDaftarAnggota() {
  const nomor = ++permintaanCari
  mencari.value = true

  try {
    const respons = await daftarAnggota({
      q: cariAnggota.value.trim() || undefined,
      status: 'aktif',
    })

    if (nomor === permintaanCari) {
      // Hanya anggota yang punya baris anggota (bukan admin murni) yang bisa dipilih.
      hasilCari.value = respons.data.filter((item) => item.anggota?.id)
    }
  } catch {
    if (nomor === permintaanCari) hasilCari.value = []
  } finally {
    if (nomor === permintaanCari) mencari.value = false
  }
}

watch(cariAnggota, () => {
  // Jangan mencari saat anggota sudah dipilih (kotak pencarian tersembunyi).
  if (anggotaTerpilih.value) return

  clearTimeout(penundaCari)
  penundaCari = setTimeout(cariDaftarAnggota, 350)
})

function kosongkanGalat() {
  for (const kunci of Object.keys(galatForm)) delete galatForm[kunci]
  galatFormUmum.value = ''
}

/** "Pratinjau bagan": membuka /struktur periode saat ini di tab baru. */
function pratinjauBagan() {
  if (!periodeAktif.value) return

  navigateTo(
    { path: '/struktur', query: { periode: String(periodeAktif.value.id) } },
    { open: { target: '_blank' } },
  )
}

function bukaTambah() {
  sedangEdit.value = null
  form.jabatan_id = ''
  form.divisi_id = ''
  anggotaTerpilih.value = null
  cariAnggota.value = ''
  hasilCari.value = []
  kosongkanGalat()
  modalForm.value = true
  clearTimeout(penundaCari)
  cariDaftarAnggota()
}

function bukaEdit(item: Kepengurusan) {
  sedangEdit.value = item
  form.jabatan_id = item.jabatan_id
  form.divisi_id = item.divisi_id ?? ''
  anggotaTerpilih.value = {
    anggota_id: item.anggota_id,
    nama: item.nama || `Anggota #${item.anggota_id}`,
  }
  cariAnggota.value = ''
  hasilCari.value = []
  kosongkanGalat()
  modalForm.value = true
}

function pilihAnggota(item: User) {
  if (!item.anggota?.id) return

  anggotaTerpilih.value = {
    anggota_id: item.anggota.id,
    nama: item.nama,
    email: item.email,
  }
  cariAnggota.value = ''
  hasilCari.value = []
}

function gantiAnggota() {
  anggotaTerpilih.value = null
  cariAnggota.value = ''
  clearTimeout(penundaCari)
  cariDaftarAnggota()
}

async function simpan() {
  kosongkanGalat()

  if (!anggotaTerpilih.value) {
    galatForm.anggota_id = ['Pilih anggota yang akan dijadikan pengurus.']
    return
  }

  if (!form.jabatan_id) {
    galatForm.jabatan_id = ['Jabatan wajib dipilih.']
    return
  }

  memuatSimpan.value = true

  // Draf 2.5: tanpa periode_id — backend mencatat pada periode saat ini.
  const payload = {
    jabatan_id: Number(form.jabatan_id),
    divisi_id: form.divisi_id === '' ? null : Number(form.divisi_id),
    anggota_id: anggotaTerpilih.value.anggota_id,
  }

  try {
    const respons = sedangEdit.value
      ? await ubahPengurus(sedangEdit.value.id, payload)
      : await simpanPengurus(payload)

    modalForm.value = false
    tampilkan(respons.message, 'sukses')

    await muatPengurus()
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galatForm[kunci] = daftarPesan
    }

    // Galat `periode` (periode lama hanya-baca) tidak punya isian sendiri.
    if (isian.periode?.[0]) galatFormUmum.value = isian.periode[0]
    else if (Object.keys(isian).length === 0) galatFormUmum.value = pesanGalat(galatServer)
  } finally {
    memuatSimpan.value = false
  }
}

function mintaHapus(item: Kepengurusan) {
  akanDihapus.value = item
  galatHapus.value = ''
  modalHapus.value = true
}

async function hapusSekarang() {
  if (!akanDihapus.value) return

  galatHapus.value = ''
  memuatHapus.value = true

  try {
    const respons = await hapusPengurus(akanDihapus.value.id)

    modalHapus.value = false
    tampilkan(respons.message, 'sukses')

    await muatPengurus()
  } catch (galatServer) {
    galatHapus.value = galatValidasi(galatServer).periode?.[0] ?? pesanGalat(galatServer)
  } finally {
    memuatHapus.value = false
  }
}

onMounted(muatAwal)
onBeforeUnmount(() => clearTimeout(penundaCari))
</script>
