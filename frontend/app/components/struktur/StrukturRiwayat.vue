<template>
  <!-- Tab Riwayat admin -->
  <div>
    <div class="flex flex-wrap items-end gap-3 border-t border-garis pt-6">
      <div v-if="periode.length > 0" class="w-full max-w-xs">
        <AppSelect v-model="periodeId" label="Periode" :opsi="opsiPeriode" />
      </div>
      <p class="max-w-xl text-[13px] text-teks-lembut">
        Periode lama hanya dapat dibaca. Riwayat ini juga dapat dilihat seluruh anggota di halaman
        Struktur.
      </p>
    </div>

    <AppAlert v-if="galat" variant="bahaya" title="Gagal memuat periode" class="mt-6">{{ galat }}</AppAlert>

    <div v-else-if="memuat" class="mt-6 h-32 animate-pulse rounded-lg bg-permukaan" />

    <AppEmptyState
      v-else-if="periode.length === 0"
      title="Belum ada periode."
      description="Periode pertama dibuat otomatis saat Anda menambahkan pengurus."
    />

    <PanelRiwayatStruktur v-else class="mt-8" :periode-id="periodeId ? Number(periodeId) : null" />
  </div>
</template>

<script setup lang="ts">
import type { Periode } from '~/types/api'

const { daftarPeriode } = useStruktur()

const periode = ref<Periode[]>([])
const periodeId = ref<number | string | null>(null)
const memuat = ref(true)
const galat = ref('')

const opsiPeriode = computed(() =>
  periode.value.map((item) => ({
    nilai: item.id,
    label: `${item.nama_periode}${item.is_aktif ? ' (saat ini)' : ''}`,
  })),
)

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await daftarPeriode()
    periode.value = respons.data
    periodeId.value = respons.data[0]?.id ?? null
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

onMounted(muat)
</script>
