<template>
  <!-- Baris tulisan -->
  <article class="border-b border-garis py-7">
    <div class="flex items-start gap-5">
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2 text-[13px]">
          <AvatarInisial :nama="materi.penulis?.nama" :id="materi.penulis?.id" ukuran="kecil" />
          <span class="text-teks">{{ materi.penulis?.nama ?? 'Anonim' }}</span>

          <template v-if="tampilStatus">
            <span class="text-teks-redup" aria-hidden="true">·</span>
            <StatusChip
              :status="statusChip ?? materi.status"
              :versi="versiChip"
              :label="labelChip"
            />
            <!-- Keterangan tambahan -->
            <span v-if="catatanChip" class="text-teks-redup">{{ catatanChip }}</span>
          </template>
        </div>

        <h3 class="mt-2 font-serif text-kartu font-bold text-teks-kuat">
          <NuxtLink :to="tautan" class="transition-colors duration-200 hover:text-aksen">
            {{ materi.judul }}
          </NuxtLink>
        </h3>

        <p v-if="materi.ringkasan" class="mt-1 line-clamp-3 text-base text-teks-lembut">
          {{ materi.ringkasan }}
        </p>

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-teks-lembut">
          <span>{{ formatTanggal(tanggalTampil) }}</span>

          <template v-if="materi.menit_baca">
            <span class="text-teks-redup" aria-hidden="true">·</span>
            <span>{{ materi.menit_baca }} menit baca</span>
          </template>

          <template v-if="jumlahKomentar !== null && jumlahKomentar !== undefined">
            <span class="text-teks-redup" aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-1.5">
              <MessageCircle class="h-4 w-4" aria-hidden="true" />
              {{ jumlahKomentar }}
            </span>
          </template>

          <!-- Aksi tambahan -->
          <div v-if="$slots.aksi" class="ml-auto flex items-center gap-3">
            <slot name="aksi" />
          </div>
        </div>

        <!-- Kategori materi -->
        <ChipKategori
          v-if="tampilKategori && materi.kategori?.length"
          :daftar="materi.kategori"
          ke="/materi"
          class="mt-3"
        />
      </div>

      <!-- Sampul kecil di kanan -->
      <NuxtLink
        v-if="materi.sampul"
        :to="tautan"
        tabindex="-1"
        aria-hidden="true"
        class="h-16 w-24 shrink-0 overflow-hidden rounded-lg border border-garis bg-permukaan sm:h-[104px] sm:w-40"
      >
        <img :src="materi.sampul" alt="" loading="lazy" class="h-full w-full object-cover" />
      </NuxtLink>
    </div>
  </article>
</template>

<script setup lang="ts">
import { MessageCircle } from '@lucide/vue'
import type { Kategori, StatusMateri } from '~/types/api'

interface Props {
  materi: {
    id: number
    slug: string
    judul: string
    ringkasan: string | null
    sampul?: string | null
    status?: StatusMateri
    penulis?: { id?: number; nama: string } | null
    diterbitkan_pada?: string | null
    diperbarui_pada?: string | null
    dibuat_pada?: string | null
    menit_baca?: number | null
    kategori?: Kategori[]
    revisi_terakhir?: { nomor_versi: number; status: string } | null
    nomor_versi?: number | null
  }
  tampilStatus?: boolean
  tampilKategori?: boolean
  statusChip?: StatusMateri
  labelChip?: string
  catatanChip?: string
  jumlahKomentar?: number | null
  ke?: string
  tanggal?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  tampilStatus: false,
  tampilKategori: false,
  statusChip: undefined,
  labelChip: undefined,
  catatanChip: undefined,
  jumlahKomentar: null,
  ke: '',
  tanggal: null,
})

const tautan = computed(() => props.ke || `/materi/${props.materi.slug}`)

const tanggalTampil = computed(
  () =>
    props.tanggal ??
    props.materi.diterbitkan_pada ??
    props.materi.diperbarui_pada ??
    props.materi.dibuat_pada ??
    null,
)

const versiChip = computed(
  () => props.materi.revisi_terakhir?.nomor_versi ?? props.materi.nomor_versi ?? null,
)
</script>
