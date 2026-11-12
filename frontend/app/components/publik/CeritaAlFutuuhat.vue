<template>
  <!-- Cerita Al-Futuuhat -->
  <div>
    <h3 v-if="judul" class="max-w-baca font-serif text-subjudul font-bold text-teks-kuat">{{ judul }}</h3>

    <div class="relative max-w-baca" :class="judul ? 'mt-6' : ''">
      <div ref="isiEl" :class="ringkas ? 'max-h-[22rem] overflow-hidden' : ''">
        <!-- HTML sudah disanitasi backend -->
        <div v-if="isi" class="prosa" v-html="isi" />

        <div v-else class="space-y-6 font-serif text-badan text-teks">
          <p>
            Al-Futuuhat adalah organisasi yang menghimpun anggota untuk belajar, berkarya, dan
            mengabdi bersama. Setiap anggota mendapat kesempatan mengikuti pembinaan serta kegiatan
            yang diselenggarakan oleh divisi sesuai minat dan bidangnya.
          </p>
          <p>
            Sistem informasi ini menjadi tempat anggota mendaftar, membaca materi, menyusun materi
            baru, dan berdiskusi melalui forum internal.
          </p>
        </div>
      </div>

      <div
        v-if="terpotong"
        class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-kanvas to-transparent"
        aria-hidden="true"
      />
    </div>

    <NuxtLink v-if="terpotong" to="/tentang" class="btn btn-sekunder btn-sm mt-5">
      Baca selengkapnya
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  judul?: string | null
  isi?: string | null
  ringkas?: boolean
}>()

const isiEl = ref<HTMLElement | null>(null)
const terpotong = ref(false)

// Ukur tinggi cerita
function ukur() {
  const el = isiEl.value
  terpotong.value = Boolean(props.ringkas && el && el.scrollHeight > el.clientHeight + 4)
}

onMounted(() => nextTick(ukur))
watch(() => props.isi, () => nextTick(ukur))
</script>
