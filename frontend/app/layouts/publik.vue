<template>
  <div class="flex min-h-screen flex-col bg-kanvas text-teks">
    <!-- Header 57 px: wordmark di kiri, menu di kanan (DESIGN.md §4). -->
    <header class="sticky top-0 z-30 border-b border-garis bg-kanvas/95 backdrop-blur">
      <div class="mx-auto flex h-[57px] w-full max-w-halaman items-center gap-6 px-6">
        <NuxtLink to="/" class="shrink-0">
          <img :src="logo" alt="Al-Futuuhat" class="h-7 w-auto md:h-8" />
        </NuxtLink>

        <!-- Menu layar besar -->
        <nav aria-label="Navigasi publik" class="ml-auto hidden items-center gap-6 md:flex">
          <NuxtLink
            v-for="item in menu"
            :key="item.to"
            :to="item.to"
            class="text-sm transition-colors duration-200"
            :class="sedangAktif(item.to) ? 'text-teks-kuat' : 'text-teks-lembut hover:text-teks-kuat'"
          >
            {{ item.label }}
          </NuxtLink>

          <slot name="navigasi" />

          <NuxtLink
            v-if="isLoggedIn"
            :to="isAdmin ? '/admin' : '/dasbor'"
            class="text-sm text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
          >
            Dasbor
          </NuxtLink>
          <template v-else>
            <NuxtLink
              to="/masuk"
              class="text-sm text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
            >
              Masuk
            </NuxtLink>
            <NuxtLink to="/daftar" class="btn btn-primer btn-sm">Daftar</NuxtLink>
          </template>
        </nav>

        <!-- Menu laci untuk ponsel -->
        <button
          type="button"
          class="ml-auto grid h-10 w-10 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:text-teks-kuat md:hidden"
          :aria-expanded="laciTerbuka"
          aria-controls="menu-publik"
          aria-label="Buka menu"
          @click="laciTerbuka = !laciTerbuka"
        >
          <component :is="laciTerbuka ? X : Menu" class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <nav
        v-show="laciTerbuka"
        id="menu-publik"
        aria-label="Navigasi publik ponsel"
        class="border-t border-garis px-6 py-3 md:hidden"
      >
        <NuxtLink
          v-for="item in menu"
          :key="item.to"
          :to="item.to"
          class="block py-2 text-sm text-teks-lembut"
          @click="laciTerbuka = false"
        >
          {{ item.label }}
        </NuxtLink>

        <NuxtLink
          v-if="isLoggedIn"
          :to="isAdmin ? '/admin' : '/dasbor'"
          class="block py-2 text-sm text-teks-lembut"
          @click="laciTerbuka = false"
        >
          Dasbor
        </NuxtLink>
        <template v-else>
          <NuxtLink to="/masuk" class="block py-2 text-sm text-teks-lembut" @click="laciTerbuka = false">
            Masuk
          </NuxtLink>
          <NuxtLink to="/daftar" class="btn btn-primer btn-sm mt-3 w-full" @click="laciTerbuka = false">
            Daftar
          </NuxtLink>
        </template>
      </nav>
    </header>

    <main class="mx-auto w-full max-w-halaman flex-1 px-6 py-11">
      <slot />
    </main>

    <footer class="border-t border-garis">
      <!-- Kontak organisasi yang diatur admin (KF-22); kolom kosong tidak ditampilkan. -->
      <div
        v-if="adaKontak"
        class="mx-auto flex w-full max-w-halaman flex-wrap gap-x-6 gap-y-2 px-6 pt-8 text-[13px] text-teks-lembut"
      >
        <span v-if="kontak?.alamat" class="inline-flex items-center gap-2">
          <MapPin class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ kontak.alamat }}
        </span>
        <a
          v-if="kontak?.whatsapp"
          :href="`https://wa.me/${kontak.whatsapp}`"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 transition-colors duration-200 hover:text-teks-kuat"
        >
          <MessageCircle class="h-4 w-4 shrink-0" aria-hidden="true" />
          WhatsApp
        </a>
        <a
          v-if="kontak?.email"
          :href="`mailto:${kontak.email}`"
          class="inline-flex items-center gap-2 transition-colors duration-200 hover:text-teks-kuat"
        >
          <Mail class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ kontak.email }}
        </a>
        <a
          v-if="kontak?.instagram"
          :href="`https://instagram.com/${kontak.instagram}`"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 transition-colors duration-200 hover:text-teks-kuat"
        >
          <AtSign class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ kontak.instagram }}
        </a>
      </div>

      <div class="mx-auto flex w-full max-w-halaman flex-col gap-1 px-6 py-8 text-[13px] text-teks-redup sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ tahun }} Al-Futuuhat. Seluruh hak cipta dilindungi.</p>
        <p>Sistem informasi keanggotaan dan pengelolaan materi.</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
// Logo resmi (tulisan putih, latar transparan) menggantikan wordmark teks.
import logo from '~/assets/logo.png'
import { AtSign, Mail, MapPin, Menu, MessageCircle, X } from '@lucide/vue'
import type { KontakOrganisasi } from '~/types/api'

const { isLoggedIn, isAdmin } = useAuth()
const route = useRoute()

const tahun = new Date().getFullYear()
const laciTerbuka = ref(false)

// Kontak organisasi (KF-22) diambil dari konten beranda; gagal dimuat = disembunyikan.
const kontak = ref<KontakOrganisasi | null>(null)
const adaKontak = computed(() => Boolean(kontak.value && Object.values(kontak.value).some(Boolean)))

onMounted(() => {
  useBeranda()
    .publik()
    .then((respons) => (kontak.value = respons.data.kontak ?? null))
    .catch(() => {})
})

// Menu pengunjung: beranda (/), artikel, program, dan bagian bergabung.
// Struktur kepengurusan tidak lagi punya menu sendiri karena sudah tampil
// di beranda; tempatnya diganti halaman Program (KF-21, KP-21.7).
const menu = [
  { label: 'Beranda', to: '/' },
  { label: 'Tentang kami', to: '/tentang' },
  { label: 'Artikel', to: '/publikasi' },
  { label: 'Program', to: '/program' },
  { label: 'Cara bergabung', to: '/#bergabung' },
]

// Tautan berbagian (#bergabung) hanya aktif saat bagian itu dibuka; sedangkan
// beranda hanya aktif saat tidak sedang membuka bagian mana pun.
function sedangAktif(to: string) {
  const [jalur, bagian] = to.split('#')

  if (bagian) return route.path === jalur && route.hash === `#${bagian}`

  if (jalur === '/') return route.path === '/' && !route.hash

  return route.path === jalur || route.path.startsWith(`${jalur}/`)
}
</script>
