<template>
  <!-- Kartu program untuk daftar /program. Polanya sama dengan KartuArtikel
       (DESIGN.md §5): sampul 16:10 di atas, lalu status, nama, ringkasan, dan meta. -->
  <article
    class="group flex h-full flex-col overflow-hidden rounded-lg bg-permukaan ring-1 ring-garis transition-colors duration-200 hover:ring-garis-kuat"
  >
    <NuxtLink :to="tautan" tabindex="-1" aria-hidden="true" class="block">
      <div class="aspect-[16/10] w-full overflow-hidden bg-angkat">
        <img
          v-if="program.sampul"
          :src="program.sampul"
          alt=""
          loading="lazy"
          class="h-full w-full object-cover transition-transform duration-300 ease-halus group-hover:scale-[1.02]"
        />
        <span v-else class="grid h-full w-full place-items-center text-teks-redup">
          <CalendarDays class="h-6 w-6" aria-hidden="true" />
        </span>
      </div>
    </NuxtLink>

    <div class="flex flex-1 flex-col p-5">
      <LencanaStatusProgram :status="program.status" class="self-start" />

      <h2 class="mt-3 font-serif text-kartu font-bold text-teks-kuat">
        <NuxtLink :to="tautan" class="transition-colors duration-200 hover:text-aksen">
          {{ program.nama }}
        </NuxtLink>
      </h2>

      <p v-if="program.ringkasan" class="mt-2 line-clamp-3 text-sm leading-relaxed text-teks-lembut">
        {{ program.ringkasan }}
      </p>

      <div class="mt-auto space-y-1.5 pt-4 text-[13px] text-teks-redup">
        <p v-if="program.jadwal" class="flex items-start gap-2">
          <CalendarDays class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
          <span>{{ program.jadwal }}</span>
        </p>

        <p v-if="namaPic" class="flex items-start gap-2">
          <Users class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
          <span>PIC: {{ namaPic }}</span>
        </p>

        <p v-if="ringkasDokumentasi" class="flex items-start gap-2">
          <Image class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
          <span>{{ ringkasDokumentasi }}</span>
        </p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { CalendarDays, Image, Users } from '@lucide/vue'
import type { ProgramRingkas } from '~/types/api'

const props = defineProps<{ program: ProgramRingkas }>()

const tautan = computed(() => `/program/${props.program.slug}`)

const namaPic = computed(() =>
  props.program.pic
    .map((item) => item.nama)
    .filter(Boolean)
    .join(', '),
)

// Mis. "12 foto · 3 video"; disembunyikan bila belum ada dokumentasi.
const ringkasDokumentasi = computed(() => {
  const bagian: string[] = []

  if (props.program.jumlah_foto) bagian.push(`${props.program.jumlah_foto} foto`)
  if (props.program.jumlah_video) bagian.push(`${props.program.jumlah_video} video`)

  return bagian.join(' · ')
})
</script>
