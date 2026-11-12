<template>
  <!-- Dasbor admin -->
  <div>
    <header class="pb-1">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Dasbor</h1>
      <p class="mt-3 text-sm text-teks-lembut">Ringkasan anggota dan materi.</p>
    </header>

    <div v-if="memuat" class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-[86px] animate-pulse rounded-lg bg-permukaan" />
    </div>

    <div v-else class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        v-for="kartu in kartu"
        :key="kartu.label"
        :label="kartu.label"
        :nilai="kartu.nilai"
        :keterangan="kartu.keterangan"
        :ke="kartu.ke"
      />
    </div>

    <div class="mt-9 grid gap-10 lg:grid-cols-2">
      <!-- Panel kiri -->
      <section>
        <PanelJudul judul="Pendaftar terbaru" tautan="Lihat semua" ke="/admin/pendaftaran" />

        <div v-if="memuatPendaftar" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 3" :key="n" class="h-11 rounded-full bg-permukaan" />
        </div>

        <p v-else-if="pendaftar.length === 0" class="py-5 text-sm text-teks-lembut">
          Tidak ada pendaftar menunggu.
        </p>

        <template v-else>
          <div
            v-for="item in pendaftar"
            :key="item.id"
            class="flex items-center gap-3.5 border-b border-garis py-4"
          >
            <AvatarInisial :nama="item.nama" :id="item.id" />

            <div class="min-w-0 flex-1">
              <p class="text-[15px] text-teks-kuat">{{ item.nama }}</p>
              <p class="mt-0.5 text-[13px] text-teks-redup">
                Mendaftar {{ formatWaktuRelatif(item.terdaftar_pada) }}
              </p>
            </div>

            <AppButton
              variant="sekunder"
              size="sm"
              @click="navigateTo(`/admin/pendaftaran/${item.id}`)"
            >
              Periksa
            </AppButton>
          </div>
        </template>
      </section>

      <!-- Panel kanan -->
      <section>
        <PanelJudul
          judul="Materi menunggu pemeriksaan"
          tautan="Lihat semua"
          ke="/admin/verifikasi-materi"
        />

        <div v-if="memuatAntrean" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 2" :key="n" class="h-11 rounded-full bg-permukaan" />
        </div>

        <p v-else-if="antrean.length === 0" class="py-5 text-sm text-teks-lembut">
          Tidak ada materi yang menunggu pemeriksaan.
        </p>

        <template v-else>
          <div
            v-for="item in antrean"
            :key="item.id"
            class="flex items-center gap-3.5 border-b border-garis py-4"
          >
            <div class="min-w-0 flex-1">
              <p class="font-serif text-[17px] leading-[1.3] font-semibold text-teks-kuat">
                {{ item.materi?.judul ?? 'Materi' }}
              </p>
              <p class="mt-1 text-[13px] text-teks-redup">{{ metaAntrean(item) }}</p>
            </div>

            <AppButton
              variant="sekunder"
              size="sm"
              @click="navigateTo(`/admin/verifikasi-materi/${item.id}`)"
            >
              Tinjau
            </AppButton>
          </div>
        </template>
      </section>
    </div>

    <!-- Statistik -->
    <div class="mt-12 grid gap-10 lg:grid-cols-2">
      <section>
        <PanelJudul judul="Materi paling banyak dibaca" />
        <div v-if="memuatStatistik" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 3" :key="n" class="h-9 rounded-full bg-permukaan" />
        </div>
        <p v-else-if="!statistik?.materi_terpopuler.length" class="py-5 text-sm text-teks-lembut">
          Belum ada materi yang dibaca.
        </p>
        <ol v-else class="mt-3 divide-y divide-garis border-t border-garis">
          <li v-for="(item, i) in statistik.materi_terpopuler" :key="item.id" class="flex items-center gap-4 py-3">
            <span class="w-5 text-[13px] text-teks-redup">{{ i + 1 }}</span>
            <div class="min-w-0 flex-1">
              <NuxtLink :to="`/materi/${item.slug}`" class="block truncate font-serif text-[15px] font-semibold text-teks-kuat hover:text-aksen">
                {{ item.judul }}
              </NuxtLink>
              <p class="text-[13px] text-teks-redup">{{ item.penulis ?? 'Anggota' }}</p>
            </div>
            <span class="shrink-0 text-[13px] text-teks-lembut">{{ item.jumlah_dibaca }} kali dibaca</span>
          </li>
        </ol>
      </section>

      <section>
        <PanelJudul judul="Anggota paling aktif (30 hari)" />
        <div v-if="memuatStatistik" class="animate-pulse space-y-4 pt-4">
          <div v-for="n in 3" :key="n" class="h-9 rounded-full bg-permukaan" />
        </div>
        <p v-else-if="!statistik?.anggota_teraktif.length" class="py-5 text-sm text-teks-lembut">
          Belum ada aktivitas anggota dalam 30 hari terakhir.
        </p>
        <ol v-else class="mt-3 divide-y divide-garis border-t border-garis">
          <li v-for="(item, i) in statistik.anggota_teraktif" :key="item.id" class="flex items-center gap-4 py-3">
            <span class="w-5 text-[13px] text-teks-redup">{{ i + 1 }}</span>
            <AvatarInisial :nama="item.nama" :id="item.id" ukuran="sedang" />
            <p class="min-w-0 flex-1 truncate text-sm text-teks-kuat">{{ item.nama }}</p>
            <span class="shrink-0 text-[13px] text-teks-lembut">
              {{ item.jumlah_materi }} materi · {{ item.jumlah_komentar }} komentar
            </span>
          </li>
        </ol>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DasborAdmin, Revisi, ResponsData, StatistikAdmin, User } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Dasbor Admin' })

const api = useApi()
const { daftarPendaftar } = usePendaftaran()
const { antrean: ambilAntrean } = useVerifikasi()

const ringkasan = ref<DasborAdmin | null>(null)
const memuat = ref(true)

const pendaftar = ref<User[]>([])
const memuatPendaftar = ref(true)

const antrean = ref<Revisi[]>([])
const memuatAntrean = ref(true)

const kartu = computed(() => [
  {
    label: 'Pendaftar menunggu',
    nilai: ringkasan.value?.pendaftar_menunggu ?? 0,
    ke: '/admin/pendaftaran',
  },
  {
    label: 'Menunggu pemeriksaan',
    nilai: (ringkasan.value?.revisi_diajukan ?? 0) + (ringkasan.value?.penarikan_diajukan ?? 0),
    keterangan: `${ringkasan.value?.revisi_diajukan ?? 0} minta tayang · ${ringkasan.value?.penarikan_diajukan ?? 0} minta diturunkan`,
    ke: '/admin/verifikasi-materi',
  },
  {
    label: 'Anggota aktif',
    nilai: ringkasan.value?.anggota_aktif ?? 0,
    ke: '/admin/anggota',
  },
  {
    label: 'Tayang untuk umum',
    nilai: ringkasan.value?.materi_terbit ?? 0,
    ke: '/materi?status=terbit',
  },
])

// Keterangan antrean
function metaAntrean(item: Revisi): string {
  const penulis = item.materi?.penulis?.nama ?? 'Anggota'
  const revisi = item.materi?.status === 'terbit' ? ' · versi baru' : ''

  return `${penulis}${revisi} · diajukan ${formatTanggal(item.dibuat_pada)}`
}

async function muatRingkasan() {
  memuat.value = true

  try {
    const respons = await api.get<ResponsData<DasborAdmin>>('/admin/dasbor')
    ringkasan.value = respons.data
  } catch {
    ringkasan.value = null
  } finally {
    memuat.value = false
  }
}

async function muatPendaftar() {
  memuatPendaftar.value = true

  try {
    const respons = await daftarPendaftar('menunggu', 1)
    pendaftar.value = respons.data.slice(0, 3)
  } catch {
    pendaftar.value = []
  } finally {
    memuatPendaftar.value = false
  }
}

async function muatAntrean() {
  memuatAntrean.value = true

  try {
    const respons = await ambilAntrean(1)
    antrean.value = respons.data.slice(0, 2)
  } catch {
    antrean.value = []
  } finally {
    memuatAntrean.value = false
  }
}

const { statistik: ambilStatistik } = useProfil()
const statistik = ref<StatistikAdmin | null>(null)
const memuatStatistik = ref(true)

async function muatStatistik() {
  try {
    statistik.value = (await ambilStatistik()).data
  } catch {
    statistik.value = null
  } finally {
    memuatStatistik.value = false
  }
}

onMounted(() => {
  muatRingkasan()
  muatPendaftar()
  muatAntrean()
  muatStatistik()
})
</script>
