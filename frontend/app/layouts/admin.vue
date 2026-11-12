<template>
  <div class="min-h-screen bg-kanvas text-teks lg:flex">
    <!-- Sidebar 248 px -->
    <aside
      class="border-b border-garis bg-permukaan lg:sticky lg:top-0 lg:h-screen lg:w-62 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-b-0"
    >
      <div class="flex items-center justify-between gap-3 px-6 py-5">
        <NuxtLink to="/admin" class="shrink-0">
          <img :src="logo" alt="Al-Futuuhat" class="h-7 w-auto md:h-8" />
        </NuxtLink>

        <button
          type="button"
          class="grid h-9 w-9 place-items-center rounded-full text-teks-lembut lg:hidden"
          :aria-expanded="laciTerbuka"
          aria-controls="menu-admin"
          aria-label="Buka menu admin"
          @click="laciTerbuka = !laciTerbuka"
        >
          <component :is="laciTerbuka ? X : Menu" class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <nav
        id="menu-admin"
        aria-label="Navigasi admin"
        class="px-3 pb-4"
        :class="laciTerbuka ? 'block' : 'hidden lg:block'"
      >
        <div v-for="grup in menuGrup" :key="grup.judul" class="mb-5">
          <p class="px-3 pb-2 text-xs tracking-wider text-teks-redup uppercase">
            {{ grup.judul }}
          </p>

          <NuxtLink
            v-for="item in grup.item"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200"
            :class="
              sedangAktif(item.to)
                ? 'bg-angkat text-teks-kuat'
                : 'text-teks-lembut hover:bg-angkat/60 hover:text-teks-kuat'
            "
          >
            <component :is="item.ikon" class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="flex-1">{{ item.label }}</span>

            <!-- Lencana jumlah antrean -->
            <span
              v-if="item.jumlah"
              class="grid h-5 min-w-5 place-items-center rounded-full bg-aksen px-1.5 text-[11px] font-semibold text-tombol-teks"
            >
              {{ item.jumlah }}
            </span>
          </NuxtLink>
        </div>

        <slot name="navigasi" />

        <!-- Akun -->
        <div class="mt-5">
          <p class="px-3 pb-2 text-xs tracking-wider text-teks-redup uppercase">Akun</p>

          <NuxtLink
            to="/profil"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200"
            :class="
              sedangAktif('/profil')
                ? 'bg-angkat text-teks-kuat'
                : 'text-teks-lembut hover:bg-angkat/60 hover:text-teks-kuat'
            "
          >
            <UserRound class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="flex-1">Profil</span>
          </NuxtLink>

          <NuxtLink
            to="/notifikasi"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200"
            :class="
              sedangAktif('/notifikasi')
                ? 'bg-angkat text-teks-kuat'
                : 'text-teks-lembut hover:bg-angkat/60 hover:text-teks-kuat'
            "
          >
            <Bell class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="flex-1">Notifikasi</span>
            <span
              v-if="jumlahNotifikasi"
              class="grid h-5 min-w-5 place-items-center rounded-full bg-aksen px-1.5 text-[11px] font-semibold text-tombol-teks"
            >
              {{ jumlahNotifikasi }}
            </span>
          </NuxtLink>

          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-teks-lembut transition-colors duration-200 hover:bg-angkat/60 hover:text-teks-kuat disabled:opacity-50"
            :disabled="keluar"
            @click="klikKeluar"
          >
            <LogOut class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="flex-1">Keluar</span>
          </button>
        </div>
      </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <main class="flex-1 px-6 py-9 lg:px-12">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import logo from '~/assets/logo.png'
import {
  Bell,
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  Image,
  LayoutDashboard,
  LogOut,
  Menu,
  Network,
  Tags,
  UserCheck,
  UserRound,
  Users,
  X,
} from '@lucide/vue'
import type { DasborAdmin, DasborAnggota, ResponsData } from '~/types/api'

const { logout } = useAuth()
const api = useApi()
const route = useRoute()

const keluar = ref(false)
const laciTerbuka = ref(false)
const ringkasan = ref<DasborAdmin | null>(null)
const jumlahNotifikasi = ref(0)

const menuGrup = computed(() => [
  {
    judul: 'Ringkasan',
    item: [{ label: 'Dasbor', to: '/admin', ikon: LayoutDashboard, jumlah: 0 }],
  },
  {
    judul: 'Keanggotaan',
    item: [
      {
        label: 'Pendaftaran',
        to: '/admin/pendaftaran',
        ikon: UserCheck,
        jumlah: ringkasan.value?.pendaftar_menunggu ?? 0,
      },
      { label: 'Data anggota', to: '/admin/anggota', ikon: Users, jumlah: 0 },
      { label: 'Struktur', to: '/admin/struktur', ikon: Network, jumlah: 0 },
    ],
  },
  {
    judul: 'Materi',
    item: [
      {
        label: 'Pemeriksaan materi',
        to: '/admin/verifikasi-materi',
        ikon: ClipboardCheck,
        jumlah: (ringkasan.value?.revisi_diajukan ?? 0) + (ringkasan.value?.penarikan_diajukan ?? 0),
      },
      { label: 'Arsip materi', to: '/materi', ikon: BookOpen, jumlah: 0 },
      { label: 'Kategori', to: '/admin/kategori', ikon: Tags, jumlah: 0 },
    ],
  },
  {
    judul: 'Kegiatan',
    item: [{ label: 'Program', to: '/admin/program', ikon: CalendarDays, jumlah: 0 }],
  },
  {
    judul: 'Tampilan',
    item: [{ label: 'Konten beranda', to: '/admin/beranda', ikon: Image, jumlah: 0 }],
  },
])

function sedangAktif(to: string) {
  if (to === '/admin') return route.path === '/admin'

  return route.path === to || route.path.startsWith(`${to}/`)
}

async function klikKeluar() {
  keluar.value = true

  try {
    await logout()
  } finally {
    keluar.value = false
  }
}

// Muat ringkasan antrean
async function muatRingkasan() {
  try {
    const respons = await api.get<ResponsData<DasborAdmin>>('/admin/dasbor')
    ringkasan.value = respons.data
  } catch {
    ringkasan.value = null
  }
}

// Muat jumlah notifikasi
async function muatNotifikasi() {
  try {
    const respons = await api.get<ResponsData<DasborAnggota>>('/dasbor')
    jumlahNotifikasi.value = respons.data.notifikasi_belum_dibaca ?? 0
  } catch {
    jumlahNotifikasi.value = 0
  }
}

onMounted(() => {
  muatRingkasan()
  muatNotifikasi()
})

watch(
  () => route.path,
  () => {
    laciTerbuka.value = false
    muatNotifikasi()
  },
)
</script>
