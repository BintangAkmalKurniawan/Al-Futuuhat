<template>
  <!-- Editor materi baru. Header halaman memakai layout anggota (navigasi saja);
       status dan tombol aksi ada di dalam FormMateri. Belum ada status, jadi chip
       tidak ditampilkan (layar 12). -->
  <FormMateri
    ref="formRef"
    keterangan="Materi baru"
    :memuat="memuat"
    :galat-umum="galatUmum"
    :galat-isian="galatIsian"
    @simpan="kirim"
  >
    <template #aksi>
      <AppButton variant="hantu" size="sm" @click="navigateTo('/materi-saya')">
        Batal
      </AppButton>

      <!-- Materi baru belum punya id, jadi tombol utama menyimpan sebagai draf. -->
      <AppButton size="sm" :loading="memuat" @click="formRef?.kirim()">Simpan</AppButton>
    </template>
  </FormMateri>
</template>

<script setup lang="ts">
import type { PayloadMateri } from '~/types/api'

// KP-08.6: admin hanya mengelola, tidak menulis materi → dialihkan ke /admin.
definePageMeta({ layout: 'anggota', middleware: ['auth', 'bukan-admin'] })
useHead({ title: 'Tulis Materi' })

const { simpan: simpanMateri } = useMateri()
const { tampilkan } = usePesanToast()

const formRef = ref<{ kirim: () => void } | null>(null)

const memuat = ref(false)
const galatUmum = ref('')
const galatIsian = ref<{
  judul?: string
  ringkasan?: string
  sampul?: string
  kategori?: string
  konten?: string
  catatan_revisi?: string
}>({})

async function kirim(payload: PayloadMateri) {
  memuat.value = true
  galatUmum.value = ''
  galatIsian.value = {}

  try {
    await simpanMateri(payload)

    // Langsung buka tab Draf agar materi baru terlihat (alur F5).
    await navigateTo({ path: '/materi-saya', query: { tab: 'draf', sukses: 'disimpan' } })
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatIsian.value = {
      judul: isian.judul?.[0],
      ringkasan: isian.ringkasan?.[0],
      sampul: isian.sampul?.[0],
      // Kategori memakai kunci `kategori` atau `kategori.0` (KF-19).
      kategori: isian.kategori?.[0] ?? isian['kategori.0']?.[0],
      konten: isian.konten?.[0] ?? isian['konten.ops']?.[0] ?? isian.konten_html?.[0],
      catatan_revisi: isian.catatan_revisi?.[0],
    }

    // Tampilkan pesan umum bila galat tidak tertampung pada isian mana pun.
    if (!Object.values(galatIsian.value).some(Boolean)) {
      galatUmum.value = pesanGalat(galatServer)
      tampilkan(galatUmum.value, 'bahaya')
    }
  } finally {
    memuat.value = false
  }
}
</script>