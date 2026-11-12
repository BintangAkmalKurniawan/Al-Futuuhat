<template>
  <div class="mx-auto max-w-md">
    <header class="border-b border-garis pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Buat kata sandi baru</h1>
      <p v-if="email" class="mt-2 text-sm text-teks-lembut">Untuk akun {{ email }}</p>
    </header>

    <AppAlert v-if="tautanRusak" variant="bahaya" title="Tautannya tidak bisa dipakai" class="mt-6">
      {{ tautanRusak }}
      <p class="mt-2">
        <NuxtLink to="/lupa-kata-sandi" class="font-medium underline">Minta tautan baru</NuxtLink>
      </p>
    </AppAlert>

    <AppAlert v-if="pesan" variant="bahaya" class="mt-6">{{ pesan }}</AppAlert>

    <form v-if="token && email" novalidate class="mt-6 space-y-5" @submit.prevent="kirim">
      <AppInput
        v-model="password"
        type="password"
        label="Kata sandi baru"
        autocomplete="new-password"
        :error="galat.password"
        required
      />

      <AppInput
        v-model="konfirmasi"
        type="password"
        label="Ulangi kata sandi"
        autocomplete="new-password"
        :error="galat.password_confirmation"
        required
      />

      <AppButton type="submit" class="w-full" :loading="memuat">Simpan kata sandi</AppButton>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Buat Kata Sandi Baru' })

const { aturUlangKataSandi } = useAuth()
const route = useRoute()

const token = String(route.query.token ?? '')
const email = String(route.query.email ?? '')

const password = ref('')
const konfirmasi = ref('')
const galat = reactive<Record<string, string>>({})
const pesan = ref('')
const tautanRusak = ref(
  token && email ? '' : 'Tautannya tidak lengkap. Coba minta tautan baru ya.',
)
const memuat = ref(false)

async function kirim() {
  for (const kunci of Object.keys(galat)) delete galat[kunci]
  pesan.value = ''
  tautanRusak.value = ''
  memuat.value = true

  try {
    await aturUlangKataSandi({
      token,
      email,
      password: password.value,
      password_confirmation: konfirmasi.value,
    })

    await navigateTo({ path: '/masuk', query: { sandi: 'diganti' } })
  } catch (galatServer) {
    const isian = galatValidasi(galatServer)

    if (isian.token || isian.email) {
      tautanRusak.value = isian.token?.[0] ?? isian.email?.[0] ?? ''
    }

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
