<template>
  <!-- Unggah dokumentasi program -->
  <section class="rounded-lg bg-permukaan p-5 ring-1 ring-garis">
    <h2 class="text-sm font-medium text-teks-kuat">Tambah dokumentasi</h2>

    <FilterSegmen v-model="jenis" :opsi="opsiJenis" label="Jenis dokumentasi" class="mt-4" />

    <!-- Foto / video -->
    <div v-if="jenis !== 'youtube'" class="mt-5 space-y-4">
      <div class="flex flex-wrap items-center gap-3">
        <input
          ref="masukanBerkas"
          type="file"
          multiple
          :accept="jenis === 'foto' ? 'image/jpeg,image/png' : 'video/mp4'"
          class="hidden"
          @change="pilihBerkas"
        />
        <AppButton variant="sekunder" size="sm" :disabled="mengunggah" @click="masukanBerkas?.click()">
          <Upload class="h-4 w-4" aria-hidden="true" />
          {{ jenis === 'foto' ? 'Pilih foto' : 'Pilih video' }}
        </AppButton>
        <p class="text-[13px] text-teks-redup">
          {{ jenis === 'foto' ? 'JPG atau PNG, maksimal 2 MB per foto.' : 'MP4, maksimal 50 MB per video.' }}
        </p>
      </div>

      <AppInput
        v-model="keterangan"
        label="Keterangan"
        placeholder="Misalnya: Suasana kajian pekan pertama"
        hint="Opsional. Dipakai untuk semua berkas yang diunggah bersamaan."
        :disabled="mengunggah"
      />

      <!-- Antrean berkas -->
      <ul v-if="antrean.length" class="divide-y divide-garis rounded-md ring-1 ring-garis">
        <li v-for="(butir, indeks) in antrean" :key="indeks" class="flex items-center gap-3 px-3 py-2 text-sm">
          <component :is="jenis === 'foto' ? Image : Film" class="h-4 w-4 shrink-0 text-teks-redup" aria-hidden="true" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-teks">{{ butir.berkas.name }}</span>
            <span class="block text-[12px]" :class="kelasStatus[butir.status]">
              {{ ukuranBerkas(butir.berkas.size) }} · {{ butir.pesan }}
            </span>
          </span>
          <button
            v-if="butir.status === 'menunggu' || butir.status === 'gagal'"
            type="button"
            class="grid h-7 w-7 place-items-center rounded-full text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
            :aria-label="`Keluarkan ${butir.berkas.name} dari antrean`"
            :disabled="mengunggah"
            @click="antrean.splice(indeks, 1)"
          >
            <X class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
        </li>
      </ul>

      <AppButton
        v-if="jumlahSiap > 0"
        size="sm"
        :loading="mengunggah"
        :disabled="mengunggah"
        @click="unggahSemua"
      >
        Unggah {{ jumlahSiap }} berkas
      </AppButton>
    </div>

    <!-- Tautan YouTube -->
    <form v-else novalidate class="mt-5 space-y-4" @submit.prevent="tambahYoutube">
      <AppInput
        v-model="tautan"
        type="url"
        label="Tautan YouTube"
        placeholder="https://www.youtube.com/watch?v=..."
        hint="Bentuk youtube.com/watch, youtu.be, atau youtube.com/shorts."
        :error="galatTautan"
        required
      />
      <AppInput
        v-model="keterangan"
        label="Keterangan"
        placeholder="Misalnya: Rekaman kajian lengkap"
        hint="Opsional."
        :error="galatKeterangan"
      />
      <AppButton type="submit" size="sm" :loading="mengunggah" :disabled="mengunggah">
        Tambahkan video
      </AppButton>
    </form>
  </section>
</template>

<script setup lang="ts">
import { Film, Image, Upload, X } from '@lucide/vue'
import type { JenisDokumentasi } from '~/types/api'

const props = defineProps<{ programId: number }>()
const emit = defineEmits<{ (e: 'terunggah'): void }>()

const { unggahDokumentasi } = useProgram()
const { tampilkan } = usePesanToast()

type StatusButir = 'menunggu' | 'mengunggah' | 'berhasil' | 'gagal'

interface ButirAntrean {
  berkas: File
  status: StatusButir
  pesan: string
}

const BATAS_FOTO = 2 * 1024 * 1024
const BATAS_VIDEO = 50 * 1024 * 1024

const jenis = ref<JenisDokumentasi>('foto')
const keterangan = ref('')
const tautan = ref('')
const galatTautan = ref('')
const galatKeterangan = ref('')
const antrean = ref<ButirAntrean[]>([])
const mengunggah = ref(false)
const masukanBerkas = ref<HTMLInputElement | null>(null)

const opsiJenis = [
  { nilai: 'foto', label: 'Foto' },
  { nilai: 'video', label: 'Video' },
  { nilai: 'youtube', label: 'Tautan YouTube' },
]

const kelasStatus: Record<StatusButir, string> = {
  menunggu: 'text-teks-redup',
  mengunggah: 'text-aksen',
  berhasil: 'text-terbit',
  gagal: 'text-bahaya',
}

const jumlahSiap = computed(() => antrean.value.filter((butir) => butir.status === 'menunggu').length)

watch(jenis, () => {
  if (!mengunggah.value) antrean.value = []
  galatTautan.value = ''
  galatKeterangan.value = ''
})

function ukuranBerkas(bita: number) {
  return bita >= 1024 * 1024 ? `${(bita / 1024 / 1024).toFixed(1)} MB` : `${Math.ceil(bita / 1024)} KB`
}

// Periksa berkas
function periksa(berkas: File): string {
  if (jenis.value === 'foto') {
    if (!['image/jpeg', 'image/png'].includes(berkas.type)) return 'Foto harus berformat JPG atau PNG.'
    if (berkas.size > BATAS_FOTO) return 'Ukuran foto maksimal 2 MB.'
  } else {
    if (berkas.type !== 'video/mp4') return 'Video harus berformat MP4.'
    if (berkas.size > BATAS_VIDEO) return 'Ukuran video maksimal 50 MB.'
  }

  return ''
}

function pilihBerkas(event: Event) {
  const el = event.target as HTMLInputElement
  const daftar = Array.from(el.files ?? [])

  el.value = ''

  antrean.value = antrean.value.filter((butir) => butir.status !== 'berhasil')

  for (const berkas of daftar) {
    const masalah = periksa(berkas)

    antrean.value.push({
      berkas,
      status: masalah ? 'gagal' : 'menunggu',
      pesan: masalah || 'Siap diunggah',
    })
  }
}

// Unggah semua berkas
async function unggahSemua() {
  mengunggah.value = true
  let berhasil = 0

  for (const butir of antrean.value) {
    if (butir.status !== 'menunggu') continue

    butir.status = 'mengunggah'
    butir.pesan = 'Mengunggah...'

    const data = new FormData()
    data.append('jenis', jenis.value)
    data.append('berkas', butir.berkas)
    if (keterangan.value.trim()) data.append('keterangan', keterangan.value.trim())

    try {
      await unggahDokumentasi(props.programId, data)
      butir.status = 'berhasil'
      butir.pesan = 'Berhasil diunggah'
      berhasil++
    } catch (galatServer) {
      const isian = galatValidasi(galatServer)

      butir.status = 'gagal'
      butir.pesan = isian.berkas?.[0] ?? isian.keterangan?.[0] ?? pesanGalat(galatServer)
    }
  }

  mengunggah.value = false

  if (berhasil > 0) {
    keterangan.value = ''
    tampilkan(`${berhasil} dokumentasi ditambahkan.`, 'sukses')
    emit('terunggah')
  }
}

async function tambahYoutube() {
  galatTautan.value = ''
  galatKeterangan.value = ''

  if (!tautan.value.trim()) {
    galatTautan.value = 'Tautan YouTube wajib diisi.'
    return
  }

  mengunggah.value = true

  const data = new FormData()
  data.append('jenis', 'youtube')
  data.append('tautan', tautan.value.trim())
  if (keterangan.value.trim()) data.append('keterangan', keterangan.value.trim())

  try {
    const respons = await unggahDokumentasi(props.programId, data)

    tautan.value = ''
    keterangan.value = ''
    tampilkan(respons.message, 'sukses')
    emit('terunggah')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatTautan.value = isian.tautan?.[0] ?? ''
    galatKeterangan.value = isian.keterangan?.[0] ?? ''

    if (!galatTautan.value && !galatKeterangan.value) tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    mengunggah.value = false
  }
}
</script>
