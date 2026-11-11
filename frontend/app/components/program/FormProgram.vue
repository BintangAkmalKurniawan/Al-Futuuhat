<template>
  <!-- Formulir tambah/ubah program (KP-21.1, KP-21.2). Dikirim sebagai multipart
       karena sampul berupa berkas; sampul dikirim bersama formulir saat Simpan. -->
  <form novalidate @submit.prevent="simpan">
    <AppAlert v-if="galatUmum" variant="bahaya" title="Gagal menyimpan" class="mb-6">
      {{ galatUmum }}
    </AppAlert>

    <div class="space-y-5">
      <AppInput
        v-model="form.nama"
        label="Nama program"
        placeholder="Misalnya: Kajian Rutin Jumat Malam"
        :error="galat.nama"
        required
      />

      <AppTextarea
        v-model="form.ringkasan"
        label="Ringkasan"
        :rows="2"
        placeholder="Satu atau dua kalimat yang tampil di kartu daftar program."
        hint="Opsional, maksimal 300 karakter."
        :error="galat.ringkasan"
      />

      <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_200px]">
        <AppInput
          v-model="form.jadwal"
          label="Jadwal pelaksanaan"
          placeholder="Misalnya: Setiap Jumat malam, ba'da Isya"
          hint="Opsional, teks bebas."
          :error="galat.jadwal"
        />

        <AppSelect
          v-model="form.status"
          label="Status"
          :opsi="opsiStatus"
          :error="galat.status"
          required
        />
      </div>
    </div>

    <!-- Sampul -->
    <section class="mt-8 border-t border-garis pt-6">
      <h2 class="text-sm font-medium text-teks-kuat">Gambar sampul</h2>
      <p class="mt-1 text-[13px] text-teks-redup">
        Opsional. JPG atau PNG, maksimal 2 MB. Tampil di kartu dan di atas halaman program.
      </p>

      <div class="mt-4 flex flex-wrap items-start gap-5">
        <div
          class="flex aspect-[16/10] w-full max-w-[288px] items-center justify-center overflow-hidden rounded-lg border border-garis bg-permukaan"
        >
          <img
            v-if="sampulTampil"
            :src="sampulTampil"
            alt="Pratinjau sampul program"
            class="h-full w-full object-cover"
          />
          <span v-else class="text-[13px] text-teks-redup">Belum ada sampul</span>
        </div>

        <div>
          <input
            ref="masukanSampul"
            type="file"
            accept="image/jpeg,image/png"
            class="hidden"
            @change="pilihSampul"
          />

          <div class="flex flex-wrap gap-2">
            <AppButton variant="sekunder" size="sm" @click="masukanSampul?.click()">
              {{ sampulTampil ? 'Ganti sampul' : 'Pilih sampul' }}
            </AppButton>
            <AppButton
              v-if="sampulTampil || hapusSampul"
              variant="hantu"
              size="sm"
              @click="tandaiHapusSampul"
            >
              {{ labelTombolHapus }}
            </AppButton>
          </div>

          <p v-if="hapusSampul" class="mt-2 text-[13px] text-teks-redup">
            Sampul akan dihapus saat Anda menekan Simpan.
          </p>
          <p v-if="galat.sampul" class="pesan-galat" role="alert">{{ galat.sampul }}</p>
        </div>
      </div>
    </section>

    <!-- PIC -->
    <section class="mt-8 border-t border-garis pt-6">
      <PemilihPic v-model="form.pic" :error="galat.pic" />
    </section>

    <!-- Penjelasan rinci -->
    <section class="mt-8 border-t border-garis pt-6">
      <ClientOnly>
        <EditorQuill
          v-model:konten="konten"
          v-model:html="form.deskripsi"
          label="Penjelasan program"
          hint="Jelaskan program secara rinci: tujuan, sasaran, tempat, dan cara mengikutinya."
          placeholder="Tulis penjelasan program di sini..."
          :error="galat.deskripsi"
          required
        />

        <template #fallback>
          <p class="py-16 text-center text-[13px] text-teks-redup">Menyiapkan editor...</p>
        </template>
      </ClientOnly>
    </section>

    <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-garis pt-6">
      <AppButton type="submit" :loading="menyimpan" :disabled="menyimpan">
        {{ program ? 'Simpan perubahan' : 'Simpan program' }}
      </AppButton>
      <NuxtLink to="/admin/program" class="btn btn-hantu">Batal</NuxtLink>

      <p v-if="program?.diperbarui_pada" class="text-[13px] text-teks-redup sm:ml-auto">
        Terakhir diperbarui {{ formatTanggalWaktu(program.diperbarui_pada) }}
      </p>
    </div>
  </form>
</template>

<script setup lang="ts">
import type { KontenQuill, PicProgram, ProgramAdmin, StatusProgram } from '~/types/api'

const props = defineProps<{ program?: ProgramAdmin | null }>()

const emit = defineEmits<{ (e: 'tersimpan', program: ProgramAdmin, pesan: string): void }>()

const { simpan: simpanBaru, ubah } = useProgram()

const form = reactive<{
  nama: string
  ringkasan: string
  jadwal: string
  status: StatusProgram
  deskripsi: string
  pic: PicProgram[]
}>({
  nama: props.program?.nama ?? '',
  ringkasan: props.program?.ringkasan ?? '',
  jadwal: props.program?.jadwal ?? '',
  status: props.program?.status ?? 'aktif',
  deskripsi: props.program?.deskripsi ?? '',
  pic: [...(props.program?.pic ?? [])],
})

// Delta tidak disimpan backend; hanya dipakai editor selama formulir terbuka.
const konten = ref<KontenQuill>({ ops: [] })

const opsiStatus = [
  { nilai: 'aktif', label: 'Berjalan' },
  { nilai: 'selesai', label: 'Selesai' },
]

const menyimpan = ref(false)
const galatUmum = ref('')
const galat = ref<Record<string, string | undefined>>({})

// ------------------------------------------------------------ Sampul

const masukanSampul = ref<HTMLInputElement | null>(null)
const sampulTersimpan = ref<string | null>(props.program?.sampul ?? null)
const sampul = ref<File | null>(null)
const pratinjauSampul = ref('')
const hapusSampul = ref(false)

const sampulTampil = computed(() => {
  if (pratinjauSampul.value) return pratinjauSampul.value
  if (hapusSampul.value) return null

  return sampulTersimpan.value
})

const labelTombolHapus = computed(() => {
  if (sampul.value) return 'Batalkan pilihan'
  return hapusSampul.value ? 'Batalkan hapus' : 'Hapus sampul'
})

function bersihkanPratinjau() {
  if (pratinjauSampul.value) URL.revokeObjectURL(pratinjauSampul.value)
  pratinjauSampul.value = ''
}

/** Cek jenis dan ukuran di klien lebih dulu (KNF-03); backend tetap memeriksa ulang. */
function pilihSampul(event: Event) {
  const el = event.target as HTMLInputElement
  const berkas = el.files?.[0] ?? null

  el.value = '' // agar berkas yang sama bisa dipilih lagi setelah gagal

  if (!berkas) return

  galat.value = { ...galat.value, sampul: undefined }

  if (!['image/jpeg', 'image/png'].includes(berkas.type)) {
    galat.value.sampul = 'Sampul harus berformat JPG atau PNG.'
    return
  }

  if (berkas.size > 2 * 1024 * 1024) {
    galat.value.sampul = 'Ukuran sampul maksimal 2 MB.'
    return
  }

  bersihkanPratinjau()
  sampul.value = berkas
  pratinjauSampul.value = URL.createObjectURL(berkas)
  hapusSampul.value = false
}

/** Batalkan berkas yang baru dipilih, atau tandai sampul tersimpan untuk dihapus saat Simpan. */
function tandaiHapusSampul() {
  if (sampul.value) {
    bersihkanPratinjau()
    sampul.value = null
    return
  }

  hapusSampul.value = !hapusSampul.value
}

// ------------------------------------------------------------ Simpan

async function simpan() {
  menyimpan.value = true
  galatUmum.value = ''
  galat.value = {}

  const data = new FormData()
  data.append('nama', form.nama.trim())
  data.append('ringkasan', form.ringkasan.trim())
  data.append('jadwal', form.jadwal.trim())
  data.append('status', form.status)
  data.append('deskripsi', rapikanSpasiHtml(form.deskripsi))
  // Array dikirim sebagai pic[]=1&pic[]=2 agar terbaca Laravel sebagai array.
  for (const orang of form.pic) data.append('pic[]', String(orang.anggota_id))
  if (sampul.value) data.append('sampul', sampul.value)
  if (hapusSampul.value) data.append('hapus_sampul', '1')

  try {
    const respons = props.program ? await ubah(props.program.id, data) : await simpanBaru(data)

    sampulTersimpan.value = respons.data.sampul
    sampul.value = null
    hapusSampul.value = false
    bersihkanPratinjau()

    emit('tersimpan', respons.data, respons.message)
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    // Galat PIC bisa bernama `pic` atau `pic.0`, `pic.1`, dst.
    const kunciPic = Object.keys(isian).find((kunci) => kunci === 'pic' || kunci.startsWith('pic.'))

    galat.value = {
      nama: isian.nama?.[0],
      ringkasan: isian.ringkasan?.[0],
      jadwal: isian.jadwal?.[0],
      status: isian.status?.[0],
      deskripsi: isian.deskripsi?.[0],
      sampul: isian.sampul?.[0],
      pic: kunciPic ? isian[kunciPic]?.[0] : undefined,
    }

    if (Object.keys(isian).length === 0) galatUmum.value = pesanGalat(galatServer)
    else galatUmum.value = 'Periksa kembali isian yang ditandai.'
  } finally {
    menyimpan.value = false
  }
}

onBeforeUnmount(bersihkanPratinjau)
</script>
