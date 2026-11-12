<template>
  <div>
    <!-- Hero -->
    <section class="relative overflow-hidden pt-6 pb-16 lg:pt-10 lg:pb-24">
      <!-- Ornamen lengkung -->
      <svg class="pointer-events-none absolute -top-10 right-0 hidden h-[320px] w-[320px] lg:block" viewBox="0 0 200 200" fill="none" aria-hidden="true">
        <circle cx="160" cy="40" r="92" stroke="var(--color-garis-kuat)" stroke-width="1" />
        <path d="M60 200a100 100 0 0 1 100-100" stroke="var(--color-aksen)" stroke-opacity="0.6" />
      </svg>

      <div class="relative max-w-[700px]">
        <h1 class="font-serif text-[44px] leading-[1.05] font-semibold tracking-[-0.03em] text-teks-kuat sm:text-[64px] lg:text-tampilan">Tumbuh bersama,<br />belajar bersama.</h1>

        <!-- GANTI -->
        <p class="mt-6 max-w-[560px] font-serif text-dek text-teks-lembut">Wadah pembinaan, pembelajaran, dan pengabdian bagi anggota. Melalui divisi-divisi yang ada, anggota mengembangkan kemampuan dan berkontribusi bagi masyarakat.</p>

        <div class="mt-8 flex flex-wrap items-center gap-4">
          <NuxtLink to="/publikasi" class="btn btn-primer btn-lg">Baca artikel</NuxtLink>

          <NuxtLink v-if="!isLoggedIn" to="/daftar" class="text-sm text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"> Daftar sebagai anggota </NuxtLink>
          <NuxtLink v-else :to="isAdmin ? '/admin' : '/dasbor'" class="text-sm text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"> atau buka dasbor </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Artikel terbaru dan program -->
    <section v-if="artikelTerbaru.length" class="border-t border-garis py-14">
      <PanelJudul judul="Artikel terbaru" tautan="Lihat semua" ke="/publikasi" />
      <div class="mt-6 grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
        <KartuArtikel v-for="item in artikelTerbaru" :key="item.id" :materi="item" />
      </div>
    </section>

    <section v-if="programKami.length" class="border-t border-garis py-14">
      <PanelJudul judul="Program kami" tautan="Lihat semua" ke="/program" />
      <div class="mt-6 grid gap-8 sm:grid-cols-2 xl:grid-cols-3">
        <KartuProgram v-for="item in programKami" :key="item.id" :program="item" />
      </div>
    </section>

    <!-- Cerita Al-Futuuhat -->
    <section id="cerita" class="border-t border-garis py-14">
      <h2 class="text-[15px] text-teks-lembut">Cerita Al-Futuuhat</h2>

      <div v-if="memuatBeranda" class="mt-6 max-w-baca animate-pulse space-y-4">
        <div class="h-3 w-full rounded-full bg-permukaan" />
        <div class="h-3 w-5/6 rounded-full bg-permukaan" />
        <div class="h-3 w-4/6 rounded-full bg-permukaan" />
      </div>

      <!-- Cerita panjang dipotong -->
      <CeritaAlFutuuhat v-else class="mt-6" :judul="ceritaJudul" :isi="ceritaIsi" ringkas />
    </section>

    <!-- Struktur organisasi -->
    <section id="struktur" class="border-t border-garis py-11">
      <BaganStruktur varian="ringkas" />
    </section>

    <!-- Cara bergabung -->
    <section id="bergabung" class="border-t border-garis py-14">
      <h2 class="text-[15px] text-teks-lembut">Cara bergabung</h2>

      <ol class="mt-6 divide-y divide-garis border-y border-garis">
        <li v-for="(langkah, urutan) in langkahGabung" :key="langkah.judul" class="flex gap-6 py-6">
          <span class="font-serif text-kartu text-teks-redup" aria-hidden="true">
            {{ urutan + 1 }}
          </span>
          <div>
            <p class="text-sm text-teks-kuat">{{ langkah.judul }}</p>
            <p class="mt-1 max-w-baca text-sm text-teks-lembut">{{ langkah.isi }}</p>
          </div>
        </li>
      </ol>

      <NuxtLink v-if="!isLoggedIn" to="/daftar" class="btn btn-primer mt-8"> Daftar sebagai anggota </NuxtLink>
    </section>
  </div>
</template>

<script setup lang="ts">
import type { MateriPublikRingkas, PengaturanBeranda, ProgramRingkas } from "~/types/api";

definePageMeta({ layout: "publik" });
useHead({ title: "Beranda" });

const { isLoggedIn, isAdmin } = useAuth();
const { publik: berandaPublik } = useBeranda();
const { publikArsip } = useMateri();
const { daftarPublik: daftarProgram } = useProgram();

const artikelTerbaru = ref<MateriPublikRingkas[]>([]);
const programKami = ref<ProgramRingkas[]>([]);

const pengaturan = ref<PengaturanBeranda | null>(null);
const memuatBeranda = ref(true);

const ceritaJudul = computed(() => pengaturan.value?.cerita_judul ?? "");
const ceritaIsi = computed(() => rapikanSpasiHtml(pengaturan.value?.cerita_isi ?? ""));

onMounted(async () => {
  try {
    const respons = await berandaPublik();
    pengaturan.value = respons.data;
  } finally {
    memuatBeranda.value = false;
  }
});

onMounted(() => {
  publikArsip({ page: 1 })
    .then((respons) => (artikelTerbaru.value = respons.data.slice(0, 3)))
    .catch(() => {});
  daftarProgram()
    .then((respons) => (programKami.value = respons.data.slice(0, 3)))
    .catch(() => {});
});

const langkahGabung = [
  {
    judul: "Isi formulir",
    isi: "Lengkapi data dirimu dan ceritakan alasanmu bergabung.",
  },
  {
    judul: "Tunggu dicek pengurus",
    isi: "Pengurus akan mengecek pendaftaranmu. Kamu bisa lihat hasilnya saat mencoba masuk.",
  },
  {
    judul: "Mulai belajar",
    isi: "Setelah disetujui, kamu bisa membaca materi, menulis, dan ikut berdiskusi.",
  },
];
</script>
