<template>
  <div class="mx-auto max-w-md">
    <header class="border-b border-garis pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Masuk</h1>
      <p class="mt-2 text-sm text-teks-lembut">
        Masuk untuk menulis materi dan ikut berdiskusi.
      </p>
    </header>

    <AppAlert v-if="pesan" :variant="varianPesan" :title="judulPesan" class="mt-6">
      {{ pesan }}

      <p v-if="catatanAdmin" class="mt-2">
        <span class="font-medium">Catatan dari admin:</span> {{ catatanAdmin }}
      </p>
    </AppAlert>

    <form novalidate class="mt-6 space-y-5" @submit.prevent="kirim">
      <AppInput
        v-model="email"
        type="email"
        label="Email"
        autocomplete="username"
        :error="galat.email"
        required
      />

      <AppInput
        v-model="password"
        type="password"
        label="Kata sandi"
        autocomplete="current-password"
        :error="galat.password"
        required
      />

      <AppButton type="submit" class="w-full" :loading="memuat">Masuk</AppButton>

      <p class="text-center text-sm text-teks-lembut">
        Belum punya akun?
        <NuxtLink to="/daftar" class="text-aksen hover:underline">Daftar</NuxtLink>
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Masuk' })

const { login } = useAuth()

const email = ref('')
const password = ref('')
const galat = reactive<Record<string, string>>({})
const pesan = ref('')
const statusAkun = ref<string | null>(null)
const catatanAdmin = ref<string | null>(null)
const memuat = ref(false)

// Empat jenis pesan login (KP-04): kredensial salah, menunggu, ditolak, nonaktif.
const judulPesan = computed(() => {
  switch (statusAkun.value) {
    case 'menunggu':
      return 'Pendaftaranmu lagi dicek admin'
    case 'ditolak':
      return 'Pendaftaranmu belum disetujui'
    case 'nonaktif':
      return 'Akunmu sedang tidak aktif'
    default:
      return 'Belum bisa masuk'
  }
})

const varianPesan = computed<'info' | 'peringatan' | 'bahaya'>(() => {
  if (statusAkun.value === 'ditolak') return 'bahaya'
  if (statusAkun.value === 'menunggu') return 'info'

  return 'peringatan'
})

function kosongkanGalat() {
  for (const kunci of Object.keys(galat)) delete galat[kunci]
}

async function kirim() {
  kosongkanGalat()
  pesan.value = ''
  statusAkun.value = null
  catatanAdmin.value = null
  memuat.value = true

  try {
    const pengguna = await login(email.value, password.value)

    // KP-04.4: admin ke /admin, anggota ke /dasbor.
    await navigateTo(pengguna.role === 'admin' ? '/admin' : '/dasbor')
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    for (const [kunci, daftarPesan] of Object.entries(isian)) {
      galat[kunci] = daftarPesan[0] ?? ''
    }

    const data = dataGalat(galatServer)

    if (data?.status_akun) {
      statusAkun.value = data.status_akun
      catatanAdmin.value = data.catatan ?? null
      pesan.value = data.message
    } else if (Object.keys(isian).length === 0) {
      pesan.value = data?.message || 'Belum bisa masuk. Coba lagi ya.'
    }
  } finally {
    memuat.value = false
  }
}
</script>
