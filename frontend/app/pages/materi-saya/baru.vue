<template>
  <!-- Editor materi baru -->
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

      <AppButton size="sm" :loading="memuat" @click="formRef?.kirim()">Simpan</AppButton>
    </template>
  </FormMateri>
</template>

<script setup lang="ts">
import type { PayloadMateri } from '~/types/api'

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

    await navigateTo({ path: '/materi-saya', query: { tab: 'draf', sukses: 'disimpan' } })
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatIsian.value = {
      judul: isian.judul?.[0],
      ringkasan: isian.ringkasan?.[0],
      sampul: isian.sampul?.[0],
      kategori: isian.kategori?.[0] ?? isian['kategori.0']?.[0],
      konten: isian.konten?.[0] ?? isian['konten.ops']?.[0] ?? isian.konten_html?.[0],
      catatan_revisi: isian.catatan_revisi?.[0],
    }

    if (!Object.values(galatIsian.value).some(Boolean)) {
      galatUmum.value = pesanGalat(galatServer)
      tampilkan(galatUmum.value, 'bahaya')
    }
  } finally {
    memuat.value = false
  }
}
</script>