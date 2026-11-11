<template>
  <!-- Profil sendiri (KF-24): data diri dan kata sandi. Dipakai anggota maupun
       admin, jadi teksnya netral (Panduan Bahasa UI). -->
  <div class="mx-auto w-full max-w-2xl">
    <header class="pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Profil</h1>
      <p class="mt-3 text-sm text-teks-lembut">Data diri dan kata sandi akun.</p>
    </header>

    <div v-if="memuat" class="animate-pulse space-y-4">
      <div class="h-11 rounded-lg bg-permukaan" />
      <div class="h-11 rounded-lg bg-permukaan" />
      <div class="h-24 rounded-lg bg-permukaan" />
    </div>

    <AppAlert v-else-if="galatMuat" variant="bahaya" title="Profil belum bisa dimuat">{{ galatMuat }}</AppAlert>

    <template v-else>
      <!-- Data diri -->
      <form novalidate class="border-t border-garis pt-6" @submit.prevent="simpanProfil">
        <h2 class="text-sm font-medium text-teks-kuat">Data diri</h2>

        <div class="mt-4 flex flex-wrap items-center gap-5">
          <div class="h-20 w-20 overflow-hidden rounded-full bg-permukaan ring-1 ring-garis">
            <img v-if="fotoTampil" :src="fotoTampil" alt="Foto profil" class="h-full w-full object-cover" />
            <span v-else class="grid h-full w-full place-items-center">
              <AvatarInisial :nama="form.nama" :id="user?.id" ukuran="besar" />
            </span>
          </div>

          <div v-if="punyaAnggota">
            <input ref="masukanFoto" type="file" accept="image/jpeg,image/png" class="hidden" @change="pilihFoto" />
            <div class="flex flex-wrap gap-2">
              <AppButton variant="sekunder" size="sm" @click="masukanFoto?.click()">
                {{ fotoTampil ? 'Ganti foto' : 'Pilih foto' }}
              </AppButton>
              <AppButton v-if="fotoTampil" variant="hantu" size="sm" @click="lepasFoto">Hapus foto</AppButton>
            </div>
            <p class="mt-1.5 text-[13px] text-teks-redup">JPG atau PNG, maksimal 2 MB. Hanya terlihat oleh pengurus.</p>
            <p v-if="galat.foto" class="pesan-galat" role="alert">{{ galat.foto }}</p>
          </div>
        </div>

        <div class="mt-6 space-y-4">
          <AppInput v-model="form.nama" label="Nama lengkap" :error="galat.nama" required />
          <AppInput :model-value="user?.email ?? ''" label="Email" hint="Email tidak dapat diubah." readonly />
          <template v-if="punyaAnggota">
            <AppInput v-model="form.no_hp" type="tel" label="Nomor HP" :error="galat.no_hp" required />
            <AppTextarea v-model="form.alamat" label="Alamat" :rows="3" :error="galat.alamat" required />
          </template>
        </div>

        <AppButton type="submit" class="mt-6" :loading="menyimpan">Simpan perubahan</AppButton>
      </form>

      <!-- Ganti kata sandi -->
      <form novalidate class="mt-10 border-t border-garis pt-6" @submit.prevent="simpanKataSandi">
        <h2 class="text-sm font-medium text-teks-kuat">Ganti kata sandi</h2>
        <p class="mt-1 text-[13px] text-teks-redup">Perangkat lain yang sedang masuk perlu masuk lagi setelah kata sandi diganti.</p>

        <div class="mt-4 space-y-4">
          <AppInput v-model="sandi.password_lama" type="password" label="Kata sandi lama" autocomplete="current-password" :error="galatSandi.password_lama" required />
          <AppInput v-model="sandi.password" type="password" label="Kata sandi baru" hint="Minimal 8 karakter." autocomplete="new-password" :error="galatSandi.password" required />
          <AppInput v-model="sandi.password_confirmation" type="password" label="Ulangi kata sandi baru" autocomplete="new-password" required />
        </div>

        <AppButton type="submit" variant="sekunder" class="mt-6" :loading="menyimpanSandi">Ganti kata sandi</AppButton>
      </form>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'anggota', middleware: ['auth'] })
useHead({ title: 'Profil' })

const { tampil, ubah, gantiKataSandi } = useProfil()
const { user, fetchMe } = useAuth()
const { tampilkan } = usePesanToast()

const memuat = ref(true)
const galatMuat = ref('')
const menyimpan = ref(false)
const menyimpanSandi = ref(false)

const punyaAnggota = ref(false)
const form = reactive({ nama: '', no_hp: '', alamat: '' })
const galat = ref<Record<string, string | undefined>>({})

// Foto: tersimpan (URL), berkas baru yang dipilih, atau ditandai untuk dihapus.
const masukanFoto = ref<HTMLInputElement | null>(null)
const fotoTersimpan = ref<string | null>(null)
const fotoBaru = ref<File | null>(null)
const pratinjau = ref('')
const hapusFoto = ref(false)

const fotoTampil = computed(() => pratinjau.value || (hapusFoto.value ? null : fotoTersimpan.value))

const sandi = reactive({ password_lama: '', password: '', password_confirmation: '' })
const galatSandi = ref<Record<string, string | undefined>>({})

function bersihkanPratinjau() {
  if (pratinjau.value) URL.revokeObjectURL(pratinjau.value)
  pratinjau.value = ''
}

function pilihFoto(event: Event) {
  const el = event.target as HTMLInputElement
  const berkas = el.files?.[0] ?? null
  el.value = ''
  if (!berkas) return

  galat.value = { ...galat.value, foto: undefined }

  if (!['image/jpeg', 'image/png'].includes(berkas.type)) {
    galat.value.foto = 'Foto harus berformat JPG atau PNG.'
    return
  }
  if (berkas.size > 2 * 1024 * 1024) {
    galat.value.foto = 'Foto terlalu besar. Ukuran maksimal 2 MB.'
    return
  }

  bersihkanPratinjau()
  fotoBaru.value = berkas
  pratinjau.value = URL.createObjectURL(berkas)
  hapusFoto.value = false
}

function lepasFoto() {
  if (fotoBaru.value) {
    bersihkanPratinjau()
    fotoBaru.value = null
    return
  }
  hapusFoto.value = true
}

async function muat() {
  memuat.value = true
  galatMuat.value = ''

  try {
    const respons = await tampil()
    form.nama = respons.data.nama
    form.no_hp = respons.data.anggota?.no_hp ?? ''
    form.alamat = respons.data.anggota?.alamat ?? ''
    fotoTersimpan.value = respons.data.anggota?.foto ?? null
    punyaAnggota.value = Boolean(respons.data.anggota)
  } catch (galatServer) {
    galatMuat.value = pesanGalat(galatServer)
  } finally {
    memuat.value = false
  }
}

async function simpanProfil() {
  menyimpan.value = true
  galat.value = {}

  const data = new FormData()
  data.append('nama', form.nama.trim())
  if (punyaAnggota.value) {
    data.append('no_hp', form.no_hp.trim())
    data.append('alamat', form.alamat.trim())
  }
  if (fotoBaru.value) data.append('foto', fotoBaru.value)
  if (hapusFoto.value) data.append('hapus_foto', '1')

  try {
    const respons = await ubah(data)

    fotoTersimpan.value = respons.data.anggota?.foto ?? null
    fotoBaru.value = null
    hapusFoto.value = false
    bersihkanPratinjau()
    await fetchMe() // nama di menu akun ikut diperbarui
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)
    galat.value = Object.fromEntries(Object.entries(isian).map(([kunci, pesan]) => [kunci, pesan[0]]))
    if (Object.keys(isian).length === 0) tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    menyimpan.value = false
  }
}

async function simpanKataSandi() {
  menyimpanSandi.value = true
  galatSandi.value = {}

  try {
    const respons = await gantiKataSandi({ ...sandi })
    sandi.password_lama = ''
    sandi.password = ''
    sandi.password_confirmation = ''
    tampilkan(respons.message, 'sukses')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)
    galatSandi.value = Object.fromEntries(Object.entries(isian).map(([kunci, pesan]) => [kunci, pesan[0]]))
    if (Object.keys(isian).length === 0) tampilkan(pesanGalat(galatServer), 'bahaya')
  } finally {
    menyimpanSandi.value = false
  }
}

onMounted(muat)
onBeforeUnmount(bersihkanPratinjau)
</script>
