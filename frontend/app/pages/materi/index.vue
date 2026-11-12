<template>
  <!-- Arsip materi anggota -->
  <div class="grid grid-cols-[minmax(0,1fr)] gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
    <div>
      <header class="pb-6">
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">Arsip materi</h1>
      </header>

      <!-- Ponsel -->
      <div class="mb-6 lg:hidden">
        <SearchPill v-model="kataKunci" placeholder="Cari materi atau penulis" realtime @cari="kataKunciDicari = $event" />
      </div>

      <AppTabs v-model="tab" :tabs="daftarTab" class="mb-2" />

      <!-- Ponsel -->
      <div v-if="daftarKategori.length" class="mt-4 lg:hidden">
        <FilterKategori v-model="kategori" :daftar="daftarKategori" gulir />
      </div>

      <AppAlert v-if="galat" variant="bahaya" title="Materi belum bisa dimuat" class="mt-6">
        {{ galat }}
      </AppAlert>

      <div v-if="memuat" class="space-y-7 pt-7">
        <div v-for="n in 3" :key="n" class="animate-pulse space-y-3">
          <div class="h-3 w-40 rounded-full bg-permukaan" />
          <div class="h-5 w-3/4 rounded-full bg-permukaan" />
          <div class="h-3 w-full rounded-full bg-permukaan" />
        </div>
      </div>

      <AppEmptyState v-else-if="daftarMateri.length === 0" title="Materinya tidak ketemu." description="Coba kata kunci, status, atau kategori lain ya.">
        <template #action>
          <AppButton v-if="adaFilter" variant="sekunder" size="sm" @click="bersihkanFilter"> Hapus filter </AppButton>
        </template>
      </AppEmptyState>

      <template v-else>
        <div class="border-t border-garis">
          <StoryRow v-for="item in daftarMateri" :key="item.id" :materi="item" tampil-status tampil-kategori :jumlah-komentar="item.jumlah_komentar ?? null" />
        </div>

        <div class="pt-8">
          <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
        </div>
      </template>
    </div>

    <!-- Sidebar -->
    <aside class="space-y-8 lg:sticky lg:top-24 lg:self-start">
      <div class="hidden lg:block">
        <SearchPill v-model="kataKunci" placeholder="Cari materi atau penulis" realtime @cari="kataKunciDicari = $event" />
      </div>

      <!-- Penyaring kategori versi sidebar -->
      <section v-if="daftarKategori.length" class="hidden border-t border-garis pt-6 lg:block">
        <h2 class="text-sm font-medium text-teks-kuat">Kategori</h2>
        <FilterKategori v-model="kategori" :daftar="daftarKategori" class="mt-3" />
      </section>

      <section class="border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Arti status</h2>
        <ul class="mt-3 space-y-3">
          <li v-for="baris in legenda" :key="baris.label" class="flex items-start gap-3">
            <StatusChip :status="baris.status" class="mt-0.5" />
            <span class="text-[13px] text-teks-lembut">{{ baris.arti }}</span>
          </li>
        </ul>
      </section>
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { Kategori, MateriRingkas, MetaHalaman, StatusMateri } from "~/types/api";

definePageMeta({ layout: "anggota", middleware: ["auth"] });
useHead({ title: "Arsip Materi" });

const route = useRoute();
const router = useRouter();
const { arsip } = useMateri();
const { daftar: daftarKategoriApi } = useKategori();

const daftarTab = [
  { kunci: "semua", label: "Semua" },
  { kunci: "draf", label: "Belum tayang" },
  { kunci: "diajukan", label: "Lagi dicek admin" },
  { kunci: "ditolak", label: "Belum disetujui" },
  { kunci: "terbit", label: "Tayang untuk umum" },
];

const legenda: { status: StatusMateri; label: string; arti: string }[] = [
  { status: "draf", label: "Belum tayang", arti: "Belum tayang — hanya bisa dibaca anggota" },
  { status: "diajukan", label: "Lagi dicek admin", arti: "Lagi dicek admin — sebentar lagi diputuskan" },
  { status: "ditolak", label: "Belum disetujui", arti: "Belum disetujui — tetap bisa dibaca anggota" },
  { status: "terbit", label: "Tayang untuk umum", arti: "Tayang untuk umum — bisa dibaca siapa saja" },
];

const statusUrl = typeof route.query.status === "string" ? route.query.status : "";
const tab = ref(daftarTab.some((item) => item.kunci === statusUrl) ? statusUrl : "semua");

const kataKunci = ref(typeof route.query.q === "string" ? route.query.q : "");
const kataKunciDicari = ref(kataKunci.value);

const kategori = ref(typeof route.query.kategori === "string" ? route.query.kategori : "");
const daftarKategori = ref<Kategori[]>([]);

const halaman = ref(1);
const daftarMateri = ref<MateriRingkas[]>([]);
const meta = ref<MetaHalaman | null>(null);
const memuat = ref(true);
const galat = ref("");

const adaFilter = computed(
  () => Boolean(kataKunciDicari.value || tab.value !== "semua" || kategori.value),
);

let permintaanTerakhir = 0;

async function muat() {
  const nomor = ++permintaanTerakhir;
  memuat.value = true;
  galat.value = "";

  try {
    const respons = await arsip({
      q: kataKunciDicari.value || undefined,
      status: tab.value === "semua" ? undefined : tab.value,
      kategori: kategori.value || undefined,
      page: halaman.value,
    });

    if (nomor !== permintaanTerakhir) return;

    daftarMateri.value = respons.data;
    meta.value = respons.meta;
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) {
      galat.value = pesanGalat(galatServer, "Materi belum bisa dimuat.");
    }
  } finally {
    if (nomor === permintaanTerakhir) memuat.value = false;
  }
}

// Daftar kategori untuk penyaring
async function muatKategori() {
  try {
    daftarKategori.value = (await daftarKategoriApi()).data;
  } catch {
    daftarKategori.value = [];
  }
}

// Sinkronkan URL
function sinkronUrl() {
  router.replace({
    query: {
      ...(kataKunciDicari.value ? { q: kataKunciDicari.value } : {}),
      ...(tab.value !== "semua" ? { status: tab.value } : {}),
      ...(kategori.value ? { kategori: kategori.value } : {}),
    },
  });
}

// Mulai ulang
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
  tab.value = "semua";
  kategori.value = "";
}

watch([kataKunciDicari, tab, kategori], () => {
  sinkronUrl();
  mulaiUlang();
});

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
