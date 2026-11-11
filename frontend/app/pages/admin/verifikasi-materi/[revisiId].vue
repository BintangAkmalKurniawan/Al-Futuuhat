<template>
  <!-- Tinjau revisi: bandingkan versi publik dengan revisi diajukan (KP-11.2, DESIGN.md §6.3 no. 24). -->
  <div>
    <NuxtLink to="/admin/verifikasi-materi" class="crumb">
      <ChevronLeft class="h-4 w-4" aria-hidden="true" />
      Pemeriksaan materi
    </NuxtLink>

    <AppAlert v-if="galat" variant="bahaya" title="Pengajuan tidak dapat dimuat" class="mb-6">
      {{ galat }}
    </AppAlert>

    <!-- Kerangka memuat: judul, baris meta, lalu dua kolom banding. -->
    <div v-if="memuat" class="animate-pulse space-y-5">
      <div class="h-7 w-2/3 rounded-full bg-permukaan" />
      <div class="h-3.5 w-1/2 rounded-full bg-permukaan" />
      <div class="h-56 w-full rounded-lg bg-permukaan" />
    </div>

    <template v-else-if="revisi">
      <div class="flex flex-wrap items-center gap-3.5">
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">
          {{ revisi.judul ?? revisi.materi?.judul ?? 'Materi' }}
        </h1>
        <StatusChip :status="revisi.status" :versi="revisi.nomor_versi" />
      </div>

      <p class="mt-2.5 text-sm text-teks-lembut">
        {{ revisi.materi?.penulis?.nama ?? 'Anggota' }} · diajukan
        {{ formatTanggalWaktu(revisi.dibuat_pada) }}
        <template v-if="revisi.catatan_revisi">
          · Catatan perubahan: <span class="text-teks">“{{ revisi.catatan_revisi }}”</span>
        </template>
      </p>

      <!-- Materi pernah terbit: perbandingan yang sama dengan dialog penulis (KP-11.2, KP-10.7).
           Belum pernah terbit: cukup satu kolom berisi revisi yang diajukan. -->
      <div class="mt-6 rounded-lg border border-garis p-6">
        <PerbandinganVersi
          v-if="versiTerbit"
          :kiri="versiTerbit"
          :kanan="revisi"
          :label-kiri="`Versi yang terakhir tayang · v${versiTerbit.nomor_versi}`"
          :label-kanan="`Versi yang diajukan · v${revisi.nomor_versi}`"
        />

        <template v-else>
          <p class="text-[13px] font-medium text-teks-kuat">Versi yang diajukan (belum pernah tayang)</p>
          <h2 class="mt-4 font-serif text-[26px] leading-snug font-bold text-teks-kuat">{{ revisi.judul }}</h2>
          <p v-if="revisi.ringkasan" class="mt-2 font-serif text-[17px] text-teks-lembut">{{ revisi.ringkasan }}</p>
          <ChipKategori v-if="revisi.kategori?.length" :daftar="revisi.kategori" class="mt-3" />
          <img
            v-if="revisi.sampul"
            :src="revisi.sampul"
            alt="Sampul materi"
            class="mt-4 aspect-[16/10] w-full max-w-md rounded-lg border border-garis object-cover"
          />
          <div class="mt-6 border-t border-garis pt-6">
            <IsiMateri :html="revisi.konten_html" />
          </div>
        </template>
      </div>

      <!-- Bilah keputusan menempel di bawah. -->
      <div class="decide">
        <span class="text-[13px] text-teks-redup">{{ catatanKeputusan }}</span>
        <span class="tumbuh" />

        <AppButton
          variant="bahaya"
          :disabled="!bisaDiputuskan || memproses"
          @click="modalTolak = true"
        >
          Tolak
        </AppButton>

        <AppButton :loading="memproses" :disabled="!bisaDiputuskan" @click="setujui">
          Setujui &amp; tayangkan
        </AppButton>
      </div>

      <!-- Dialog tolak: catatan wajib diisi (KP-11.4, layar 25). -->
      <AppModal
        v-model:open="modalTolak"
        title="Tolak pengajuan ini?"
        :deskripsi="deskripsiTolak"
      >
        <AppTextarea
          v-model="catatan"
          label="Catatan untuk penulis"
          placeholder="Tuliskan alasan penolakan..."
          :rows="5"
          :error="galatCatatan"
          required
        />

        <template #footer>
          <AppButton variant="hantu" :disabled="memproses" @click="tutupModal">
            Batal
          </AppButton>
          <AppButton variant="bahaya" :loading="memproses" @click="tolak">
            Tolak pengajuan
          </AppButton>
        </template>
      </AppModal>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft } from '@lucide/vue'
import type { Revisi } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Tinjau Pengajuan Publikasi' })

const route = useRoute()
const { tinjau, putuskan } = useVerifikasi()
const { tampilkan } = usePesanToast()

const revisi = ref<Revisi | null>(null)
const versiTerbit = ref<Revisi | null>(null)
const memuat = ref(true)
const galat = ref('')

const modalTolak = ref(false)
const catatan = ref('')
const galatCatatan = ref('')
const memproses = ref(false)

// KP-11.6: hanya revisi berstatus diajukan yang boleh diputuskan.
const bisaDiputuskan = computed(() => revisi.value?.status === 'diajukan')

const nomorVersi = computed(() => revisi.value?.nomor_versi ?? 0)

// Draf 2.3: materi yang diajukan tidak sedang terbit (sudah ditarik bila pernah terbit),
// jadi persetujuan selalu menerbitkannya kembali untuk umum (KP-11.3).
const catatanKeputusan = computed(() =>
  versiTerbit.value
    ? 'Jika disetujui, versi ini akan langsung tayang untuk umum menggantikan versi sebelumnya.'
    : 'Jika disetujui, materi ini akan langsung tayang untuk umum.',
)

const deskripsiTolak = computed(
  () =>
    'Materi tetap dapat dibaca dan didiskusikan anggota, tetapi tidak tayang untuk umum. Catatan Anda hanya dapat dibaca oleh penulis.',
)

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await tinjau(String(route.params.revisiId))
    revisi.value = respons.data.revisi
    versiTerbit.value = respons.data.versi_terbit
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

function tutupModal() {
  modalTolak.value = false
  galatCatatan.value = ''
}

async function setujui() {
  if (!revisi.value || memproses.value) return

  memproses.value = true

  try {
    const respons = await putuskan(revisi.value.id, 'disetujui')
    tampilkan(respons.message, 'sukses')
    await navigateTo('/admin/verifikasi-materi')
  } catch (galatServer) {
    tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    memproses.value = false
  }
}

async function tolak() {
  if (!revisi.value || memproses.value) return

  // KP-11.4: catatan wajib diisi sebelum penolakan dikirim.
  if (!catatan.value.trim()) {
    galatCatatan.value = 'Mohon isi catatan untuk penulis.'
    return
  }

  galatCatatan.value = ''
  memproses.value = true

  try {
    const respons = await putuskan(revisi.value.id, 'ditolak', catatan.value.trim())
    tampilkan(respons.message, 'sukses')
    await navigateTo('/admin/verifikasi-materi')
  } catch (galatServer) {
    // Galat validasi catatan dari server ditampilkan di dalam dialog.
    galatCatatan.value = galatIsian(galatServer, 'catatan') || pesanGalat(galatServer)
  } finally {
    memproses.value = false
  }
}

onMounted(muat)
</script>
