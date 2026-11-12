<template>
  <!-- Kelola satu anggota -->
  <div>
    <NuxtLink to="/admin/anggota" class="crumb">
      <ChevronLeft class="h-4 w-4" aria-hidden="true" />
      Data anggota
    </NuxtLink>

    <div v-if="memuat" class="mt-8 animate-pulse space-y-4">
      <div class="h-8 w-64 rounded-full bg-permukaan" />
      <div class="h-4 w-52 rounded-full bg-permukaan" />
      <div class="h-3 w-full rounded-full bg-permukaan" />
    </div>

    <AppAlert v-else-if="galat" variant="bahaya" title="Gagal memuat data" class="mt-8">
      {{ galat }}
    </AppAlert>

    <template v-else-if="anggota">
      <div class="flex flex-wrap items-center gap-3.5 pt-2">
        <AvatarInisial :nama="anggota.nama" :id="anggota.id" ukuran="besar" />
        <h1 class="font-serif text-halaman font-bold text-teks-kuat">{{ anggota.nama }}</h1>
        <AppBadge :variant="varianStatusAkun(anggota.status)">
          {{ labelStatusAkun(anggota.status) }}
        </AppBadge>
      </div>

      <p class="mt-1.5 text-sm text-teks-lembut">
        Bergabung {{ formatTanggal(anggota.anggota?.tanggal_bergabung) }}
      </p>

      <div class="mt-7 grid gap-12 lg:grid-cols-[minmax(0,1fr)_340px]">
        <!-- Kolom kiri -->
        <div>
          <AppAlert v-if="galatSimpan" variant="bahaya" title="Gagal menyimpan" class="mb-6">
            {{ galatSimpan }}
          </AppAlert>

          <form novalidate @submit.prevent="simpan">
            <div class="space-y-5">
              <AppInput
                v-model="form.nama"
                label="Nama lengkap"
                :error="galatIsianData.nama"
                required
              />

              <div>
                <AppInput
                  :model-value="anggota.email"
                  label="Email"
                  readonly
                />
                <p class="help">Email tidak dapat diubah.</p>
              </div>

              <div class="grid gap-5 sm:grid-cols-2">
                <AppInput
                  v-model="form.no_hp"
                  label="Nomor HP"
                  :error="galatIsianData.no_hp"
                  required
                />

                <AppSelect
                  v-if="!diriSendiri"
                  v-model="form.role"
                  label="Peran"
                  :opsi="opsiPeran"
                  :error="galatIsianData.role"
                  required
                />
              </div>

              <p v-if="diriSendiri" class="help">Anda tidak dapat mengubah peran Anda sendiri.</p>

              <AppTextarea
                v-model="form.alamat"
                label="Alamat"
                :error="galatIsianData.alamat"
                required
              />
            </div>

            <div class="mt-6 flex items-center gap-2.5">
              <AppButton type="submit" :loading="memuatSimpan">Simpan perubahan</AppButton>
              <AppButton variant="hantu" :disabled="memuatSimpan" @click="batal">
                Batal
              </AppButton>
            </div>
          </form>
        </div>

        <!-- Kolom kanan -->
        <aside class="lg:sticky lg:top-24 lg:self-start">
          <div class="sidecard">
            <h4>Atur ulang kata sandi</h4>
            <p>
              Gunakan bila anggota lupa kata sandinya. Sampaikan kata sandi baru kepada anggota
              secara langsung.
            </p>
            <AppButton variant="sekunder" size="sm" @click="bukaModalSandi">
              <Key class="h-4 w-4" aria-hidden="true" />
              Atur ulang
            </AppButton>
          </div>

          <div class="sidecard">
            <h4>{{ aksiStatus === 'nonaktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</h4>
            <p>
              {{
                aksiStatus === 'nonaktif'
                  ? 'Anggota akan keluar dari semua perangkat dan tidak dapat masuk sampai diaktifkan kembali.'
                  : 'Anggota dapat masuk kembali dan memakai seluruh fitur setelah akun diaktifkan.'
              }}
            </p>

            <p v-if="diriSendiri" class="text-[13px] text-teks-redup">
              Anda tidak dapat menonaktifkan akun Anda sendiri.
            </p>
            <AppButton
              v-else
              :variant="aksiStatus === 'nonaktif' ? 'bahaya' : 'sekunder'"
              size="sm"
              @click="modalStatus = true"
            >
              {{ aksiStatus === 'nonaktif' ? 'Nonaktifkan' : 'Aktifkan' }}
            </AppButton>
          </div>
        </aside>
      </div>
    </template>

    <!-- Modal atur ulang kata sandi -->
    <AppModal
      v-model:open="modalSandi"
      title="Atur ulang kata sandi"
      :deskripsi="`Kata sandi baru untuk ${anggota?.nama ?? 'anggota'}.`"
    >
      <div class="space-y-5">
        <AppAlert v-if="galatSandi" variant="bahaya" title="Gagal menyimpan">
          {{ galatSandi }}
        </AppAlert>

        <AppInput
          v-model="sandi.password"
          type="password"
          label="Kata sandi baru"
          autocomplete="new-password"
          hint="Minimal 8 karakter."
          :error="galatIsianSandi.password"
          required
        />

        <AppInput
          v-model="sandi.password_confirmation"
          type="password"
          label="Ulangi kata sandi baru"
          autocomplete="new-password"
          :error="galatIsianSandi.password_confirmation"
          required
        />

        <p class="text-[13px] text-teks-redup">
          Anggota perlu masuk kembali setelah kata sandinya diubah.
        </p>
      </div>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatSandi" @click="modalSandi = false">
          Batal
        </AppButton>
        <AppButton :loading="memuatSandi" @click="simpanSandi">Simpan kata sandi</AppButton>
      </template>
    </AppModal>

    <!-- Modal ubah status -->
    <AppModal
      v-model:open="modalStatus"
      :title="aksiStatus === 'nonaktif' ? 'Nonaktifkan akun?' : 'Aktifkan akun?'"
      :deskripsi="
        aksiStatus === 'nonaktif'
          ? 'Anggota akan langsung keluar dan tidak dapat masuk. Akun dapat diaktifkan kembali kapan saja.'
          : 'Anggota dapat masuk kembali setelah akun diaktifkan.'
      "
    >
      <AppAlert v-if="galatStatus" variant="bahaya" title="Gagal mengubah status">
        {{ galatStatus }}
      </AppAlert>
      <p v-else class="text-sm text-teks-lembut">{{ anggota?.nama }}</p>

      <template #footer>
        <AppButton variant="hantu" :disabled="memuatStatus" @click="modalStatus = false">
          Batal
        </AppButton>
        <AppButton
          :variant="aksiStatus === 'nonaktif' ? 'bahaya' : 'utama'"
          :loading="memuatStatus"
          @click="ubahStatusSekarang"
        >
          {{ aksiStatus === 'nonaktif' ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, Key } from '@lucide/vue'
import type { Peran, User } from '~/types/api'

definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Kelola Anggota' })

const route = useRoute()
const { detailAnggota, ubahAnggota, ubahStatus, aturUlangSandi } = useAnggota()
const { user: penggunaMasuk } = useAuth()
const { tampilkan } = usePesanToast()
const id = route.params.id as string

const opsiPeran = [
  { nilai: 'anggota', label: 'Anggota' },
  { nilai: 'admin', label: 'Admin' },
]

const anggota = ref<User | null>(null)
const memuat = ref(true)
const galat = ref('')

const form = reactive({
  nama: '',
  no_hp: '',
  alamat: '',
  role: 'anggota' as Peran,
})

const galatData = reactive<Record<string, string[]>>({})
const galatSimpan = ref('')
const memuatSimpan = ref(false)

const sandi = reactive({ password: '', password_confirmation: '' })
const galatSandiData = reactive<Record<string, string[]>>({})
const galatSandi = ref('')
const memuatSandi = ref(false)
const modalSandi = ref(false)

const modalStatus = ref(false)
const galatStatus = ref('')
const memuatStatus = ref(false)

const diriSendiri = computed(() => Number(id) === penggunaMasuk.value?.id)
const aksiStatus = computed<'aktif' | 'nonaktif'>(() =>
  anggota.value?.status === 'aktif' ? 'nonaktif' : 'aktif',
)

const galatIsianData = computed(() => ({
  nama: galatData.nama?.[0] ?? '',
  no_hp: galatData.no_hp?.[0] ?? '',
  alamat: galatData.alamat?.[0] ?? '',
  role: galatData.role?.[0] ?? '',
}))

const galatIsianSandi = computed(() => ({
  password: galatSandiData.password?.[0] ?? '',
  password_confirmation: galatSandiData.password_confirmation?.[0] ?? '',
}))

function isiForm(data: User) {
  form.nama = data.nama
  form.no_hp = data.anggota?.no_hp ?? ''
  form.alamat = data.anggota?.alamat ?? ''
  form.role = data.role
}

function kosongkan(objek: Record<string, string[]>) {
  for (const kunci of Object.keys(objek)) delete objek[kunci]
}

// Tombol "Batal"
function batal() {
  kosongkan(galatData)
  galatSimpan.value = ''

  if (anggota.value) isiForm(anggota.value)
}

// Buka modal kata sandi
function bukaModalSandi() {
  sandi.password = ''
  sandi.password_confirmation = ''
  kosongkan(galatSandiData)
  galatSandi.value = ''
  modalSandi.value = true
}

async function muat() {
  memuat.value = true
  galat.value = ''

  try {
    const respons = await detailAnggota(id)
    anggota.value = respons.data
    isiForm(respons.data)
  } catch (galatServer) {
    galat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

async function simpan() {
  kosongkan(galatData)
  galatSimpan.value = ''
  memuatSimpan.value = true

  try {
    const respons = await ubahAnggota(id, { ...form })

    anggota.value = respons.data
    isiForm(respons.data)
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galatData[kunci] = daftarPesan
    }

    if (Object.keys(isian).length === 0) {
      galatSimpan.value = pesanGalat(galatServer)
    }
  } finally {
    memuatSimpan.value = false
  }
}

async function simpanSandi() {
  kosongkan(galatSandiData)
  galatSandi.value = ''

  if (sandi.password.length < 8) {
    galatSandiData.password = ['Kata sandi minimal 8 karakter.']
    return
  }

  if (sandi.password !== sandi.password_confirmation) {
    galatSandiData.password_confirmation = ['Konfirmasi kata sandi tidak sama.']
    return
  }

  memuatSandi.value = true

  try {
    const respons = await aturUlangSandi(id, sandi.password, sandi.password_confirmation)

    sandi.password = ''
    sandi.password_confirmation = ''
    modalSandi.value = false
    tampilkan(`${respons.message} Anggota perlu masuk kembali.`, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galatSandiData[kunci] = daftarPesan
    }

    if (Object.keys(isian).length === 0) {
      galatSandi.value = pesanGalat(galatServer)
    }
  } finally {
    memuatSandi.value = false
  }
}

async function ubahStatusSekarang() {
  galatStatus.value = ''
  memuatStatus.value = true

  try {
    const respons = await ubahStatus(id, aksiStatus.value)

    anggota.value = respons.data
    modalStatus.value = false
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    galatStatus.value = galatIsian(galatServer, 'status') || pesanGalat(galatServer)
  } finally {
    memuatStatus.value = false
  }
}

onMounted(muat)
</script>