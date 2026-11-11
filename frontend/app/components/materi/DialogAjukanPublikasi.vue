<template>
  <!-- Konfirmasi "Ajukan publikasi" (KP-10.7). Materi yang pernah terbit
       menampilkan perbandingan versi publik terakhir dengan versi yang akan
       diajukan; materi yang belum pernah terbit cukup konfirmasi biasa. -->
  <AppModal v-model:open="terbuka" :title="judulDialog" :deskripsi="deskripsiDialog" :lebar="pernahTerbit">
    <AppAlert v-if="galatAjukan" variant="bahaya" title="Belum berhasil diajukan" class="mb-4">
      {{ galatAjukan }}
    </AppAlert>

    <template v-if="pernahTerbit">
      <!-- Kerangka memuat berbentuk dua kolom. -->
      <div v-if="memuatBanding" class="grid animate-pulse gap-8 py-2 md:grid-cols-2">
        <div v-for="n in 2" :key="n" class="space-y-3">
          <div class="h-6 w-3/4 rounded-full bg-permukaan" />
          <div class="h-3 w-full rounded-full bg-permukaan" />
          <div class="h-3 w-5/6 rounded-full bg-permukaan" />
          <div class="h-32 w-full rounded-lg bg-permukaan" />
        </div>
      </div>

      <AppAlert v-else-if="galatBanding" variant="peringatan" title="Perbandingannya belum bisa dimuat">
        {{ galatBanding }}
        <button type="button" class="ml-1 text-aksen hover:underline" @click="muatBanding">Coba lagi</button>
      </AppAlert>

      <PerbandinganVersi v-else-if="kiri && kanan" :kiri="kiri" :kanan="kanan" />

      <p class="mt-4 text-[13px] text-teks-redup">
        Selama dicek admin, isinya belum bisa diubah.
      </p>
    </template>

    <p v-else class="text-sm text-teks-lembut">
      Anggota tetap bisa berdiskusi di forum materi ini.
    </p>

    <template #footer>
      <AppButton variant="hantu" :disabled="memuatAjukan" @click="terbuka = false">Batal</AppButton>
      <AppButton :loading="memuatAjukan" @click="ajukanSekarang">Ya, ajukan</AppButton>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import type { DataEditor, Revisi } from '~/types/api'

const props = defineProps<{
  materiId: number | string
  /** Id revisi terakhir (draf) yang akan diajukan. */
  idRevisi: number | null
  nomorRevisi: number | null
  /** Id versi publik terakhir; null bila materi belum pernah terbit. */
  idVersiPublik: number | null
}>()

const emit = defineEmits<{ diajukan: [respons: { message: string; data: DataEditor }] }>()

const terbuka = defineModel<boolean>('open', { default: false })

const { ajukan, versi } = useMateri()

const kiri = ref<Revisi | null>(null)
const kanan = ref<Revisi | null>(null)
const memuatBanding = ref(false)
const galatBanding = ref('')

const memuatAjukan = ref(false)
const galatAjukan = ref('')

const pernahTerbit = computed(() => props.idVersiPublik !== null && props.idRevisi !== null)

const judulDialog = computed(() =>
  pernahTerbit.value ? 'Ajukan versi barumu supaya tayang?' : 'Ajukan materi ini supaya tayang?',
)

const deskripsiDialog = computed(() =>
  pernahTerbit.value
    ? 'Ini bedanya dengan versi yang terakhir tayang. Cek sekali lagi sebelum diajukan ya.'
    : 'Admin akan mengecek materimu dulu. Selama dicek, isinya belum bisa diubah.',
)

/** Ambil isi lengkap kedua versi lewat GET /materi/{id}/riwayat/{revisi} (KP-13.4). */
async function muatBanding() {
  if (!pernahTerbit.value) return

  memuatBanding.value = true
  galatBanding.value = ''

  try {
    const [publik, baru] = await Promise.all([
      versi(props.materiId, props.idVersiPublik as number),
      versi(props.materiId, props.idRevisi as number),
    ])

    kiri.value = publik.data
    kanan.value = baru.data
  } catch (galatServer) {
    galatBanding.value = pesanGalat(galatServer, 'Coba muat ulang ya.')
  } finally {
    memuatBanding.value = false
  }
}

// Setiap kali dialog dibuka, perbandingan dimuat ulang agar sesuai draf terbaru.
watch(terbuka, (buka) => {
  if (!buka) return

  galatAjukan.value = ''
  kiri.value = null
  kanan.value = null
  muatBanding()
})

async function ajukanSekarang() {
  memuatAjukan.value = true
  galatAjukan.value = ''

  try {
    const respons = await ajukan(props.materiId)

    terbuka.value = false
    emit('diajukan', respons)
  } catch (galatServer) {
    galatAjukan.value = pesanGalat(galatServer, 'Materinya belum berhasil diajukan. Coba lagi ya.')
  } finally {
    memuatAjukan.value = false
  }
}
</script>
