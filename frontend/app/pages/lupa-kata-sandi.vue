<template>
  <div class="mx-auto max-w-md">
    <header class="border-b border-garis pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Lupa kata sandi</h1>
      <p class="mt-2 text-sm text-teks-lembut">
        Masukkan emailmu, nanti kami kirim tautan untuk membuat kata sandi baru.
      </p>
    </header>

    <AppAlert v-if="terkirim" variant="info" title="Cek emailmu" class="mt-6">
      {{ terkirim }}
    </AppAlert>

    <AppAlert v-if="pesan" variant="bahaya" class="mt-6">{{ pesan }}</AppAlert>

    <form novalidate class="mt-6 space-y-5" @submit.prevent="kirim">
      <AppInput
        v-model="email"
        type="email"
        label="Email"
        autocomplete="username"
        :error="galat"
        required
      />

      <AppButton type="submit" class="w-full" :loading="memuat">Kirim tautan</AppButton>

      <p class="text-center text-sm">
        <NuxtLink to="/masuk" class="text-aksen hover:underline">Kembali ke halaman masuk</NuxtLink>
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Lupa Kata Sandi' })

const { lupaKataSandi } = useAuth()

const email = ref('')
const galat = ref('')
const pesan = ref('')
const terkirim = ref('')
const memuat = ref(false)

async function kirim() {
  galat.value = ''
  pesan.value = ''
  terkirim.value = ''
  memuat.value = true

  try {
    const respons = await lupaKataSandi(email.value)

    terkirim.value = respons.message
  } catch (galatServer) {
    galat.value = galatIsian(galatServer, 'email')

    if (!galat.value) {
      pesan.value = pesanGalat(galatServer)
    }
  } finally {
    memuat.value = false
  }
}
</script>
