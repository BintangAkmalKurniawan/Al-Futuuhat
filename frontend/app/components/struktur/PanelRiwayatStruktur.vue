<template>
  <!-- Riwayat perubahan struktur satu periode (Draf 2.5, KP-07.8):
       perbandingan dengan periode sebelumnya dan log per aksi admin.
       Dipakai halaman anggota /struktur dan tab Riwayat di admin. -->
  <section>
    <AppAlert v-if="galat" variant="bahaya" title="Riwayat belum bisa dimuat">{{ galat }}</AppAlert>

    <div v-else-if="memuat" class="animate-pulse space-y-4">
      <div class="h-9 w-72 rounded-full bg-permukaan" />
      <div class="grid gap-4 sm:grid-cols-3">
        <div v-for="n in 3" :key="n" class="h-32 rounded-lg bg-permukaan" />
      </div>
    </div>

    <template v-else-if="riwayat">
      <AppTabs v-model="tab" :tabs="daftarTab" />

      <!-- Perbandingan dengan periode sebelumnya -->
      <div v-if="tab === 'perbandingan'" class="pt-6">
        <p v-if="!riwayat.perbandingan" class="text-sm text-teks-lembut">
          Periode {{ riwayat.periode.nama_periode }} adalah periode pertama, jadi belum ada periode
          sebelumnya untuk dibandingkan.
        </p>

        <template v-else>
          <p class="text-[13px] text-teks-redup">
            Periode {{ riwayat.periode.nama_periode }} dibanding periode
            {{ riwayat.periode_sebelumnya?.nama_periode }}.
          </p>

          <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-permukaan p-5 ring-1 ring-garis">
              <h3 class="flex items-center gap-2 text-sm font-medium text-teks-kuat">
                <UserPlus class="h-4 w-4 text-terbit" aria-hidden="true" />
                Masuk
                <span class="text-teks-redup">{{ riwayat.perbandingan.masuk.length }}</span>
              </h3>
              <ul v-if="riwayat.perbandingan.masuk.length" class="mt-3 space-y-2.5">
                <li v-for="(orang, i) in riwayat.perbandingan.masuk" :key="i">
                  <p class="text-sm text-teks">{{ orang.nama }}</p>
                  <p class="text-[13px] text-teks-redup">{{ orang.posisi }}</p>
                </li>
              </ul>
              <p v-else class="mt-3 text-[13px] text-teks-redup">Tidak ada.</p>
            </div>

            <div class="rounded-lg bg-permukaan p-5 ring-1 ring-garis">
              <h3 class="flex items-center gap-2 text-sm font-medium text-teks-kuat">
                <UserMinus class="h-4 w-4 text-bahaya" aria-hidden="true" />
                Keluar
                <span class="text-teks-redup">{{ riwayat.perbandingan.keluar.length }}</span>
              </h3>
              <ul v-if="riwayat.perbandingan.keluar.length" class="mt-3 space-y-2.5">
                <li v-for="(orang, i) in riwayat.perbandingan.keluar" :key="i">
                  <p class="text-sm text-teks">{{ orang.nama }}</p>
                  <p class="text-[13px] text-teks-redup">{{ orang.posisi }}</p>
                </li>
              </ul>
              <p v-else class="mt-3 text-[13px] text-teks-redup">Tidak ada.</p>
            </div>

            <div class="rounded-lg bg-permukaan p-5 ring-1 ring-garis">
              <h3 class="flex items-center gap-2 text-sm font-medium text-teks-kuat">
                <ArrowRightLeft class="h-4 w-4 text-aksen" aria-hidden="true" />
                Pindah jabatan
                <span class="text-teks-redup">{{ riwayat.perbandingan.pindah.length }}</span>
              </h3>
              <ul v-if="riwayat.perbandingan.pindah.length" class="mt-3 space-y-2.5">
                <li v-for="(orang, i) in riwayat.perbandingan.pindah" :key="i">
                  <p class="text-sm text-teks">{{ orang.nama }}</p>
                  <p class="text-[13px] text-teks-redup">
                    {{ orang.dari }} <span aria-hidden="true">→</span>
                    <span class="sr-only">menjadi</span> {{ orang.menjadi }}
                  </p>
                </li>
              </ul>
              <p v-else class="mt-3 text-[13px] text-teks-redup">Tidak ada.</p>
            </div>
          </div>
        </template>
      </div>

      <!-- Log perubahan, terbaru di atas -->
      <div v-else class="pt-2">
        <p v-if="riwayat.log.length === 0" class="pt-4 text-sm text-teks-lembut">
          Belum ada perubahan yang tercatat pada periode ini.
        </p>

        <ul v-else class="divide-y divide-garis">
          <li v-for="item in riwayat.log" :key="item.id" class="flex gap-3 py-4">
            <span
              class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-permukaan ring-1 ring-garis"
              :class="warnaAksi[item.aksi]"
            >
              <component :is="ikonAksi[item.aksi]" class="h-4 w-4" aria-hidden="true" />
            </span>
            <div class="min-w-0">
              <p class="text-sm text-teks">{{ item.keterangan }}</p>
              <p class="mt-0.5 text-[13px] text-teks-redup">
                {{ item.admin?.nama ?? 'Admin' }} · {{ formatTanggalWaktu(item.dibuat_pada) }}
              </p>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { ArrowRightLeft, CalendarPlus, Pencil, UserMinus, UserPlus } from '@lucide/vue'
import type { AksiRiwayatStruktur, RiwayatStruktur } from '~/types/api'

const props = defineProps<{ periodeId: number | null }>()

const { riwayatPeriode } = useStruktur()

const riwayat = ref<RiwayatStruktur | null>(null)
const memuat = ref(false)
const galat = ref('')
const tab = ref('perbandingan')

const daftarTab = computed(() => [
  { kunci: 'perbandingan', label: 'Dibanding periode sebelumnya' },
  { kunci: 'log', label: 'Log perubahan', jumlah: riwayat.value?.log.length ?? 0 },
])

const ikonAksi: Record<AksiRiwayatStruktur, unknown> = {
  tambah: UserPlus,
  ubah: Pencil,
  hapus: UserMinus,
  periode_baru: CalendarPlus,
}

const warnaAksi: Record<AksiRiwayatStruktur, string> = {
  tambah: 'text-terbit',
  ubah: 'text-aksen',
  hapus: 'text-bahaya',
  periode_baru: 'text-teks-lembut',
}

let nomorPermintaan = 0

async function muat(id: number) {
  const nomor = ++nomorPermintaan
  memuat.value = true
  galat.value = ''

  try {
    const respons = await riwayatPeriode(id)
    if (nomor === nomorPermintaan) riwayat.value = respons.data
  } catch (galatServer) {
    if (nomor === nomorPermintaan) galat.value = pesanGalat(galatServer)
  } finally {
    if (nomor === nomorPermintaan) memuat.value = false
  }
}

// Muat ulang setiap kali periode yang dipilih berganti.
watch(
  () => props.periodeId,
  (id) => {
    if (id) muat(id)
  },
  { immediate: true },
)

/** Dipanggil admin setelah mengubah susunan agar log langsung terbarui. */
function muatUlang() {
  if (props.periodeId) return muat(props.periodeId)
}

defineExpose({ muatUlang })
</script>
