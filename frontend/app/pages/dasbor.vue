<template>
  <!-- Dasbor anggota -->
  <div class="mx-auto w-full max-w-halaman">
    <header class="pb-1">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">
        Selamat datang, {{ user?.nama ?? 'anggota' }}
      </h1>
      <p class="mt-3 text-sm text-teks-lembut">Ringkasan materimu dan kabar terbaru.</p>
    </header>

    <!-- Kerangka memuat -->
    <div v-if="memuat" class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-[86px] animate-pulse rounded-lg bg-permukaan" />
    </div>

    <div v-else class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="kartu in kartuMateri"
        :key="kartu.kunci"
        :label="kartu.label"
        :nilai="kartu.nilai"
        :ke="{ path: '/materi-saya', query: { tab: kartu.kunci } }"
      />
    </div>

    <div class="mt-9 grid grid-cols-[minmax(0,1fr)] gap-14 lg:grid-cols-[minmax(0,1fr)_360px]">
      <!-- Panel kiri -->
      <section>
        <PanelJudul judul="Materi saya terbaru" tautan="Lihat semua" ke="/materi-saya" />

        <div v-if="memuatMateri" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 3" :key="n" class="space-y-2 border-b border-garis pb-4">
            <div class="h-3.5 w-2/3 rounded-full bg-permukaan" />
            <div class="h-3 w-40 rounded-full bg-permukaan" />
          </div>
        </div>

        <p v-else-if="materiTerbaru.length === 0" class="py-5 text-sm text-teks-lembut">
          Kamu belum menulis materi.
        </p>

        <template v-else>
          <NuxtLink
            v-for="item in materiTerbaru"
            :key="item.id"
            :to="`/materi/${item.slug}`"
            class="flex items-start gap-3.5 border-b border-garis py-4 transition-colors duration-200 hover:bg-permukaan/40"
          >
            <span class="min-w-0 flex-1">
              <span class="block font-serif text-[17px] leading-[1.3] font-semibold text-teks-kuat">
                {{ item.judul }}
              </span>
              <span class="mt-1 block text-[13px] text-teks-redup">{{ metaMateri(item) }}</span>
            </span>

            <StatusChip :status="item.status" />
          </NuxtLink>
        </template>

        <AppButton class="mt-6" @click="navigateTo('/materi-saya/baru')">Tulis materi baru</AppButton>
      </section>

      <!-- Panel kanan -->
      <section>
        <PanelJudul judul="Notifikasi" tautan="Lihat semua" ke="/notifikasi" />

        <div v-if="memuatNotifikasi" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 3" :key="n" class="h-12 rounded-full bg-permukaan" />
        </div>

        <p v-else-if="notifikasi.length === 0" class="py-5 text-sm text-teks-lembut">
          Belum ada notifikasi.
        </p>

        <div v-else class="mt-3 border-t border-garis pl-4">
          <article
            v-for="item in notifikasi"
            :key="item.id"
            class="relative flex gap-3.5 border-b border-garis py-[18px]"
            :class="item.dibaca ? 'opacity-70' : ''"
          >
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
              <p class="text-sm leading-relaxed text-teks">{{ item.data.pesan }}</p>
              <p class="mt-1 text-[13px] text-teks-redup">
                {{ formatWaktuRelatif(item.dibuat_pada) }}
              </p>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CircleCheck, FileText, MessageCircle, Undo2, X } from '@lucide/vue'
import type {
  DasborAnggota,
  MateriRingkas,
  Notifikasi,
  ResponsData,
  ResponsHalaman,
} from '~/types/api'

definePageMeta({ layout: 'anggota', middleware: ['auth', 'bukan-admin'] })
useHead({ title: 'Dasbor' })

const api = useApi()
const { user } = useAuth()
const { materiSaya } = useMateri()

const ringkasan = ref<DasborAnggota | null>(null)
const memuat = ref(true)

const materiTerbaru = ref<MateriRingkas[]>([])
const memuatMateri = ref(true)

const notifikasi = ref<Notifikasi[]>([])
const memuatNotifikasi = ref(true)

const kartuMateri = computed(() => [
  { kunci: 'draf', label: 'Belum tayang', nilai: ringkasan.value?.materi.draf ?? 0 },
  { kunci: 'diajukan', label: 'Lagi dicek admin', nilai: ringkasan.value?.materi.diajukan ?? 0 },
  { kunci: 'ditolak', label: 'Belum disetujui', nilai: ringkasan.value?.materi.ditolak ?? 0 },
  { kunci: 'terbit', label: 'Tayang untuk umum', nilai: ringkasan.value?.materi.terbit ?? 0 },
])

// Keterangan materi
function metaMateri(item: MateriRingkas): string {
  const komentar = `${item.jumlah_komentar ?? 0} komentar`

  if (item.status === 'terbit') return `Tayang ${formatTanggal(item.diterbitkan_pada)} · ${komentar}`
  if (item.status === 'draf') return `Diperbarui ${formatWaktuRelatif(item.diperbarui_pada)} · ${komentar}`

  const kata = item.status === 'diajukan' ? 'Diajukan' : 'Belum disetujui'

  return `${kata} ${formatTanggal(item.diperbarui_pada)} · ${komentar}`
}

function ikon(item: Notifikasi) {
  if (item.data.tipe === 'komentar_baru') return MessageCircle
  if (item.data.tipe === 'materi_diajukan') return FileText
  if (item.data.tipe === 'penarikan_diajukan') return Undo2
  if (item.data.keputusan === 'ditolak') return X

  return CircleCheck
}

async function muatRingkasan() {
  memuat.value = true

  try {
    const respons = await api.get<ResponsData<DasborAnggota>>('/dasbor')
    ringkasan.value = respons.data
  } catch {
    ringkasan.value = null
  } finally {
    memuat.value = false
  }
}

async function muatMateri() {
  memuatMateri.value = true

  try {
    const respons = await materiSaya({ page: 1 })
    materiTerbaru.value = respons.data.slice(0, 3)
  } catch {
    materiTerbaru.value = []
  } finally {
    memuatMateri.value = false
  }
}

async function muatNotifikasi() {
  memuatNotifikasi.value = true

  try {
    const respons = await api.get<ResponsHalaman<Notifikasi>>('/notifikasi', { query: { page: 1 } })
    notifikasi.value = respons.data.slice(0, 3)
  } catch {
    notifikasi.value = []
  } finally {
    memuatNotifikasi.value = false
  }
}

onMounted(() => {
  muatRingkasan()
  muatMateri()
  muatNotifikasi()
})
</script>
