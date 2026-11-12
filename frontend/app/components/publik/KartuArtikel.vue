<template>
  <!-- Kartu artikel -->
  <article
    class="group flex h-full flex-col overflow-hidden rounded-lg bg-permukaan ring-1 ring-garis transition-colors duration-200 hover:ring-garis-kuat"
  >
    <NuxtLink :to="tautan" tabindex="-1" aria-hidden="true" class="block">
      <div class="aspect-[16/10] w-full overflow-hidden bg-angkat">
        <img
          v-if="materi.sampul"
          :src="materi.sampul"
          alt=""
          loading="lazy"
          class="h-full w-full object-cover transition-transform duration-300 ease-halus group-hover:scale-[1.02]"
        />
        <!-- Tanpa sampul -->
        <SampulBawaan
          v-else
          :id="materi.id"
          :judul="materi.judul"
          :kategori="materi.kategori?.[0]?.nama"
          class="transition-transform duration-300 ease-halus group-hover:scale-[1.02]"
        />
      </div>
    </NuxtLink>

    <div class="flex flex-1 flex-col p-5">
      <div class="flex items-center gap-2 text-[13px]">
        <AvatarInisial :nama="materi.penulis?.nama" ukuran="kecil" />
        <span class="text-teks">{{ materi.penulis?.nama ?? 'Anonim' }}</span>
      </div>

      <ChipKategori
        v-if="materi.kategori?.length"
        :daftar="materi.kategori"
        ke="/publikasi"
        class="mt-3"
      />

      <h2 class="mt-3 font-serif text-kartu font-bold text-teks-kuat">
        <NuxtLink :to="tautan" class="transition-colors duration-200 hover:text-aksen">
          {{ materi.judul }}
        </NuxtLink>
      </h2>

      <p v-if="materi.ringkasan" class="mt-2 line-clamp-3 text-sm leading-relaxed text-teks-lembut">
        {{ materi.ringkasan }}
      </p>

      <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-4 text-[13px] text-teks-redup">
        <span v-if="materi.diterbitkan_pada">{{ formatTanggal(materi.diterbitkan_pada) }}</span>

        <template v-if="materi.menit_baca">
          <span aria-hidden="true">·</span>
          <span>{{ materi.menit_baca }} menit baca</span>
        </template>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { MateriPublikRingkas } from '~/types/api'

const props = defineProps<{ materi: MateriPublikRingkas }>()

const tautan = computed(() => `/publikasi/${props.materi.slug}`)
</script>
