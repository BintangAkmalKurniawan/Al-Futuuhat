<template>
  <!-- Overflow-x-clip -->
  <div class="flex min-h-screen flex-col overflow-x-clip bg-kanvas text-teks">
    <!-- Header anggota -->
    <header class="sticky top-0 z-30 border-b border-garis bg-kanvas/95 backdrop-blur">
      <div class="mx-auto flex h-[52px] w-full max-w-halaman items-center px-4 sm:px-6 md:h-[57px] md:gap-5">
        <NuxtLink to="/materi" class="shrink-0">
          <img :src="logo" alt="Al-Futuuhat" class="h-7 w-auto md:h-8" />
        </NuxtLink>

        <!-- Navbar tablet/desktop -->
        <nav aria-label="Navigasi anggota" class="ml-auto hidden items-center gap-6 md:flex">
          <NuxtLink v-for="item in menuNavbar" :key="item.to" :to="item.to" class="text-sm transition-colors duration-200" :class="sedangAktif(item) ? 'text-teks-kuat' : 'text-teks-lembut hover:text-teks-kuat'">
            {{ item.label }}
          </NuxtLink>

          <NuxtLink to="/materi-saya/baru" class="inline-flex h-10 items-center gap-2 rounded-full px-2 text-sm text-teks-lembut transition-colors duration-200 hover:bg-permukaan hover:text-teks-kuat" aria-label="Tulis materi baru">
            <PenLine class="h-5 w-5" :stroke-width="1.5" aria-hidden="true" />
            <span>Tulis</span>
          </NuxtLink>

          <NuxtLink
            to="/notifikasi"
            class="relative grid h-10 w-10 place-items-center rounded-full text-teks-lembut transition-colors duration-200 hover:bg-permukaan hover:text-teks-kuat"
            :aria-label="`Notifikasi${jumlahNotifikasi ? `, ${jumlahNotifikasi} belum dibaca` : ''}`"
          >
            <Bell class="h-5 w-5" :stroke-width="1.5" aria-hidden="true" />
            <span v-if="jumlahNotifikasi" class="absolute top-1 right-1 grid h-4 min-w-4 place-items-center rounded-full bg-aksen px-1 text-[10px] font-semibold text-tombol-teks">
              {{ jumlahNotifikasi }}
            </span>
          </NuxtLink>
        </nav>

        <!-- Menu avatar -->
        <div class="relative ml-auto md:ml-0">
          <button
            type="button"
            class="relative block rounded-full"
            :aria-expanded="menuTerbuka"
            aria-haspopup="menu"
            :aria-label="`Menu akun${jumlahNotifikasi ? `, ${jumlahNotifikasi} notifikasi belum dibaca` : ''}`"
            @click="menuTerbuka = !menuTerbuka"
          >
            <AvatarInisial :nama="user?.nama" :id="user?.id" />
            <span v-if="jumlahNotifikasi" class="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full bg-aksen ring-2 ring-kanvas md:hidden" aria-hidden="true" />
          </button>

          <div v-if="menuTerbuka" class="absolute right-0 mt-2 w-60 overflow-hidden rounded-lg border border-garis bg-angkat py-1 shadow-[0_14px_40px_rgba(0,0,0,.5)]" role="menu">
            <!-- Profil singkat -->
            <div class="flex items-center gap-3 border-b border-garis px-4 py-3">
              <AvatarInisial :nama="user?.nama" :id="user?.id" />
              <div class="min-w-0">
                <p class="truncate text-sm text-teks-kuat">{{ user?.nama ?? "Anggota" }}</p>
                <p class="mt-0.5 text-[13px] text-teks-redup">{{ isAdmin ? "Admin" : "Anggota" }}</p>
              </div>
            </div>

            <NuxtLink to="/dasbor" class="hidden items-center gap-3 px-4 py-2 text-sm text-teks transition-colors duration-200 hover:bg-permukaan md:flex" role="menuitem" @click="menuTerbuka = false">
              <LayoutDashboard class="h-4 w-4 text-teks-lembut" :stroke-width="1.5" aria-hidden="true" />
              Dasbor
            </NuxtLink>

            <!-- Profil sendiri -->
            <NuxtLink to="/profil" class="flex items-center gap-3 px-4 py-2 text-sm text-teks transition-colors duration-200 hover:bg-permukaan" role="menuitem" @click="menuTerbuka = false">
              <UserRound class="h-4 w-4 text-teks-lembut" :stroke-width="1.5" aria-hidden="true" />
              Profil
            </NuxtLink>

            <NuxtLink to="/notifikasi" class="flex items-center gap-3 px-4 py-2 text-sm text-teks transition-colors duration-200 hover:bg-permukaan" role="menuitem" @click="menuTerbuka = false">
              <Bell class="h-4 w-4 text-teks-lembut" :stroke-width="1.5" aria-hidden="true" />
              <span class="flex-1">Notifikasi</span>
              <span v-if="jumlahNotifikasi" class="grid h-5 min-w-5 place-items-center rounded-full bg-aksen px-1.5 text-[11px] font-semibold text-tombol-teks">
                {{ jumlahNotifikasi }}
              </span>
            </NuxtLink>

            <NuxtLink v-if="isAdmin" to="/admin" class="flex items-center gap-3 px-4 py-2 text-sm text-teks transition-colors duration-200 hover:bg-permukaan" role="menuitem" @click="menuTerbuka = false">
              <ShieldCheck class="h-4 w-4 text-teks-lembut" :stroke-width="1.5" aria-hidden="true" />
              Panel admin
            </NuxtLink>

            <button
              type="button"
              class="flex w-full items-center gap-3 border-t border-garis px-4 py-2 text-left text-sm text-teks transition-colors duration-200 hover:bg-permukaan disabled:opacity-50"
              role="menuitem"
              :disabled="keluar"
              @click="klikKeluar"
            >
              <LogOut class="h-4 w-4 text-teks-lembut" :stroke-width="1.5" aria-hidden="true" />
              Keluar
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-halaman flex-1 px-4 pt-8 pb-28 sm:px-6 md:pt-10 md:pb-10">
      <slot />
    </main>

    <!-- Toolbar bawah ponsel -->
    <nav aria-label="Navigasi anggota (ponsel)" class="fixed inset-x-3 bottom-3 z-30 mx-auto max-w-md rounded-2xl bg-angkat/95 shadow-[0_14px_40px_rgba(0,0,0,.5)] ring-1 ring-garis backdrop-blur md:hidden">
      <ul class="grid h-16 grid-cols-5 items-center">
        <li v-for="item in menuToolbar" :key="item.to">
          <NuxtLink
            :to="item.to"
            class="flex flex-col items-center gap-1 rounded-lg py-1 text-[11px] transition-colors duration-200"
            :class="!item.utama && sedangAktif(item) ? 'text-aksen' : 'text-teks-lembut hover:text-teks-kuat'"
            :aria-current="sedangAktif(item) ? 'page' : undefined"
          >
            <span v-if="item.utama" class="grid h-9 w-9 place-items-center rounded-full bg-tombol text-tombol-teks" :class="sedangAktif(item) && 'ring-2 ring-aksen ring-offset-2 ring-offset-angkat'">
              <component :is="item.ikon" class="h-5 w-5" :stroke-width="2" aria-hidden="true" />
            </span>
            <component :is="item.ikon" v-else class="h-5 w-5" :stroke-width="1.5" aria-hidden="true" />
            <span :class="item.utama && sedangAktif(item) && 'text-aksen'">{{ item.label }}</span>
          </NuxtLink>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup lang="ts">
import logo from '~/assets/logo.png'
import { Bell, FileText, LayoutDashboard, Library, LogOut, Network, PenLine, Plus, ShieldCheck, UserRound } from "@lucide/vue";
import type { DasborAnggota, ResponsData } from "~/types/api";

const { user, isAdmin, logout } = useAuth();
const api = useApi();
const route = useRoute();

const keluar = ref(false);
const menuTerbuka = ref(false);
const jumlahNotifikasi = ref(0);

const menuNavbar = [
  { label: "Arsip", to: "/materi" },
  { label: "Materi saya", to: "/materi-saya" },
  { label: "Struktur", to: "/struktur" },
];

const menuToolbar = [
  { label: "Dasbor", to: "/dasbor", ikon: LayoutDashboard },
  { label: "Arsip", to: "/materi", ikon: Library },
  { label: "Tulis", to: "/materi-saya/baru", ikon: Plus, utama: true },
  { label: "Materi saya", to: "/materi-saya", ikon: FileText },
  { label: "Struktur", to: "/struktur", ikon: Network },
];

// Menu aktif
function sedangAktif(item: { to: string; utama?: boolean }) {
  if (item.utama) return route.path === item.to;
  if (item.to === "/materi-saya" && route.path === "/materi-saya/baru") return false;
  return route.path === item.to || route.path.startsWith(`${item.to}/`);
}

async function klikKeluar() {
  keluar.value = true;

  try {
    await logout();
  } finally {
    keluar.value = false;
  }
}

// Muat jumlah notifikasi
async function muatNotifikasi() {
  try {
    const respons = await api.get<ResponsData<DasborAnggota>>("/dasbor");
    jumlahNotifikasi.value = respons.data.notifikasi_belum_dibaca ?? 0;
  } catch {
    jumlahNotifikasi.value = 0;
  }
}

onMounted(() => {
  if (user.value) muatNotifikasi();
  else
    useAuth()
      .fetchMe()
      .then(muatNotifikasi)
      .catch(() => {});
});

// Tutup menu avatar
function tutupMenu(event: MouseEvent) {
  if (!menuTerbuka.value) return;
  if (!(event.target as HTMLElement).closest('[aria-haspopup="menu"]')) menuTerbuka.value = false;
}

watch(
  () => route.fullPath,
  () => (menuTerbuka.value = false),
);

onMounted(() => document.addEventListener("click", tutupMenu));
onBeforeUnmount(() => document.removeEventListener("click", tutupMenu));
</script>
