<template>
  <!-- Ajukan penarikan materi -->
  <AppModal
    v-model:open="terbuka"
    title="Turunkan materi ini dari halaman umum?"
    deskripsi="Materi tetap tayang sampai admin menyetujui. Setelah turun, kamu bisa mengajukan versi terbarumu."
  >
    <AppTextarea
      v-model="alasan"
      label="Kenapa mau diturunkan?"
      placeholder="Misalnya: ada bagian yang perlu diperbarui."
      :rows="5"
      :hint="`${alasan.length}/${BATAS} karakter`"
      :error="galat"
      required
    />

    <template #footer>
      <AppButton variant="hantu" :disabled="memuat" @click="terbuka = false">Batal</AppButton>
      <AppButton :loading="memuat" @click="kirim">Kirim permintaan</AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import type { Penarikan } from '~/types/api'

const props = defineProps<{ materiId: number | string }>()

const emit = defineEmits<{ diajukan: [respons: { message: string; data: Penarikan }] }>()

const terbuka = defineModel<boolean>('open', { default: false })

const { tarik } = useMateri()

const BATAS = 1000

const alasan = ref('')
const galat = ref('')
const memuat = ref(false)

watch(terbuka, (buka) => {
  if (!buka) return

  alasan.value = ''
  galat.value = ''
})

async function kirim() {
  if (!alasan.value.trim()) {
    galat.value = 'Tulis dulu alasannya ya.'
    return
  }

  if (alasan.value.length > BATAS) {
    galat.value = 'Alasannya kepanjangan, maksimal 1.000 karakter ya.'
    return
  }

  memuat.value = true
  galat.value = ''

  try {
    const respons = await tarik(props.materiId, alasan.value.trim())

    terbuka.value = false
    emit('diajukan', respons)
  } catch (galatServer) {
    galat.value = galatIsian(galatServer, 'alasan') || pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}
</script>
