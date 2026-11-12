<template>
  <!-- Perbandingan dua versi materi -->
  <div>
    <!-- Ponsel -->
    <AppTabs v-model="sisi" :tabs="tabSisi" class="mb-2 md:hidden" />

    <!-- Layar lebar -->
    <div class="hidden grid-cols-2 gap-8 pb-3 md:grid">
      <p v-for="kolom in tabSisi" :key="kolom.kunci" class="text-[13px] font-medium text-teks-kuat">
        {{ kolom.label }}
      </p>
    </div>

    <section v-for="bagian in daftarBagian" :key="bagian.kunci" class="border-t border-garis py-5">
      <div class="mb-3 flex items-center gap-2.5 text-[12px] tracking-[.06em] text-teks-redup uppercase">
        {{ bagian.label }}
        <span v-if="bagian.berubah" class="text-[12px] tracking-normal text-aksen normal-case">Berubah</span>
      </div>

      <div class="grid gap-8 md:grid-cols-2">
        <div v-for="kolom in (['kiri', 'kanan'] as const)" :key="kolom" :class="kolom !== sisi && 'hidden md:block'">
          <!-- Judul & ringkasan & isi -->
          <p
            v-if="bagian.kunci !== 'sampul' && bagian.kunci !== 'kategori'"
            :class="kelasTeks[bagian.kunci as KunciTeks]"
          >
            <template v-if="potonganKolom(bagian.kunci, kolom).length">
              <span
                v-for="(potongan, indeks) in potonganKolom(bagian.kunci, kolom)"
                :key="indeks"
                :class="kelasPotongan[potongan.jenis]"
              >{{ potongan.teks }}</span>
            </template>
            <span v-else class="font-sans text-[13px] text-teks-redup">(kosong)</span>
          </p>

          <!-- Sampul -->
          <template v-else-if="bagian.kunci === 'sampul'">
            <img
              v-if="versi(kolom).sampul"
              :src="versi(kolom).sampul ?? undefined"
              :alt="`Sampul ${labelKolom(kolom)}`"
              class="aspect-[16/10] w-full max-w-sm rounded-lg border border-garis object-cover"
            />
            <span v-else class="text-[13px] text-teks-redup">Tanpa sampul</span>
          </template>

          <!-- Kategori -->
          <div v-else class="flex flex-wrap gap-2">
            <span
              v-for="item in versi(kolom).kategori ?? []"
              :key="item.id"
              class="inline-flex items-center rounded-full border px-2.5 text-[12px] leading-5"
              :class="kelasKategori(item.id, kolom)"
            >
              {{ item.nama }}
            </span>
          </div>
        </div>
      </div>

      <p v-if="bagian.kunci === 'isi' && hanyaFormat" class="mt-3 text-[13px] text-teks-redup">
        Tulisannya sama, yang berubah format atau gambarnya.
      </p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { diffWords } from 'diff'
import type { Revisi } from '~/types/api'

type Kolom = 'kiri' | 'kanan'
type KunciBagian = 'judul' | 'ringkasan' | 'sampul' | 'kategori' | 'isi'
type KunciTeks = 'judul' | 'ringkasan' | 'isi'

interface Potongan {
  teks: string
  jenis: 'sama' | 'tambah' | 'hapus'
}

const props = defineProps<{
  kiri: Revisi
  kanan: Revisi
  labelKiri?: string
  labelKanan?: string
}>()

const sisi = ref<string>('kanan')

const tabSisi = computed(() => [
  { kunci: 'kiri', label: labelKolom('kiri') },
  { kunci: 'kanan', label: labelKolom('kanan') },
])

function labelKolom(kolom: Kolom) {
  return kolom === 'kiri'
    ? (props.labelKiri ?? 'Versi yang sedang tayang')
    : (props.labelKanan ?? 'Versi barumu')
}

function versi(kolom: Kolom): Revisi {
  return kolom === 'kiri' ? props.kiri : props.kanan
}

// Teks polos
function teksPolos(html: string | null | undefined): string {
  const dokumen = new DOMParser().parseFromString(html ?? '', 'text/html')

  dokumen.querySelectorAll('img').forEach((gambar) => gambar.replaceWith('[gambar]'))

  const blok = Array.from(dokumen.body.querySelectorAll('p, h1, h2, h3, h4, li, blockquote, pre'))
  const baris = blok.length ? blok.map((el) => el.textContent ?? '') : [dokumen.body.textContent ?? '']

  return baris.map((teks) => teks.trim()).filter(Boolean).join('\n\n')
}

// Bandingkan per kata
function banding(lama: string, baru: string): Potongan[] {
  return diffWords(lama, baru).map((bagian) => ({
    teks: bagian.value,
    jenis: bagian.added ? 'tambah' : bagian.removed ? 'hapus' : 'sama',
  }))
}

const selisih = computed<Record<KunciTeks, Potongan[]>>(() => ({
  judul: banding(props.kiri.judul ?? '', props.kanan.judul ?? ''),
  ringkasan: banding(props.kiri.ringkasan ?? '', props.kanan.ringkasan ?? ''),
  isi: banding(teksPolos(props.kiri.konten_html), teksPolos(props.kanan.konten_html)),
}))

// Potongan per kolom
function potonganKolom(kunci: KunciBagian, kolom: Kolom): Potongan[] {
  const dibuang = kolom === 'kiri' ? 'tambah' : 'hapus'

  return (selisih.value[kunci as KunciTeks] ?? []).filter((p) => p.jenis !== dibuang && p.teks !== '')
}

const idKiri = computed(() => new Set((props.kiri.kategori ?? []).map((k) => k.id)))
const idKanan = computed(() => new Set((props.kanan.kategori ?? []).map((k) => k.id)))

function kelasKategori(id: number, kolom: Kolom) {
  if (kolom === 'kiri' && !idKanan.value.has(id)) return 'border-garis text-teks-redup line-through'
  if (kolom === 'kanan' && !idKiri.value.has(id)) return 'border-aksen/60 bg-aksen/20 text-teks-kuat'

  return 'border-garis text-teks-lembut'
}

function berbeda(kunci: KunciTeks) {
  return selisih.value[kunci].some((p) => p.jenis !== 'sama')
}

const kategoriBerubah = computed(
  () =>
    idKiri.value.size !== idKanan.value.size ||
    [...idKiri.value].some((id) => !idKanan.value.has(id)),
)

const isiBerubah = computed(() => props.kiri.konten_html !== props.kanan.konten_html)

const hanyaFormat = computed(() => isiBerubah.value && !berbeda('isi'))

const daftarBagian = computed<{ kunci: KunciBagian; label: string; berubah: boolean }[]>(() => [
  { kunci: 'judul', label: 'Judul', berubah: berbeda('judul') },
  { kunci: 'ringkasan', label: 'Ringkasan', berubah: berbeda('ringkasan') },
  { kunci: 'sampul', label: 'Sampul', berubah: props.kiri.sampul !== props.kanan.sampul },
  { kunci: 'kategori', label: 'Kategori', berubah: kategoriBerubah.value },
  { kunci: 'isi', label: 'Isi', berubah: isiBerubah.value },
])

const kelasTeks: Record<KunciTeks, string> = {
  judul: 'font-serif text-[22px] leading-snug font-bold text-teks-kuat',
  ringkasan: 'font-serif text-[17px] leading-relaxed text-teks-lembut',
  isi: 'font-serif text-[16px] leading-relaxed whitespace-pre-line text-teks',
}

const kelasPotongan: Record<Potongan['jenis'], string> = {
  sama: '',
  tambah: 'rounded-sm bg-aksen/20 text-teks-kuat underline decoration-aksen/60 underline-offset-2',
  hapus: 'text-teks-redup line-through decoration-teks-redup',
}
</script>
