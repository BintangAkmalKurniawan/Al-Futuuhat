<template>
  <div class="mx-auto max-w-[780px]">
    <header class="flex flex-wrap items-end gap-4">
      <div class="min-w-0 flex-1">
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">Notifikasi</h1>
        <p class="mt-1.5 text-sm text-teks-lembut">
          {{ jumlahBelumDibaca > 0 ? `${jumlahBelumDibaca} belum dibaca` : 'Semua sudah dibaca' }}
        </p>
      </div>

      <AppButton
        v-if="jumlahBelumDibaca > 0"
        variant="hantu"
        :loading="memprosesSemua"
        @click="bacaSemua"
      >
        <Check class="h-4 w-4" aria-hidden="true" />
        Tandai semua dibaca
      </AppButton>
    </header>

    <AppAlert v-if="galat" variant="bahaya" title="Notifikasi belum bisa dimuat" class="mt-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat berbentuk baris notifikasi (DESIGN.md §11). -->
    <div v-if="memuat" class="mt-5 animate-pulse space-y-6 border-t border-garis pt-6">
      <div v-for="n in 3" :key="n" class="flex gap-3.5">
        <div class="h-9 w-9 shrink-0 rounded-full bg-permukaan" />
        <div class="flex-1 space-y-2">
          <div class="h-3.5 w-3/4 rounded-full bg-permukaan" />
          <div class="h-3 w-24 rounded-full bg-permukaan" />
        </div>
      </div>
    </div>

    <AppEmptyState
      v-else-if="notifikasi.length === 0"
      title="Belum ada notifikasi."
      description="Kabar terbaru tentang materi akan muncul di sini."
    />

    <div v-else class="mt-5 border-t border-garis pl-4">
      <article
        v-for="item in notifikasi"
        :key="item.id"
        class="relative flex gap-3.5 border-b border-garis py-[18px]"
        :class="item.dibaca ? 'opacity-70' : ''"
      >
        <!-- Titik aksen untuk yang belum dibaca (DESIGN.md §6.3 no. 16). -->
        <span
          v-if="!item.dibaca"
          class="absolute top-8 -left-4 h-[7px] w-[7px] rounded-full bg-aksen"
          aria-hidden="true"
        />

        <span
          class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-permukaan text-teks-lembut"
        >
          <component :is="ikon(item)" class="h-4 w-4" aria-hidden="true" />
        </span>

        <div class="min-w-0 flex-1">
          <p class="text-sm leading-relaxed text-teks">
            {{ awalan(item) }}
            <span class="font-semibold text-teks-kuat">{{ item.data.judul }}</span>
            {{ akhiran(item) }}
          </p>

          <!-- Cuplikan komentar baru (KF-23). -->
          <p
            v-if="item.data.cuplikan"
            class="mt-2 rounded-r-md border-l-2 border-garis-kuat bg-permukaan px-3 py-2.5 text-[13px] text-teks-lembut"
          >
            “{{ item.data.cuplikan }}”
          </p>

          <!-- Alasan penarikan dari penulis, untuk admin (KF-20). -->
          <p
            v-if="item.data.alasan"
            class="mt-2 rounded-r-md border-l-2 border-aksen bg-permukaan px-3 py-2.5 text-[13px] text-teks-lembut"
          >
            {{ item.data.alasan }}
          </p>

          <!-- Catatan admin hanya ada pada notifikasi penolakan untuk penulis. -->
          <p
            v-if="item.data.catatan"
            class="mt-2 rounded-r-md border-l-2 border-ditolak bg-permukaan px-3 py-2.5 text-[13px] text-teks-lembut"
          >
            {{ item.data.catatan }}
          </p>

          <p class="mt-1 text-[13px] text-teks-redup">
            {{ waktu(item) }}
            <template v-if="tautan(item)">
              ·
              <NuxtLink :to="tautan(item)!" class="text-aksen hover:underline">Buka</NuxtLink>
            </template>
          </p>
        </div>

        <button
          v-if="!item.dibaca"
          type="button"
          class="shrink-0 self-start text-[13px] text-teks-redup transition-colors duration-200 hover:text-teks-kuat disabled:opacity-50"
          :disabled="memproses.has(item.id)"
          @click="bacaSatu(item)"
        >
          Tandai dibaca
        </button>
      </article>
    </div>

    <AppPagination class="mt-8" :meta="meta" @ganti-halaman="gantiHalaman" />
  </div>
</template>

<script setup lang="ts">
import { Check, CircleCheck, FileText, MessageCircle, Undo2, X } from '@lucide/vue'
import type { DasborAnggota, MetaHalaman, Notifikasi, ResponsData } from '~/types/api'

definePageMeta({ layout: 'anggota', middleware: ['auth'] })
useHead({ title: 'Notifikasi' })

const { daftar, tandaiDibaca, tandaiSemuaDibaca } = useNotifikasi()
const { tampilkan } = usePesanToast()
const api = useApi()

const notifikasi = ref<Notifikasi[]>([])
const ringkasan = ref<DasborAnggota | null>(null)
const meta = ref<MetaHalaman>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  from: null,
  to: null,
  total: 0,
  links: [],
  path: '',
})
const halaman = ref(1)

const memuat = ref(true)
const galat = ref('')
const memprosesSemua = ref(false)
// Id notifikasi yang sedang diproses, agar tombolnya nonaktif sementara.
const memproses = ref(new Set<string>())

// Jumlah belum dibaca diambil dari ringkasan dasbor agar mencakup semua halaman;
// bila gagal, dihitung dari notifikasi yang sedang tampil.
const jumlahBelumDibaca = computed(
  () => ringkasan.value?.notifikasi_belum_dibaca ?? notifikasi.value.filter((item) => !item.dibaca).length,
)

function ikon(item: Notifikasi) {
  if (item.data.tipe === 'komentar_baru') return MessageCircle
  if (item.data.tipe === 'materi_diajukan') return FileText
  if (item.data.tipe === 'penarikan_diajukan') return Undo2
  if (item.data.keputusan === 'ditolak') return X

  return CircleCheck
}

// Kalimat notifikasi = awalan + judul materi (tebal) + akhiran.
// Notifikasi untuk admin memakai bahasa formal; untuk penulis memakai bahasa santai
// (Panduan Bahasa UI di CLAUDE.md). Nama penulis sudah tampil di baris waktu.
function awalan(item: Notifikasi): string {
  // Komentar baru (KF-23): netral, karena penerimanya bisa anggota maupun admin.
  if (item.data.tipe === 'komentar_baru') {
    const pengirim = item.data.pengirim ?? 'Seseorang'
    return item.data.jenis === 'balasan'
      ? `${pengirim} membalas komentar di materi`
      : `${pengirim} mengomentari materi`
  }

  if (item.data.tipe === 'penarikan_diputuskan' && item.data.keputusan === 'ditolak') {
    return 'Admin belum menyetujui penurunan materi'
  }

  if (item.data.tipe === 'penarikan_diajukan') return 'Penulis meminta materi'

  return 'Materi'
}

function akhiran(item: Notifikasi): string {
  if (item.data.tipe === 'komentar_baru') return '.'

  if (item.data.tipe === 'penarikan_diajukan') return ' diturunkan dari halaman umum. Mohon ditinjau.'

  if (item.data.tipe === 'materi_diajukan') return ' diajukan untuk ditayangkan kepada umum. Mohon ditinjau.'

  if (item.data.tipe === 'penarikan_diputuskan') {
    return item.data.keputusan === 'ditolak'
      ? ', jadi materinya masih tayang. Cek catatan admin di bawah ya.'
      : ' sudah diturunkan dari halaman umum. Sekarang kamu bisa mengajukan versi terbarumu.'
  }

  if (item.data.keputusan === 'ditolak') {
    return ' belum disetujui admin. Cek catatannya di bawah, perbaiki, lalu ajukan lagi ya.'
  }

  return ' sudah tayang untuk umum. Selamat! 🎉'
}

/** Halaman tujuan tiap jenis notifikasi; admin ke antrean, penulis ke editor materinya. */
function tautan(item: Notifikasi): string | null {
  switch (item.data.tipe) {
    case 'materi_diajukan':
      return item.data.revisi_id ? `/admin/verifikasi-materi/${item.data.revisi_id}` : '/admin/verifikasi-materi'
    case 'penarikan_diajukan':
      return '/admin/verifikasi-materi?tab=penarikan'
    case 'revisi_diverifikasi':
    case 'penarikan_diputuskan':
      return `/materi-saya/${item.data.materi_id}/edit`
    case 'komentar_baru':
      return item.data.materi_slug ? `/materi/${item.data.materi_slug}` : null
    default:
      return null
  }
}

function waktu(item: Notifikasi): string {
  const relatif = formatWaktuRelatif(item.dibuat_pada)

  // Nama penulis hanya tersedia pada notifikasi untuk admin.
  if ((item.data.tipe === 'materi_diajukan' || item.data.tipe === 'penarikan_diajukan') && item.data.penulis) {
    return `${relatif} · oleh ${item.data.penulis}`
  }

  return relatif
}

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftar(halaman.value)
    notifikasi.value = respons.data
    meta.value = respons.meta
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

function gantiHalaman(nomor: number) {
  halaman.value = nomor
  muat()
}

async function bacaSatu(item: Notifikasi) {
  if (memproses.value.has(item.id)) return

  memproses.value.add(item.id)

  try {
    await tandaiDibaca(item.id)
    // Perbarui tampilan tanpa memuat ulang seluruh daftar.
    item.dibaca = true

    if (ringkasan.value) {
      ringkasan.value.notifikasi_belum_dibaca = Math.max(
        0,
        ringkasan.value.notifikasi_belum_dibaca - 1,
      )
    }
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    memproses.value.delete(item.id)
  }
}

async function bacaSemua() {
  memprosesSemua.value = true

  try {
    const respons = await tandaiSemuaDibaca()
    tampilkan(respons.message, 'sukses')
    // Semua sudah dibaca: angka di kepala halaman ikut nol.
    if (ringkasan.value) ringkasan.value.notifikasi_belum_dibaca = 0
    await muat()
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    memprosesSemua.value = false
  }
}

async function muatRingkasan() {
  try {
    const respons = await api.get<ResponsData<DasborAnggota>>('/dasbor')
    ringkasan.value = respons.data
  } catch {
    // Gagal: jumlah belum dibaca dihitung dari daftar yang tampil.
    ringkasan.value = null
  }
}

onMounted(() => {
  muatRingkasan()
  muat()
})
</script>