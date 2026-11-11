<template>
  <!-- Daftar materi milik sendiri (DESIGN.md §6.5, layar 11). -->
  <div class="grid grid-cols-[minmax(0,1fr)] gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
    <div>
      <header class="flex flex-wrap items-start justify-between gap-4 pb-6">
        <div>
          <h1 class="font-serif text-halaman font-bold text-teks-kuat">Materi saya</h1>
        </div>

        <AppButton @click="navigateTo('/materi-saya/baru')">
          <PenLine class="h-4 w-4" aria-hidden="true" />
          Tulis materi
        </AppButton>
      </header>

      <!-- Ponsel: kotak cari di atas daftar. Di layar lebar kotak ini ada di sidebar. -->
      <div class="mb-6 lg:hidden">
        <SearchPill v-model="kataKunci" placeholder="Cari materi saya" realtime @cari="cari" />
      </div>

      <AppTabs v-model="tab" :tabs="daftarTab" />

      <AppAlert v-if="galat" variant="bahaya" title="Materi belum bisa dimuat" class="mt-6">
        {{ galat }}
      </AppAlert>

      <!-- Kerangka memuat berbentuk seperti baris tulisan. -->
      <div v-if="memuat" class="space-y-7 pt-7">
        <div v-for="n in 3" :key="n" class="animate-pulse space-y-3">
          <div class="h-3 w-40 rounded-full bg-permukaan" />
          <div class="h-5 w-3/4 rounded-full bg-permukaan" />
          <div class="h-3 w-full rounded-full bg-permukaan" />
        </div>
      </div>

      <AppEmptyState v-else-if="daftarMateri.length === 0" :title="kosong.judul" :description="kosong.deskripsi">
        <template #action>
          <AppButton v-if="kataKunci.trim()" variant="sekunder" size="sm" @click="bersihkanPencarian"> Hapus pencarian </AppButton>
          <AppButton v-else-if="tab === 'semua' || tab === 'draf'" size="sm" @click="navigateTo('/materi-saya/baru')"> Tulis materi </AppButton>
        </template>
      </AppEmptyState>

      <template v-else>
        <div class="border-t border-garis">
          <StoryRow v-for="item in daftarMateri" :key="item.id" :materi="item" :ke="keBaris(item)" :status-chip="statusBaris(item)" :label-chip="labelBaris(item)" :catatan-chip="catatanBaris(item)" tampil-status>
            <template #aksi>
              <!-- Aksi per baris mengikuti status materi/revisi (DESIGN.md §7, Draf 2.3). -->
              <AppButton v-if="aksi(item, 'ubah')" variant="sekunder" size="sm" :loading="memproses === item.id" @click="ubahSekarang(item)"> Ubah </AppButton>

              <NuxtLink v-if="aksi(item, 'lihat')" :to="`/materi/${item.slug}`" class="btn btn-sekunder btn-sm"> Lihat </NuxtLink>

              <AppButton v-if="aksi(item, 'ajukan')" size="sm" @click="mintaAjukan(item)"> Ajukan supaya tayang </AppButton>

              <AppButton v-if="aksi(item, 'batalAjukan')" variant="sekunder" size="sm" :loading="memproses === item.id" @click="batalAjukanSekarang(item)"> Batalkan pengajuan </AppButton>

              <AppButton v-if="aksi(item, 'tarik')" size="sm" @click="mintaTarik(item)"> Turunkan dari halaman umum </AppButton>

              <AppButton v-if="aksi(item, 'batalTarik')" variant="sekunder" size="sm" :loading="memproses === item.id" @click="batalTarikSekarang(item)"> Batal turunkan </AppButton>

              <MenuTitikTiga v-if="bisaHapus(item)" :aria-label="`Aksi lain untuk ${item.judul}`" :item="[{ label: 'Hapus', bahaya: true, aksi: () => mintaHapus(item) }]" />
            </template>
          </StoryRow>
        </div>

        <div class="pt-8">
          <AppPagination v-if="meta" :meta="meta" @ganti-halaman="halaman = $event" />
        </div>
      </template>
    </div>

    <aside class="space-y-8 lg:sticky lg:top-24 lg:self-start">
      <!-- Satu kotak pencarian, sama seperti arsip materi (DESIGN.md §6.5). -->
      <div class="hidden lg:block">
        <SearchPill v-model="kataKunci" placeholder="Cari materi saya" realtime @cari="cari" />
      </div>

      <section class="border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Arti status</h2>
        <ul class="mt-3 space-y-3">
          <li v-for="baris in legenda" :key="baris.status" class="flex items-start gap-3">
            <StatusChip :status="baris.status" class="mt-0.5" />
            <span class="text-[13px] text-teks-lembut">{{ baris.arti }}</span>
          </li>
        </ul>
      </section>

      <section class="border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Cara materi tayang untuk umum</h2>
        <p class="mt-2 text-sm leading-relaxed text-teks-lembut">Materimu langsung bisa dibaca semua anggota. Kalau mau dibaca umum juga, ajukan supaya tayang dan tunggu admin menyetujuinya.</p>
        <p class="mt-2 text-sm leading-relaxed text-teks-lembut">Materi yang sudah tayang tetap bisa kamu ubah. Pengunjung tetap melihat versi lama sampai kamu menurunkan materinya dan mengajukan versi baru.</p>
      </section>
    </aside>

    <!-- Konfirmasi "Ajukan publikasi", dengan perbandingan bila pernah terbit (KP-10.7). -->
    <DialogAjukanPublikasi
      v-if="akanDiajukan"
      v-model:open="modalAjukan"
      :materi-id="akanDiajukan.id"
      :id-revisi="akanDiajukan.revisi_terakhir?.id ?? null"
      :nomor-revisi="akanDiajukan.revisi_terakhir?.nomor_versi ?? null"
      :id-versi-publik="akanDiajukan.versi_publik_terakhir?.id ?? null"
      @diajukan="setelahAksi($event.message)"
    />

    <!-- Ajukan penarikan dari publik (KP-20.1). -->
    <ModalTarikMateri
      v-if="akanDitarik"
      v-model:open="modalTarik"
      :materi-id="akanDitarik.id"
      @diajukan="setelahAksi($event.message)"
    />

    <!-- Konfirmasi hapus -->
    <AppModal v-model:open="modalHapus" title="Hapus materi ini?" deskripsi="Setelah dihapus, materinya hilang untuk selamanya.">
      <AppAlert v-if="galatHapus" variant="bahaya" title="Belum berhasil dihapus">
        {{ galatHapus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ akanDihapus?.judul }}</p>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatHapus" @click="modalHapus = false"> Batal </AppButton>
        <AppButton variant="bahaya" :loading="memuatHapus" @click="hapusSekarang"> Ya, hapus </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { PenLine } from "@lucide/vue";
import type { DasborAnggota, MateriRingkas, MetaHalaman, StatusMateri } from "~/types/api";

// KP-08.6: admin hanya mengelola, tidak menulis materi → dialihkan ke /admin.
definePageMeta({ layout: "anggota", middleware: ["auth", "bukan-admin"] });
useHead({ title: "Materi Saya" });

const route = useRoute();
const router = useRouter();
const api = useApi();
const { materiSaya, batalAjukan, buatRevisi, batalTarik, hapus } = useMateri();
const { tampilkan } = usePesanToast();

type AksiBaris = "ubah" | "lihat" | "ajukan" | "batalAjukan" | "tarik" | "batalTarik";

const tabValid = ["semua", "draf", "diajukan", "terbit", "ditolak"];
const tabAwal = typeof route.query.tab === "string" && tabValid.includes(route.query.tab) ? route.query.tab : "semua";

const legenda: { status: StatusMateri; arti: string }[] = [
  { status: "draf", arti: "Belum tayang — hanya bisa dibaca anggota" },
  { status: "diajukan", arti: "Lagi dicek admin — sebentar lagi diputuskan" },
  { status: "ditolak", arti: "Belum disetujui — tetap bisa dibaca anggota" },
  { status: "terbit", arti: "Tayang untuk umum — bisa dibaca siapa saja" },
];

const tab = ref(tabAwal);
const kataKunci = ref(typeof route.query.q === "string" ? route.query.q : "");
const halaman = ref(1);
const daftarMateri = ref<MateriRingkas[]>([]);
const meta = ref<MetaHalaman | null>(null);
const memuat = ref(true);
const galat = ref("");

const ringkasan = ref<DasborAnggota | null>(null);

const modalAjukan = ref(false);
const akanDiajukan = ref<MateriRingkas | null>(null);

const modalTarik = ref(false);
const akanDitarik = ref<MateriRingkas | null>(null);

// Id materi yang aksinya sedang berjalan (Ubah, Batalkan pengajuan/penarikan).
const memproses = ref<number | null>(null);

const modalHapus = ref(false);
const akanDihapus = ref<MateriRingkas | null>(null);
const galatHapus = ref("");
const memuatHapus = ref(false);

// Angka di tab diambil dari ringkasan dasbor agar tidak perlu hitung sendiri.
const daftarTab = computed(() => [
  { kunci: "semua", label: "Semua", jumlah: ringkasan.value?.materi.total },
  { kunci: "draf", label: "Belum tayang", jumlah: ringkasan.value?.materi.draf },
  { kunci: "diajukan", label: "Lagi dicek admin", jumlah: ringkasan.value?.materi.diajukan },
  { kunci: "ditolak", label: "Belum disetujui", jumlah: ringkasan.value?.materi.ditolak },
  { kunci: "terbit", label: "Tayang untuk umum", jumlah: ringkasan.value?.materi.terbit },
]);

const kosong = computed(() => {
  // Saat mencari, pesan kosong berlaku untuk semua tab.
  if (kataKunci.value.trim()) {
    return {
      judul: "Materinya tidak ketemu.",
      deskripsi: "Coba kata kunci lain ya.",
    };
  }

  const semua = {
    judul: "Kamu belum punya materi.",
    deskripsi: "Yuk, tulis materi pertamamu.",
  };

  const teks: Record<string, { judul: string; deskripsi: string }> = {
    semua,
    draf: {
      judul: "Tidak ada materi yang belum tayang.",
      deskripsi: "Materi yang belum kamu ajukan akan muncul di sini.",
    },
    diajukan: {
      judul: "Tidak ada materi yang lagi dicek.",
      deskripsi: "Materi yang kamu ajukan akan muncul di sini selama dicek admin.",
    },
    terbit: {
      judul: "Belum ada materi yang tayang.",
      deskripsi: "Materi yang disetujui admin akan muncul di sini.",
    },
    ditolak: {
      judul: "Semua pengajuanmu aman.",
      deskripsi: "Kalau ada yang belum disetujui admin, materinya akan muncul di sini beserta catatannya.",
    },
  };

  return teks[tab.value] ?? semua;
});

/**
 * Materi terbit yang revisi terakhirnya masih draf: yang ditampilkan adalah
 * status revisi itu, bukan status materi (DESIGN.md §7).
 */
function adaDrafRevisi(item: MateriRingkas) {
  return item.status === "terbit" && item.revisi_terakhir?.status === "draf";
}

/** Status yang dipakai baris: materi terbit mengikuti status revisi terakhirnya. */
function statusBaris(item: MateriRingkas): StatusMateri {
  if (adaDrafRevisi(item)) return "draf";
  if (item.status === "terbit" && item.revisi_terakhir?.status === "diajukan") return "diajukan";

  return item.status;
}

function labelBaris(item: MateriRingkas) {
  return adaDrafRevisi(item) ? "Perubahan belum tayang" : undefined;
}

/** Ada pengajuan penarikan yang menunggu keputusan admin (KF-20). */
function menungguPenarikan(item: MateriRingkas) {
  return item.penarikan?.status === "diajukan";
}

// Keterangan di samping chip, mis. "Versi publik: v2 · Menunggu penarikan" (DESIGN.md §7).
function catatanBaris(item: MateriRingkas) {
  const bagian: string[] = [];

  if (adaDrafRevisi(item) && item.versi_publik) bagian.push("Versi lama masih tayang");
  if (menungguPenarikan(item)) bagian.push("Lagi dicek admin untuk diturunkan");

  return bagian.length ? bagian.join(" · ") : undefined;
}

/**
 * Tombol per baris (DESIGN.md §7, Draf 2.3):
 * - terbit       → Ubah + Ajukan tarik materi (atau Batalkan penarikan bila menunggu)
 * - diajukan     → Lihat + Batalkan pengajuan
 * - ditolak      → Ubah (membuat revisi baru)
 * - draf         → Ubah + Ajukan publikasi (bila revisi terakhir masih draf)
 */
function daftarAksi(item: MateriRingkas): AksiBaris[] {
  const revisi = item.revisi_terakhir?.status;

  if (item.status === "terbit") return ["ubah", menungguPenarikan(item) ? "batalTarik" : "tarik"];
  if (revisi === "diajukan") return ["lihat", "batalAjukan"];
  if (item.status === "ditolak") return ["ubah"];

  return revisi === "draf" ? ["ubah", "ajukan"] : ["ubah"];
}

function aksi(item: MateriRingkas, jenis: AksiBaris) {
  return daftarAksi(item).includes(jenis);
}

function keBaris(item: MateriRingkas) {
  return item.revisi_terakhir?.status === "draf" ? `/materi-saya/${item.id}/edit` : `/materi/${item.slug}`;
}

function bisaHapus(item: MateriRingkas) {
  return item.status === "draf" && !item.diterbitkan_pada;
}

let permintaanTerakhir = 0;

async function muat() {
  const nomor = ++permintaanTerakhir;
  memuat.value = true;
  galat.value = "";

  try {
    const respons = await materiSaya({
      q: kataKunci.value.trim() || undefined,
      status: tab.value === "semua" ? undefined : tab.value,
      page: halaman.value,
    });

    if (nomor !== permintaanTerakhir) return;

    daftarMateri.value = respons.data;
    meta.value = respons.meta;
  } catch (galatServer) {
    if (nomor === permintaanTerakhir) galat.value = pesanGalat(galatServer);
  } finally {
    if (nomor === permintaanTerakhir) memuat.value = false;
  }
}

// Jumlah di tab selalu disegarkan setelah ada perubahan status materi.
async function muatRingkasan() {
  try {
    const respons = await api.get<ResponsData<DasborAnggota>>("/dasbor");
    ringkasan.value = respons.data;
  } catch {
    ringkasan.value = null;
  }
}

function mulaiDariAwal() {
  if (halaman.value !== 1) {
    halaman.value = 1;
  } else {
    muat();
  }
}

/** Simpan kata kunci dan tab di URL agar tetap ada saat halaman dimuat ulang. */
function sinkronUrl() {
  router.replace({
    query: {
      ...(kataKunci.value.trim() ? { q: kataKunci.value.trim() } : {}),
      ...(tab.value !== "semua" ? { tab: tab.value } : {}),
    },
  });
}

/** Dipanggil kotak cari: realtime (dijeda) maupun saat Enter ditekan. */
function cari() {
  sinkronUrl();
  mulaiDariAwal();
}

function bersihkanPencarian() {
  kataKunci.value = "";
  cari();
}

/** Setelah ajukan/tarik berhasil: tampilkan pesan dan segarkan daftar + angka tab. */
async function setelahAksi(pesan: string) {
  tampilkan(pesan, "sukses");
  await Promise.all([mulaiDariAwal(), muatRingkasan()]);
}

async function mintaAjukan(item: MateriRingkas) {
  akanDiajukan.value = item;
  // Tunggu dialog terpasang (v-if) sebelum dibuka.
  await nextTick();
  modalAjukan.value = true;
}

async function mintaTarik(item: MateriRingkas) {
  akanDitarik.value = item;
  await nextTick();
  modalTarik.value = true;
}

/**
 * "Ubah": revisi draf langsung dibuka di editor. Bila revisi terakhir sudah
 * disetujui/ditolak, revisi baru dibuat dulu (KF-12) lalu editor dibuka.
 */
async function ubahSekarang(item: MateriRingkas) {
  if (item.revisi_terakhir?.status === "draf") {
    await navigateTo(`/materi-saya/${item.id}/edit`);
    return;
  }

  memproses.value = item.id;

  try {
    await buatRevisi(item.id);
    await navigateTo(`/materi-saya/${item.id}/edit`);
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), "bahaya");
  } finally {
    memproses.value = null;
  }
}

/** Batalkan pengajuan publikasi yang belum diputuskan admin (KP-10.6). */
async function batalAjukanSekarang(item: MateriRingkas) {
  memproses.value = item.id;

  try {
    const respons = await batalAjukan(item.id);
    await setelahAksi(respons.message);
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), "bahaya");
  } finally {
    memproses.value = null;
  }
}

/** Batalkan pengajuan penarikan yang belum diputuskan admin (KP-20.3). */
async function batalTarikSekarang(item: MateriRingkas) {
  memproses.value = item.id;

  try {
    const respons = await batalTarik(item.id);
    await setelahAksi(respons.message);
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), "bahaya");
  } finally {
    memproses.value = null;
  }
}

function mintaHapus(item: MateriRingkas) {
  akanDihapus.value = item;
  galatHapus.value = "";
  modalHapus.value = true;
}

async function hapusSekarang() {
  if (!akanDihapus.value) return;

  galatHapus.value = "";
  memuatHapus.value = true;

  try {
    const respons = await hapus(akanDihapus.value.id);

    modalHapus.value = false;
    tampilkan(respons.message, "sukses");

    // Bila baris terakhir di halaman ini dihapus, mundur satu halaman.
    if (daftarMateri.value.length === 1 && halaman.value > 1) {
      halaman.value -= 1;
    } else {
      await muat();
    }

    await muatRingkasan();
  } catch (galatServer) {
    galatHapus.value = pesanGalat(galatServer);
  } finally {
    memuatHapus.value = false;
  }
}

watch(tab, () => {
  sinkronUrl();
  mulaiDariAwal();
});
watch(halaman, muat);

onMounted(() => {
  // Pesan dari halaman lain (baru.vue, edit.vue) yang mengalihkan ke sini.
  const sukses = route.query.sukses;
  const pesan: Record<string, string> = {
    disimpan: "Materimu sudah disimpan. Semua anggota sudah bisa membacanya.",
    dihapus: "Materinya sudah dihapus.",
  };

  if (typeof sukses === "string" && pesan[sukses]) {
    tampilkan(pesan[sukses], "sukses");
  }

  muatRingkasan();
  muat();
});
</script>
