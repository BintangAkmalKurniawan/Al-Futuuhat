<template>
  <!-- Daftar publikasi + sidebar (DESIGN.md §6.2). -->
  <div class="grid grid-cols-[minmax(0,1fr)] gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
    <div>
      <header class="pb-8">
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">Artikel</h1>
        <p class="mt-3 font-serif text-dek text-teks-lembut">Setiap bacaan membuka wawasan baru. Luangkan waktu sejenak, temukan ilmu yang bermanfaat, dan jadikan membaca sebagai kebiasaan.</p>
      </header>

      <!-- Ponsel: kotak cari di atas daftar. Di layar lebar kotak ini ada di sidebar. -->
      <div class="mb-6 lg:hidden">
        <SearchPill v-model="kataKunci" placeholder="Cari artikel" @cari="kataKunciDicari = $event" />
      </div>

      <AppAlert v-if="galat" variant="bahaya" title="Tulisan belum bisa dimuat" class="mb-6">
        {{ galat }}
      </AppAlert>

      <!-- Ponsel: penyaring kategori sebagai satu baris pil yang dapat digulir
           (KF-19, KP-19.4). Di layar lebar penyaring ini ada di sidebar. -->
      <div v-if="daftarKategori.length" class="mb-8 lg:hidden">
        <FilterKategori v-model="kategori" :daftar="daftarKategori" gulir />
      </div>

      <!-- Kerangka memuat: bentuknya sama dengan kartu artikel. -->
      <div v-if="memuat" class="grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="n in 6" :key="n" class="animate-pulse overflow-hidden rounded-lg bg-permukaan ring-1 ring-garis">
          <div class="aspect-[16/10] w-full bg-angkat" />
          <div class="space-y-3 p-5">
            <div class="h-3 w-28 rounded-full bg-angkat" />
            <div class="h-5 w-3/4 rounded-full bg-angkat" />
            <div class="h-3 w-full rounded-full bg-angkat" />
          </div>
        </div>
      </div>

      <AppEmptyState v-else-if="daftarMateri.length === 0" title="Tulisannya tidak ketemu." description="Coba kata kunci atau kategori lain ya.">
        <template #action>
          <AppButton v-if="adaFilter" variant="sekunder" size="sm" @click="bersihkanFilter"> Hapus filter </AppButton>
        </template>
      </AppEmptyState>

      <template v-else>
        <!-- Daftar artikel berupa grid kartu (DESIGN.md §6.2). -->
        <div class="grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
          <KartuArtikel v-for="item in daftarMateri" :key="item.id" :materi="item" />
        </div>

        <div class="pt-8">
          <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
        </div>
      </template>
    </div>

    <!-- Sidebar: kotak cari, kategori, tentang publikasi, ajakan mendaftar. -->
    <aside class="space-y-8 lg:sticky lg:top-24 lg:self-start">
      <div class="hidden lg:block">
        <SearchPill v-model="kataKunci" placeholder="Cari artikel" @cari="kataKunciDicari = $event" />
      </div>

      <!-- Penyaring kategori versi sidebar; di ponsel sudah ada versi gulir di atas daftar. -->
      <section v-if="daftarKategori.length" class="hidden border-t border-garis pt-6 lg:block">
        <h2 class="text-sm font-medium text-teks-kuat">Kategori</h2>
        <FilterKategori v-model="kategori" :daftar="daftarKategori" class="mt-3" />
      </section>

      <section v-if="!isLoggedIn" class="border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Ingin ikut menulis?</h2>
        <p class="mt-2 text-sm leading-relaxed text-teks-lembut">Jadi anggota supaya kamu bisa menulis dan ikut berdiskusi.</p>
        <NuxtLink to="/daftar" class="mt-3 inline-block text-sm text-aksen hover:underline"> Daftar sebagai anggota </NuxtLink>
      </section>
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { Kategori, MateriPublikRingkas, MetaHalaman } from "~/types/api";

definePageMeta({ layout: "publik" });
useHead({ title: "Artikel" });

const route = useRoute();
const router = useRouter();
const { publikArsip } = useMateri();
const { daftar: daftarKategoriApi } = useKategori();
const { isLoggedIn } = useAuth();

// Kata kunci isian pencarian; disalin ke kataKunciDicari saat pencarian dijalankan.
const kataKunci = ref(typeof route.query.q === "string" ? route.query.q : "");
const kataKunciDicari = ref(kataKunci.value);

// Kategori terpilih (slug); kosong berarti semua kategori (KF-19, KP-19.4).
const kategori = ref(typeof route.query.kategori === "string" ? route.query.kategori : "");
const daftarKategori = ref<Kategori[]>([]);

const halaman = ref(1);
const daftarMateri = ref<MateriPublikRingkas[]>([]);
const meta = ref<MetaHalaman | null>(null);
const memuat = ref(true);
const galat = ref("");

const adaFilter = computed(() => Boolean(kataKunciDicari.value || kategori.value));

let permintaanTerakhir = 0;

async function muat() {
  const nomor = ++permintaanTerakhir;
  memuat.value = true;
  galat.value = "";

  try {
    const respons = await publikArsip({
      q: kataKunciDicari.value || undefined,
      kategori: kategori.value || undefined,
      page: halaman.value,
    });

    if (nomor !== permintaanTerakhir) return;

    daftarMateri.value = respons.data;
    meta.value = respons.meta;
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) {
      galat.value = pesanGalat(galatServer, "Tulisan belum bisa dimuat.");
    }
  } finally {
    if (nomor === permintaanTerakhir) memuat.value = false;
  }
}

/** Daftar kategori untuk penyaring. Bila gagal, daftar artikel tetap tampil. */
async function muatKategori() {
  try {
    daftarKategori.value = (await daftarKategoriApi()).data;
  } catch {
    daftarKategori.value = [];
  }
}

/** Simpan filter di URL agar bisa dibagikan dan tetap ada saat halaman dimuat ulang. */
function sinkronUrl() {
  router.replace({
    query: {
      ...(kataKunciDicari.value ? { q: kataKunciDicari.value } : {}),
      ...(kategori.value ? { kategori: kategori.value } : {}),
    },
  });
}

/** Kembali ke halaman satu lalu muat ulang daftar. */
function mulaiUlang() {
  if (halaman.value !== 1) {
    halaman.value = 1;
  } else {
    muat();
  }
}

function bersihkanFilter() {
  kataKunci.value = "";
  kataKunciDicari.value = "";
  kategori.value = "";
}

// Satu pengawas untuk seluruh penyaring: berapa pun yang berubah, URL disamakan
// lalu daftar dimuat ulang sekali.
watch([kataKunciDicari, kategori], () => {
  sinkronUrl();
  mulaiUlang();
});

// Pil kategori pada kartu dan halaman baca menuju /publikasi?kategori=slug.
// Vue Router tidak memasang ulang halaman ini, jadi pilihan disamakan dengan URL.
watch(
  () => route.query.kategori,
  (nilai) => {
    const slug = typeof nilai === "string" ? nilai : "";
    if (slug !== kategori.value) kategori.value = slug;
  },
);

watch(halaman, muat);
onMounted(() => {
  muat();
  muatKategori();
});
</script>
