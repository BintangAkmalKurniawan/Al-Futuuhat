<template>
  <section>
    <!-- Di dalam PanelDiskusi judulnya sudah ada di kepala laci, jadi di sini
         cukup baris tipis berisi jumlah komentar dan indikator siaran. -->
    <header class="flex items-center gap-3" :class="dalamPanel ? '' : 'pt-7'">
      <h2 v-if="!dalamPanel" class="text-xl font-semibold text-teks-kuat">Diskusi ({{ jumlah }})</h2>
      <p v-else class="text-[13px] text-teks-lembut">{{ jumlah }} komentar</p>

      <!-- Indikator siaran langsung (KP-15). -->
      <span
        class="inline-flex items-center gap-1.5 text-[12px]"
        :class="terhubung ? 'text-terbit' : 'text-teks-redup'"
      >
        <span
          class="h-1.75 w-1.75 rounded-full"
          :class="terhubung ? 'bg-terbit ring-4 ring-terbit/15' : 'bg-teks-redup'"
          aria-hidden="true"
        />
        {{ terhubung ? 'Langsung' : 'Terputus · menyambung ulang...' }}
      </span>
    </header>

    <div id="isi-forum" class="space-y-5 pt-5">
      <!-- Tamu tidak dapat membaca/menulis forum (rute forum wajib login). -->
      <div v-if="!isLoggedIn" class="space-y-3">
        <p class="text-sm text-teks-lembut">
          Masuk dulu sebagai anggota untuk ikut berdiskusi ya.
        </p>
        <AppButton size="sm" @click="navigateTo('/masuk')">Masuk untuk berdiskusi</AppButton>
      </div>

      <template v-else>
        <!-- Kotak tulis komentar: avatar dan nama di atas, bilah format di atas
             kotak bergaris, petunjuk lampiran di bawahnya (DESIGN.md §6.2 no. 09). -->
        <form @submit.prevent="kirim">
          <div class="flex items-center gap-3">
            <AvatarInisial :nama="user?.nama" :id="user?.id" />
            <span class="text-[15px] text-teks-kuat">{{ user?.nama }}</span>
          </div>

          <EditorQuill
            :key="versiEditor"
            v-model:html="isi"
            class="mt-3"
            komentar
            lampiran
            placeholder="Tulis pertanyaan atau tanggapan..."
            hint="Bisa lampirkan gambar JPG/PNG, maksimal 2 MB."
            :error="galatIsi"
            @lampiran="gambar = $event"
          >
            <template #aksi>
              <!-- Lampiran terpilih: nama berkas dan tombol hapus (KP-14.2). -->
              <span
                v-if="gambar"
                class="flex items-center gap-1.5 rounded-full bg-angkat px-3 py-1 text-[12px] text-teks-lembut"
              >
                <Paperclip class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                <span class="max-w-40 truncate">{{ gambar.name }}</span>
                <button
                  type="button"
                  class="text-teks-redup transition-colors duration-200 hover:text-bahaya"
                  aria-label="Hapus lampiran"
                  @click="gambar = null"
                >
                  <X class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
              </span>

              <AppButton variant="hantu" size="sm" @click="batalkan">Batal</AppButton>
              <AppButton type="submit" size="sm" :loading="mengirim">Kirim</AppButton>
            </template>
          </EditorQuill>
        </form>

        <AppAlert v-if="galat" variant="bahaya" title="Diskusinya belum bisa dimuat">{{ galat }}</AppAlert>

        <!-- Kerangka memuat berbentuk seperti komentar. -->
        <div v-if="memuat" class="animate-pulse space-y-5">
          <div v-for="n in 2" :key="n" class="space-y-2">
            <div class="h-3 w-32 rounded-full bg-permukaan" />
            <div class="h-3 w-full rounded-full bg-permukaan" />
          </div>
        </div>

        <p v-else-if="daftar.length === 0" class="text-sm text-teks-lembut">
          Belum ada komentar. Yuk, mulai diskusinya!
        </p>

        <div v-else class="space-y-6">
          <article
            v-for="komentar in daftar"
            :key="komentar.id"
            class="rounded-lg transition-colors duration-700"
            :class="baru.has(komentar.id) ? 'bg-aksen/10' : ''"
          >
            <div class="flex items-start gap-3">
              <AvatarInisial :nama="komentar.user?.nama" :id="komentar.user?.id" />

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 text-[13px]">
                  <span class="text-teks-kuat">{{ komentar.user?.nama }}</span>
                  <span v-if="komentar.user?.id === materiPenulisId" class="text-teks-redup">Penulis</span>
                  <span class="text-teks-redup">{{ formatWaktuRelatif(komentar.dibuat_pada) }}</span>
                </div>

                <IsiKomentar v-if="komentar.isi" :isi="komentar.isi" class="mt-1.5" />
                <img
                  v-if="komentar.gambar"
                  :src="`${asalBerkas}${komentar.gambar}`"
                  alt="Lampiran komentar"
                  class="mt-2 max-h-48 rounded-sm border border-garis object-cover"
                />

                <div class="mt-2 flex items-center gap-4 text-[13px]">
                  <button
                    type="button"
                    class="text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
                    @click="mulaiBalas(komentar.id)"
                  >
                    Balas
                  </button>
                  <button
                    v-if="komentar.user?.id === user?.id"
                    type="button"
                    class="text-teks-lembut transition-colors duration-200 hover:text-bahaya"
                    @click="akanDihapus = komentar.id"
                  >
                    Hapus
                  </button>
                </div>

                <!-- Balasan satu tingkat (KP-14.3) tidak ikut pada daftar komentar:
                     isinya diambil saat tombol ini ditekan (KP-14.5). -->
                <button
                  v-if="jumlahBalasan(komentar) > 0"
                  type="button"
                  class="mt-3 inline-flex items-center gap-1.5 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
                  :aria-expanded="terbukaBalasan.has(komentar.id)"
                  @click="alihkanBalasan(komentar)"
                >
                  <ChevronDown
                    class="h-3.5 w-3.5 transition-transform duration-200"
                    :class="terbukaBalasan.has(komentar.id) ? 'rotate-180' : ''"
                    aria-hidden="true"
                  />
                  {{
                    terbukaBalasan.has(komentar.id)
                      ? 'Sembunyikan balasan'
                      : `Lihat ${jumlahBalasan(komentar)} balasan`
                  }}
                </button>

                <p v-if="galatMuatBalasan[komentar.id]" class="pesan-galat mt-2" role="alert">
                  {{ galatMuatBalasan[komentar.id] }}
                </p>

                <!-- Kerangka memuat balasan. -->
                <div
                  v-if="sedangMuatBalasan.has(komentar.id)"
                  class="mt-4 animate-pulse space-y-3 border-l-2 border-garis pl-4"
                >
                  <div v-for="n in 2" :key="n" class="space-y-2">
                    <div class="h-3 w-28 rounded-full bg-permukaan" />
                    <div class="h-3 w-4/6 rounded-full bg-permukaan" />
                  </div>
                </div>

                <div
                  v-else-if="terbukaBalasan.has(komentar.id) && komentar.balasan?.length"
                  class="mt-4 space-y-4 border-l-2 border-garis pl-4"
                >
                  <div
                    v-for="balasan in komentar.balasan"
                    :key="balasan.id"
                    class="rounded-lg transition-colors duration-700"
                    :class="baru.has(balasan.id) ? 'bg-aksen/10' : ''"
                  >
                    <div class="flex flex-wrap items-center gap-x-2 text-[13px]">
                      <span class="text-teks-kuat">{{ balasan.user?.nama }}</span>
                      <span v-if="balasan.user?.id === materiPenulisId" class="text-teks-redup">Penulis</span>
                      <span class="text-teks-redup">{{ formatWaktuRelatif(balasan.dibuat_pada) }}</span>
                    </div>

                    <IsiKomentar v-if="balasan.isi" :isi="balasan.isi" class="mt-1" />
                    <img
                      v-if="balasan.gambar"
                      :src="`${asalBerkas}${balasan.gambar}`"
                      alt="Lampiran balasan"
                      class="mt-2 max-h-40 rounded-sm border border-garis object-cover"
                    />

                    <button
                      v-if="balasan.user?.id === user?.id"
                      type="button"
                      class="mt-1 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-bahaya"
                      @click="akanDihapus = balasan.id"
                    >
                      Hapus
                    </button>
                  </div>
                </div>

                <!-- Form balasan; tampilannya mengikuti kotak komentar di atas. -->
                <form
                  v-if="balasUntuk === komentar.id"
                  class="mt-3"
                  @submit.prevent="kirimBalasan(komentar.id)"
                >
                  <EditorQuill
                    :key="`balas-${komentar.id}`"
                    v-model:html="isiBalasan"
                    komentar
                    placeholder="Tulis balasan..."
                    :error="galatBalasan"
                  >
                    <template #aksi>
                      <AppButton variant="hantu" size="sm" @click="batalBalas">Batal</AppButton>
                      <AppButton type="submit" size="sm" :loading="mengirimBalasan">
                        Balas
                      </AppButton>
                    </template>
                  </EditorQuill>
                </form>
              </div>
            </div>
          </article>
        </div>

        <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
      </template>
    </div>

    <!-- Konfirmasi hapus komentar (ConfirmDialog) -->
    <AppModal
      v-model:open="modalHapus"
      title="Hapus komentar?"
      deskripsi="Komentar ini dan semua balasannya akan hilang untuk selamanya."
    >
      <AppAlert v-if="galatHapus" variant="bahaya" title="Belum berhasil dihapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">Komentar yang sudah dihapus tidak bisa dikembalikan.</p>

      <template #footer>
        <AppButton variant="hantu" size="sm" :disabled="menghapus" @click="modalHapus = false">
          Batal
        </AppButton>
        <AppButton variant="bahaya" size="sm" :loading="menghapus" @click="hapusSekarang">
          Ya, hapus
        </AppButton>
      </template>
    </AppModal>
  </section>
</template>

<script setup lang="ts">
import { ChevronDown, Paperclip, X } from '@lucide/vue'
import type { Komentar, MetaHalaman } from '~/types/api'

const props = defineProps<{
  materiId: number
  /** Penulis materi; komentator dengannya diberi label "Penulis". */
  materiPenulisId?: number | null
  /** true = komponen tampil di dalam laci (PanelDiskusi), judulnya dipangkas. */
  dalamPanel?: boolean
}>()

const {
  daftar: ambilDaftar,
  balasan: ambilBalasan,
  kirim: kirimKomentar,
  hapus: hapusKomentar,
} = useKomentar()
const { isLoggedIn, user } = useAuth()
const { $echo } = useNuxtApp()
const config = useRuntimeConfig()

// Storage::url mengembalikan path relatif (/storage/...); tambahkan asal backend.
const asalBerkas = config.public.apiBase.replace(/\/api\/?$/, '')

const daftar = ref<Komentar[]>([])
const meta = ref<MetaHalaman | null>(null)
const halaman = ref(1)
const memuat = ref(true)
const galat = ref('')

// Naikkan nilai ini untuk memasang ulang editor Quill setelah komentar terkirim.
const versiEditor = ref(0)

const isi = ref('')
const gambar = ref<File | null>(null)
const galatIsi = ref('')
const mengirim = ref(false)

const balasUntuk = ref<number | null>(null)
const isiBalasan = ref('')
const galatBalasan = ref('')
const mengirimBalasan = ref(false)

const akanDihapus = ref<number | null>(null)
const menghapus = ref(false)
const galatHapus = ref('')

// Balasan: hanya komentar yang dibuka pemakainya yang diambil dari server (KP-14.5).
const terbukaBalasan = ref(new Set<number>())
const sedangMuatBalasan = ref(new Set<number>())
const galatMuatBalasan = ref<Record<number, string>>({})

// Komentar yang baru masuk lewat siaran; diberi latar aksen yang memudar.
const baru = ref(new Set<number>())

// Keadaan sambungan Reverb, ditampilkan sebagai "Langsung"/"Terputus".
const terhubung = ref(false)

const jumlah = computed(() => meta.value?.total ?? daftar.value.length)

const modalHapus = computed({
  get: () => akanDihapus.value !== null,
  set: (nilai: boolean) => {
    if (!nilai) akanDihapus.value = null
  },
})

/**
 * Quill selalu menghasilkan HTML, termasuk saat kosong (<p><br></p>).
 * Buang tag dan entitas spasi sebelum memastikan komentar benar-benar berisi.
 */
function htmlKosong(nilai: string) {
  return !nilai
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .trim()
}

/** Tandai komentar baru agar sempat tersorot, lalu bersihkan setelah 2 detik. */
function tandaiBaru(id: number) {
  baru.value = new Set([...baru.value, id])

  setTimeout(() => {
    const sisa = new Set(baru.value)
    sisa.delete(id)
    baru.value = sisa
  }, 2000)
}

async function muat() {
  memuat.value = true
  galat.value = ''

  // Daftar dimuat ulang tanpa balasan, jadi balasan yang tadi terbuka ditutup
  // lagi; isinya diambil lagi saat tombolnya ditekan.
  terbukaBalasan.value = new Set()
  galatMuatBalasan.value = {}

  try {
    const respons = await ambilDaftar(props.materiId, halaman.value)
    daftar.value = respons.data
    meta.value = respons.meta
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer, 'Diskusinya belum bisa dimuat.')
  } finally {
    memuat.value = false
  }
}

/** Jumlah balasan: dari daftar yang sudah diambil, atau angka dari server. */
function jumlahBalasan(komentar: Komentar) {
  return komentar.balasan?.length ?? komentar.jumlah_balasan ?? 0
}

/** Tambah/buang satu id pada kumpulan id di dalam ref (Set diganti, bukan diubah). */
function setKe(dari: Ref<Set<number>>, id: number, tambah: boolean) {
  const salinan = new Set(dari.value)
  if (tambah) salinan.add(id)
  else salinan.delete(id)

  dari.value = salinan
}

/** Ambil balasan satu induk dari server, lalu tempelkan pada komentarnya. */
async function bukaBalasan(komentar: Komentar) {
  if (komentar.balasan) {
    setKe(terbukaBalasan, komentar.id, true)
    return
  }

  setKe(terbukaBalasan, komentar.id, true)
  setKe(sedangMuatBalasan, komentar.id, true)

  const sisaGalat = { ...galatMuatBalasan.value }
  delete sisaGalat[komentar.id]
  galatMuatBalasan.value = sisaGalat

  try {
    const respons = await ambilBalasan(komentar.id)
    komentar.balasan = respons.data
  } catch (galatServer) {
    galatMuatBalasan.value = {
      ...galatMuatBalasan.value,
      [komentar.id]: pesanGalat(galatServer, 'Balasannya belum bisa dimuat.'),
    }
    setKe(terbukaBalasan, komentar.id, false)
  } finally {
    setKe(sedangMuatBalasan, komentar.id, false)
  }
}

/** Tombol "Lihat n balasan" / "Sembunyikan balasan". */
function alihkanBalasan(komentar: Komentar) {
  if (terbukaBalasan.value.has(komentar.id)) {
    setKe(terbukaBalasan, komentar.id, false)
    return
  }

  bukaBalasan(komentar)
}

async function kirim() {
  if (htmlKosong(isi.value) && !gambar.value) {
    galatIsi.value = 'Tulis komentar atau lampirkan gambar dulu ya.'
    return
  }

  mengirim.value = true
  galatIsi.value = ''

  try {
    // isi berupa HTML hasil Quill; backend menyaringnya sebelum disimpan.
    await kirimKomentar(props.materiId, { isi: isi.value, gambar: gambar.value })

    isi.value = ''
    gambar.value = null
    versiEditor.value += 1

    await muat()

    // Komentar terbaru berada di halaman terakhir (urutan terlama dahulu).
    if (meta.value && meta.value.current_page !== meta.value.last_page) {
      halaman.value = meta.value.last_page
    }
  } catch (galatServer) {
    galatIsi.value =
      galatIsian(galatServer, 'isi') ||
      galatIsian(galatServer, 'gambar') ||
      pesanGalat(galatServer, 'Komentarmu belum terkirim. Coba lagi ya.')
  } finally {
    mengirim.value = false
  }
}

/** Tombol "Batal": kosongkan isian dan lampiran, lalu pasang ulang editor. */
function batalkan() {
  isi.value = ''
  gambar.value = null
  galatIsi.value = ''
  versiEditor.value += 1
}

function mulaiBalas(id: number) {
  balasUntuk.value = id
  isiBalasan.value = ''
  galatBalasan.value = ''
}

function batalBalas() {
  balasUntuk.value = null
  isiBalasan.value = ''
  galatBalasan.value = ''
}

async function kirimBalasan(indukId: number) {
  if (htmlKosong(isiBalasan.value)) {
    galatBalasan.value = 'Tulis balasanmu dulu ya.'
    return
  }

  mengirimBalasan.value = true
  galatBalasan.value = ''

  try {
    // parent_id selalu komentar induk; backend meratakan balasan satu tingkat.
    const respons = await kirimKomentar(props.materiId, {
      isi: isiBalasan.value,
      parent_id: indukId,
    })

    const induk = daftar.value.find((komentar) => komentar.id === indukId)

    batalBalas()

    // Balasan langsung ditempelkan ke induknya, tanpa memuat ulang seluruh daftar.
    if (induk) {
      if (induk.balasan) {
        induk.balasan = [...induk.balasan, respons.data]
        tandaiBaru(respons.data.id)
      } else {
        // Belum pernah diambil: buka induknya, sekaligus ambil balasannya dari server.
        induk.jumlah_balasan = (induk.jumlah_balasan ?? 0) + 1
        await bukaBalasan(induk)
      }
    }
  } catch (galatServer) {
    galatBalasan.value =
      galatIsian(galatServer, 'isi') || pesanGalat(galatServer, 'Balasanmu belum terkirim. Coba lagi ya.')
  } finally {
    mengirimBalasan.value = false
  }
}

async function hapusSekarang() {
  if (akanDihapus.value === null) return

  menghapus.value = true
  galatHapus.value = ''

  try {
    await hapusKomentar(akanDihapus.value)
    akanDihapus.value = null
    await muat()
  } catch (galatServer) {
    galatHapus.value = pesanGalat(galatServer, 'Komentarnya belum berhasil dihapus. Coba lagi ya.')
  } finally {
    menghapus.value = false
  }
}

/** Buang satu balasan dari induknya; bila balasannya belum diambil, kurangi angkanya. */
function hapusDariBalasan(induk: Komentar, balasanId: number): Komentar {
  if (induk.balasan) {
    return { ...induk, balasan: induk.balasan.filter((balasan) => balasan.id !== balasanId) }
  }

  return { ...induk, jumlah_balasan: Math.max((induk.jumlah_balasan ?? 0) - 1, 0) }
}

/**
 * Berlangganan kanal privat materi (KP-15). Komentar buatan sendiri tidak
 * diterima kembali karena backend memakai ->toOthers().
 */
function berlangganan() {
  if (!isLoggedIn.value || !$echo) return

  $echo
    .private(`materi.${props.materiId}`)
    .listen('.komentar.dibuat', (data: Komentar) => {
      if (typeof data?.id === 'number') tandaiBaru(data.id)

      // Balasan baru (KP-14.5): cukup tambah angkanya, atau langsung tampilkan
      // bila induknya sedang terbuka. Isinya tidak perlu diambil ulang.
      if (data?.parent_id) {
        const induk = daftar.value.find((komentar) => komentar.id === data.parent_id)

        if (induk) {
          if (induk.balasan) induk.balasan = [...induk.balasan, data]
          else induk.jumlah_balasan = (induk.jumlah_balasan ?? 0) + 1
        }

        return
      }

      // Komentar induk baru: daftar dimuat ulang agar urutannya tetap benar.
      muat()
    })
    .listen('.komentar.dihapus', (data: { id: number; parent_id: number | null }) => {
      // Balasan: buang dari daftar balasan induknya, atau kurangi angkanya.
      if (data.parent_id) {
        daftar.value = daftar.value.map((komentar) =>
          komentar.id === data.parent_id ? hapusDariBalasan(komentar, data.id) : komentar,
        )

        return
      }

      // Komentar induk: balasannya ikut terhapus (KP-14.4).
      daftar.value = daftar.value.filter((komentar) => komentar.id !== data.id)
    })
}

/** Ikuti keadaan sambungan Reverb untuk indikator "Langsung"/"Terputus". */
function pantauSambungan() {
  const koneksi = (
    $echo as unknown as {
      connector?: {
        pusher?: {
          connection?: { state: string; bind: (nama: string, cb: (data: { current: string }) => void) => void }
        }
      }
    }
  )?.connector?.pusher?.connection

  if (!koneksi) return

  terhubung.value = koneksi.state === 'connected'
  koneksi.bind('state_change', (data) => {
    terhubung.value = data.current === 'connected'
  })
}

onMounted(async () => {
  if (isLoggedIn.value) await muat()
  else memuat.value = false

  berlangganan()
  pantauSambungan()
})

onBeforeUnmount(() => {
  $echo?.leave(`materi.${props.materiId}`)
})

watch(halaman, muat)
</script>
