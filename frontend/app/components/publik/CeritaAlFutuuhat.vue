<template>
  <!-- Cerita Al-Futuuhat (KF-18). Mode `ringkas` (beranda): tinggi dibatasi dengan
       efek pudar dan tombol "Baca selengkapnya" ke /tentang bila ceritanya terpotong. -->
  <div>
    <h3 v-if="judul" class="max-w-baca font-serif text-subjudul font-bold text-teks-kuat">{{ judul }}</h3>

    <div class="relative max-w-baca" :class="judul ? 'mt-6' : ''">
      <div ref="isiEl" :class="ringkas ? 'max-h-[22rem] overflow-hidden' : ''">
        <!-- HTML sudah disanitasi backend (KP-08.4). -->
        <div v-if="isi" class="prosa" v-html="isi" />

        <!-- Teks bawaan selama admin belum mengisi cerita (KP-18.2). -->
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

      <!-- Efek pudar di bagian bawah cerita yang terpotong. -->
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
  /** HTML cerita dari admin; kosong = teks bawaan. */
  isi?: string | null
  /** Beranda: potong cerita panjang dan tampilkan tombol ke /tentang. */
  ringkas?: boolean
}>()

const isiEl = ref<HTMLElement | null>(null)
const terpotong = ref(false)

// Tombol hanya muncul bila isinya memang lebih tinggi dari batas.
function ukur() {
  const el = isiEl.value
  terpotong.value = Boolean(props.ringkas && el && el.scrollHeight > el.clientHeight + 4)
}

onMounted(() => nextTick(ukur))
watch(() => props.isi, () => nextTick(ukur))
</script>
