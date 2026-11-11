<template>
  <!-- Tinjau satu pendaftar (KF-05). -->
  <div>
    <NuxtLink to="/admin/pendaftaran" class="crumb">
      <ChevronLeft class="h-4 w-4" aria-hidden="true" />
      Pendaftaran
    </NuxtLink>

    <div v-if="memuat" class="mt-8 animate-pulse space-y-4">
      <div class="h-8 w-72 rounded-full bg-permukaan" />
      <div class="h-4 w-52 rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
    </div>

    <AppAlert v-else-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-8">
      {{ galat }}
    </AppAlert>

    <template v-else-if="pendaftar">
      <!-- Judul + lencana status sebaris (DESIGN.md §6.3 no. 19). -->
      <div class="flex flex-wrap items-center gap-3.5 pt-2">
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">{{ pendaftar.nama }}</h1>
        <AppBadge :variant="varianStatusAkun(pendaftar.status)">
          {{ labelStatusAkun(pendaftar.status) }}
        </AppBadge>
      </div>

      <p class="mt-1.5 text-sm text-teks-lembut">
        Mendaftar {{ formatTanggalWaktu(pendaftar.terdaftar_pada) }}
      </p>

      <div class="mt-7 grid gap-12 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div>
          <!-- Daftar data pendaftar; alasan bergabung ditulis dengan serif. -->
          <dl class="dl">
            <div>
              <dt>Email</dt>
              <dd>{{ pendaftar.email }}</dd>
            </div>
            <div>
              <dt>Nomor HP</dt>
              <dd>{{ pendaftar.anggota?.no_hp || '—' }}</dd>
            </div>
            <div>
              <dt>Alamat</dt>
              <dd class="whitespace-pre-line">{{ pendaftar.anggota?.alamat || '—' }}</dd>
            </div>
            <div>
              <dt>Alasan bergabung</dt>
              <dd class="font-serif text-[17px] leading-relaxed whitespace-pre-line">
                {{ pendaftar.anggota?.alasan_bergabung || '—' }}
              </dd>
            </div>
            <div v-if="pendaftar.anggota?.catatan_verifikasi">
              <dt>Catatan admin</dt>
              <dd class="whitespace-pre-line">{{ pendaftar.anggota.catatan_verifikasi }}</dd>
            </div>
          </dl>

          <!-- Bilah keputusan menempel di bawah kolom kiri (DESIGN.md §6.3 no. 19). -->
          <div class="decide">
            <span class="text-[13px] text-teks-redup">{{ catatanKeputusan }}</span>
            <span class="tumbuh" />

            <template v-if="bisaPutuskan">
              <AppButton variant="bahaya" @click="bukaModalTolak">Tolak</AppButton>
              <AppButton @click="modalSetuju = true">Setujui pendaftaran</AppButton>
            </template>
          </div>
        </div>

        <!-- Foto pendaftar: persegi, satu kolom kanan (DESIGN.md §6.3 no. 19). -->
        <div>
          <a
            v-if="pendaftar.anggota?.foto"
            :href="pendaftar.anggota.foto"
            target="_blank"
            rel="noopener"
          >
            <img
              :src="pendaftar.anggota.foto"
              :alt="`Foto ${pendaftar.nama}`"
              class="aspect-square w-full rounded-lg border border-garis object-cover"
            />
          </a>
          <div v-else class="photo">Tidak ada foto</div>

          <p class="help text-center">
            {{ pendaftar.anggota?.foto ? 'Buka foto ukuran penuh' : 'Pendaftar tidak melampirkan foto' }}
          </p>
        </div>
      </div>
    </template>

    <!-- Modal setujui -->
    <AppModal
      v-model:open="modalSetuju"
      title="Setujui pendaftaran?"
      :deskripsi="`Akun ${pendaftar?.nama ?? 'pendaftar'} akan aktif dan dapat masuk.`"
    >
      <AppAlert v-if="galatModal" variant="bahaya" title="Gagal menyimpan">
        {{ galatModal }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">
        Pendaftar akan menerima kabar bahwa akunnya sudah aktif.
      </p>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatKeputusan" @click="modalSetuju = false">
          Batal
        </AppButton>
        <AppButton :loading="memuatKeputusan" @click="putuskanSekarang('disetujui')">
          Ya, setujui
        </AppButton>
      </template>
    </AppModal>

    <!-- Modal tolak -->
    <AppModal
      v-model:open="modalTolak"
      title="Tolak pendaftaran?"
      deskripsi="Alasan penolakan wajib diisi dan akan terlihat oleh pendaftar saat mencoba masuk."
    >
      <div class="space-y-3">
        <AppAlert v-if="galatModal" variant="bahaya" title="Gagal menyimpan">
          {{ galatModal }}
        </AppAlert>

        <AppTextarea
          v-model="catatan"
          label="Catatan penolakan"
          placeholder="Tuliskan alasan penolakan..."
          :error="galatCatatan"
          required
        />
      </div>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatKeputusan" @click="modalTolak = false">
          Batal
        </AppButton>
        <AppButton variant="bahaya" :loading="memuatKeputusan" @click="putuskanSekarang('ditolak')">
          Ya, tolak
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft } from '@lucide/vue'
import type { User } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Detail Pendaftar' })

const route = useRoute()
const { detailPendaftar, putuskan } = usePendaftaran()
const { tampilkan } = usePesanToast()
const id = route.params.id as string

const pendaftar = ref<User | null>(null)
const memuat = ref(true)
const galat = ref('')

const modalSetuju = ref(false)
const modalTolak = ref(false)
const catatan = ref('')
const galatCatatan = ref('')
const galatModal = ref('')
const memuatKeputusan = ref(false)

const bisaPutuskan = computed(() => pendaftar.value?.status === 'menunggu')

// Keterangan di bilah keputusan; berganti bila pendaftar sudah diputuskan.
const catatanKeputusan = computed(() =>
  bisaPutuskan.value
    ? 'Data pribadi ini hanya terlihat oleh pengurus.'
    : 'Pendaftar ini sudah diputuskan. Tidak ada tindakan yang tersedia.',
)

function bukaModalTolak() {
  galatCatatan.value = ''
  galatModal.value = ''
  modalTolak.value = true
}

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await detailPendaftar(id)
    pendaftar.value = respons.data
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

async function putuskanSekarang(keputusan: 'disetujui' | 'ditolak') {
  galatModal.value = ''
  galatCatatan.value = ''

  // KP-05.4: catatan wajib saat menolak (dicek juga di backend).
  if (keputusan === 'ditolak' && catatan.value.trim() === '') {
    galatCatatan.value = 'Mohon isi alasan penolakan.'
    return
  }

  memuatKeputusan.value = true

  try {
    const respons = await putuskan(
      id,
      keputusan,
      keputusan === 'ditolak' ? catatan.value : undefined,
    )

    modalSetuju.value = false
    modalTolak.value = false
    catatan.value = ''
    tampilkan(respons.message, 'sukses')

    await muat()
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    galatCatatan.value = isian.catatan?.[0] ?? ''
    galatModal.value = isian.keputusan?.[0] ?? pesanGalat(galatServer)
  } finally {
    memuatKeputusan.value = false
  }
}

onMounted(muat)
</script>