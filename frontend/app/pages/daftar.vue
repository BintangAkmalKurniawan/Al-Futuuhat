<template>
  <div class="mx-auto max-w-2xl">
    <header class="border-b border-garis pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Daftar anggota</h1>
      <p class="mt-2 font-serif text-dek text-teks-lembut">
        Isi formulir di bawah ya. Setelah admin menyetujui pendaftaranmu, kamu bisa langsung masuk.
      </p>
    </header>

    <AppAlert v-if="pesan" variant="bahaya" title="Pendaftaran belum terkirim" class="mt-6">
      {{ pesan }}
    </AppAlert>

    <form novalidate class="mt-8 space-y-5" @submit.prevent="kirim">
      <AppInput
        v-model="form.nama"
        label="Nama lengkap"
        autocomplete="name"
        :error="galat.nama"
        required
      />

      <AppInput
        v-model="form.email"
        type="email"
        label="Email"
        autocomplete="email"
        :error="galat.email"
        required
      />

      <div class="grid gap-5 sm:grid-cols-2">
        <AppInput
          v-model="form.password"
          type="password"
          label="Kata sandi"
          autocomplete="new-password"
          hint="Minimal 8 karakter."
          :error="galat.password"
          required
        />

        <AppInput
          v-model="form.password_confirmation"
          type="password"
          label="Ulangi kata sandi"
          autocomplete="new-password"
          :error="galat.password_confirmation"
          required
        />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <AppInput
          v-model="form.no_hp"
          label="Nomor HP"
          autocomplete="tel"
          :error="galat.no_hp"
          required
        />

        <AppFileInput
          v-model="foto"
          label="Foto (opsional)"
          hint="JPG atau PNG, maksimal 2 MB."
          :error="galat.foto"
        />
      </div>

      <AppTextarea
        v-model="form.alamat"
        label="Alamat"
        :error="galat.alamat"
        required
      />

      <AppTextarea
        v-model="form.alasan_bergabung"
        label="Alasan bergabung"
        :error="galat.alasan_bergabung"
        required
      />

      <div class="flex flex-wrap items-center justify-between gap-4 border-t border-garis pt-6">
        <NuxtLink
          to="/masuk"
          class="text-sm text-teks-lembut transition-colors duration-200 hover:text-teks-kuat"
        >
          Sudah punya akun? Masuk
        </NuxtLink>

        <AppButton type="submit" :loading="memuat">Daftar</AppButton>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Daftar Anggota' })

const { daftar } = useAuth()

const form = reactive({
  nama: '',
  email: '',
  password: '',
  password_confirmation: '',
  no_hp: '',
  alamat: '',
  alasan_bergabung: '',
})

const foto = ref<File | null>(null)
const galat = reactive<Record<string, string>>({})
const pesan = ref('')
const memuat = ref(false)

function kosongkanGalat() {
  for (const kunci of Object.keys(galat)) delete galat[kunci]
}

// Periksa foto
function periksaFoto(): boolean {
  if (!foto.value) return true

  if (!['image/jpeg', 'image/png'].includes(foto.value.type)) {
    galat.foto = 'Fotonya harus JPG atau PNG ya.'
    return false
  }

  if (foto.value.size > 2 * 1024 * 1024) {
    galat.foto = 'Fotonya kebesaran, maksimal 2 MB ya.'
    return false
  }

  return true
}

async function kirim() {
  kosongkanGalat()
  pesan.value = ''

  if (!periksaFoto()) return

  const data = new FormData()
  data.append('nama', form.nama)
  data.append('email', form.email)
  data.append('password', form.password)
  data.append('password_confirmation', form.password_confirmation)
  data.append('no_hp', form.no_hp)
  data.append('alamat', form.alamat)
  data.append('alasan_bergabung', form.alasan_bergabung)

  if (foto.value) data.append('foto', foto.value)

  memuat.value = true

  try {
    await daftar(data)
    await navigateTo('/daftar/berhasil')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galat[kunci] = daftarPesan[0] ?? ''
    }

    if (Object.keys(isian).length === 0) {
      pesan.value = pesanGalat(galatServer)
    }
  } finally {
    memuat.value = false
  }
}
</script>
