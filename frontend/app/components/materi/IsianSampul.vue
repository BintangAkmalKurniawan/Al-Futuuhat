<template>
  <!-- Sampul artikel (KF-18): gambar kartu di daftar materi dan halaman artikel
       pengunjung. Terkunci bila materi sudah pernah terbit (KP-12.4). -->
  <div>
    <p class="text-xs font-medium tracking-wider text-teks-redup uppercase">Sampul artikel</p>

    <div class="mt-2 flex items-start gap-4">
      <div
        class="flex h-[90px] w-40 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-garis bg-permukaan"
      >
        <img
          v-if="url"
          :src="url"
          alt="Pratinjau sampul artikel"
          class="h-full w-full object-cover"
        />
        <span v-else class="px-2 text-center text-[11px] text-teks-redup">Belum ada sampul</span>
      </div>

      <div class="min-w-0">
        <p class="text-[13px] text-teks-redup">
          JPG atau PNG, maksimal 2 MB. Gambar ini jadi sampul artikelmu di halaman umum.
        </p>

        <p v-if="nonaktif" class="mt-2 text-[13px] text-teks-redup">
          Sampul belum bisa diubah saat ini.
        </p>

        <div v-else class="mt-2 flex flex-wrap gap-2">
          <!-- Masukan berkas disembunyikan; tombolnya yang tampil ke pengguna. -->
          <input
            ref="masukan"
            type="file"
            accept="image/jpeg,image/png"
            class="hidden"
            @change="pilihBerkas"
          />

          <AppButton
            variant="sekunder"
            size="sm"
            :loading="mengunggah"
            :disabled="mengunggah"
            @click="bukaPemilih"
          >
            {{ url ? 'Ganti sampul' : 'Pilih sampul' }}
          </AppButton>

          <AppButton v-if="url" variant="hantu" size="sm" :disabled="mengunggah" @click="lepas">
            Hapus sampul
          </AppButton>
        </div>

        <p v-if="galat" class="pesan-galat" role="alert">{{ galat }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  /** Sampul terkunci (materi sudah pernah terbit). */
  nonaktif?: boolean
  /** Galat 422 dari backend untuk isian `sampul`. */
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  nonaktif: false,
  error: '',
})

// Path berkas di server (dikirim ke backend) dan URL pratinjaunya.
const path = defineModel<string | null>('path', { default: null })
const url = defineModel<string | null>('url', { default: null })

const { unggahSampul } = useMateri()

const masukan = ref<HTMLInputElement | null>(null)
const mengunggah = ref(false)
const galatKlien = ref('')

const galat = computed(() => props.error || galatKlien.value)

function bukaPemilih() {
  masukan.value?.click()
}

/** Cek jenis dan ukuran di klien lebih dulu (KNF-03); backend tetap memeriksa ulang. */
async function pilihBerkas(event: Event) {
  const el = event.target as HTMLInputElement
  const berkas = el.files?.[0] ?? null

  // Isian dikosongkan agar berkas yang sama bisa dipilih lagi setelah gagal.
  el.value = ''

  if (!berkas) return

  galatKlien.value = ''

  if (!['image/jpeg', 'image/png'].includes(berkas.type)) {
    galatKlien.value = 'Gambarnya harus JPG atau PNG ya.'
    return
  }

  if (berkas.size > 2 * 1024 * 1024) {
    galatKlien.value = 'Gambarnya kebesaran, maksimal 2 MB ya.'
    return
  }

  mengunggah.value = true

  try {
    const respons = await unggahSampul(berkas)
    path.value = respons.path
    url.value = respons.url
  } catch (galatServer) {
    galatKlien.value = pesanGalat(galatServer, 'Sampul belum berhasil diunggah. Coba lagi ya.')
  } finally {
    mengunggah.value = false
  }
}

/** Lepas sampul: hanya menghapus rujukannya di formulir, disimpan saat simpan ditekan. */
function lepas() {
  galatKlien.value = ''
  path.value = null
  url.value = null
}
</script>
