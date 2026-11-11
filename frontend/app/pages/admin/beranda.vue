<template>
  <div class="mx-auto max-w-3xl">
    <header class="pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Konten beranda</h1>
      <p class="mt-3 text-sm text-teks-lembut">
        Cerita, visi, misi, dan kontak organisasi yang tampil di halaman pengunjung.
      </p>
    </header>

    <!-- Kerangka memuat: bentuknya sama dengan formulir. -->
    <div v-if="memuat" class="animate-pulse space-y-6">
      <div class="h-11 rounded-lg bg-permukaan" />
      <div class="h-56 rounded-lg bg-permukaan" />
    </div>

    <AppAlert v-else-if="galatMuat" variant="bahaya" title="Gagal memuat konten beranda">
      {{ galatMuat }}
    </AppAlert>

    <form v-else novalidate @submit.prevent="simpan">
      <!-- Cerita Al-Futuuhat -->
      <section class="mt-8 border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Cerita Al-Futuuhat</h2>
        <p class="mt-1 text-[13px] text-teks-redup">
          Ditampilkan pada bagian "Cerita Al-Futuuhat" di beranda. Bila dikosongkan, beranda
          memakai teks bawaan.
        </p>

        <div class="mt-4">
          <AppInput
            v-model="judul"
            label="Judul cerita"
            placeholder="Misalnya: Dari serambi ke masyarakat"
            :error="galatIsian.cerita_judul"
          />
        </div>

        <div class="mt-6">
          <ClientOnly>
            <EditorQuill
              v-model:konten="konten"
              v-model:html="html"
              label="Isi cerita"
              hint="Boleh dikosongkan bila belum ada cerita yang ingin ditampilkan."
              placeholder="Tulis cerita Al-Futuuhat di sini..."
              :error="galatIsian.cerita_isi"
            />

            <template #fallback>
              <p class="py-16 text-center text-[13px] text-teks-redup">Menyiapkan editor...</p>
            </template>
          </ClientOnly>
        </div>
      </section>

      <!-- Visi & misi (KF-26): tampil di halaman Tentang kami. -->
      <section class="mt-8 border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Visi dan misi</h2>
        <p class="mt-1 text-[13px] text-teks-redup">
          Ditampilkan di halaman Tentang kami. Bagian yang dikosongkan tidak ditampilkan.
        </p>

        <div class="mt-4 space-y-4">
          <AppTextarea
            v-model="visi"
            label="Visi"
            :rows="3"
            placeholder="Tuliskan visi organisasi."
            :error="galatIsian.visi"
          />
          <AppTextarea
            v-model="misi"
            label="Misi"
            :rows="6"
            placeholder="Tuliskan satu poin misi per baris."
            hint="Satu poin per baris; setiap baris tampil sebagai nomor tersendiri."
            :error="galatIsian.misi"
          />
        </div>
      </section>

      <!-- Kontak organisasi (KF-22): tampil di bagian bawah halaman publik. -->
      <section class="mt-8 border-t border-garis pt-6">
        <h2 class="text-sm font-medium text-teks-kuat">Kontak organisasi</h2>
        <p class="mt-1 text-[13px] text-teks-redup">
          Ditampilkan di bagian bawah halaman publik. Kolom yang dikosongkan tidak ditampilkan.
        </p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
          <AppInput
            v-model="kontak.alamat"
            label="Alamat sekretariat"
            placeholder="Misalnya: Jl. Masjid No. 1, Bandung"
            :error="galatKontak.kontak_alamat"
            class="sm:col-span-2"
          />
          <AppInput
            v-model="kontak.whatsapp"
            type="tel"
            label="Nomor WhatsApp"
            placeholder="Misalnya: 081234567890"
            :error="galatKontak.kontak_whatsapp"
          />
          <AppInput
            v-model="kontak.email"
            type="email"
            label="Email"
            placeholder="Misalnya: halo@alfutuuhat.id"
            :error="galatKontak.kontak_email"
          />
          <AppInput
            v-model="kontak.instagram"
            label="Akun Instagram"
            placeholder="Misalnya: alfutuuhat"
            :error="galatKontak.kontak_instagram"
          />
        </div>
      </section>

      <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-garis pt-6">
        <AppButton type="submit" :loading="menyimpan" :disabled="menyimpan">Simpan</AppButton>

        <p v-if="diperbaruiPada" class="text-[13px] text-teks-redup">
          Terakhir diperbarui {{ formatTanggalWaktu(diperbaruiPada) }}
        </p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import type { KontenQuill } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Konten Beranda' })

const { tampil, simpan: simpanBeranda } = useBeranda()
const { tampilkan } = usePesanToast()

const memuat = ref(true)
const galatMuat = ref('')
const menyimpan = ref(false)

const judul = ref('')
const konten = ref<KontenQuill>({ ops: [] })
const html = ref('')
const diperbaruiPada = ref<string | null>(null)

const galatIsian = ref<{ cerita_judul?: string; cerita_isi?: string; visi?: string; misi?: string }>({})

// Kontak organisasi (KF-22).
const kontak = reactive({ alamat: '', whatsapp: '', email: '', instagram: '' })

// Visi & misi (KF-26).
const visi = ref('')
const misi = ref('')
const galatKontak = ref<Record<string, string | undefined>>({})

async function muat() {
  memuat.value = true
  galatMuat.value = ''

  try {
    const respons = await tampil()

    judul.value = respons.data.cerita_judul ?? ''
    html.value = respons.data.cerita_isi ?? ''
    diperbaruiPada.value = respons.data.diperbarui_pada
    visi.value = respons.data.visi ?? ''
    misi.value = respons.data.misi ?? ''
    kontak.alamat = respons.data.kontak?.alamat ?? ''
    // Nomor tersimpan berawalan 62; ditampilkan apa adanya.
    kontak.whatsapp = respons.data.kontak?.whatsapp ?? ''
    kontak.email = respons.data.kontak?.email ?? ''
    kontak.instagram = respons.data.kontak?.instagram ?? ''
  } catch (galatServer) {
    galatMuat.value = pesanGalat(galatServer, 'Gagal memuat konten beranda.')
  } finally {
    memuat.value = false
  }
}

async function simpan() {
  menyimpan.value = true
  galatIsian.value = {}

  const data = new FormData()
  data.append('cerita_judul', judul.value.trim())
  // HTML Quill selalu memakai &nbsp;; rapikan seperti saat menyimpan materi.
  data.append('cerita_isi', rapikanSpasiHtml(html.value))
  data.append('visi', visi.value.trim())
  data.append('misi', misi.value.trim())
  data.append('kontak_alamat', kontak.alamat.trim())
  data.append('kontak_whatsapp', kontak.whatsapp.trim())
  data.append('kontak_email', kontak.email.trim())
  data.append('kontak_instagram', kontak.instagram.trim())
  galatKontak.value = {}

  try {
    const respons = await simpanBeranda(data)

    diperbaruiPada.value = respons.data.diperbarui_pada
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatIsian.value = {
      cerita_judul: isian.cerita_judul?.[0],
      cerita_isi: isian.cerita_isi?.[0],
      visi: isian.visi?.[0],
      misi: isian.misi?.[0],
    }

    galatKontak.value = {
      kontak_alamat: isian.kontak_alamat?.[0],
      kontak_whatsapp: isian.kontak_whatsapp?.[0],
      kontak_email: isian.kontak_email?.[0],
      kontak_instagram: isian.kontak_instagram?.[0],
    }

    // Galat yang tidak tertampung pada isian mana pun ditampilkan sebagai toast.
    if (Object.keys(isian).length === 0) {
      tampilkan(pesanGalat(galatServer), 'bahaya')
    }
  } finally {
    menyimpan.value = false
  }
}

onMounted(muat)
</script>
