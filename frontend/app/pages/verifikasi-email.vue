<template>
  <div class="mx-auto max-w-baca py-10 text-center">
    <!-- Hasil konfirmasi -->
    <template v-if="berhasil">
      <span
        class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-terbit/12 text-terbit"
        aria-hidden="true"
      >
        <Check class="h-6 w-6" />
      </span>

      <h1 class="mt-6 font-serif text-halaman font-bold text-teks-kuat">
        {{ status === 'sudah' ? 'Emailmu sudah dikonfirmasi sebelumnya' : 'Emailmu sudah dikonfirmasi' }}
      </h1>

      <p class="mx-auto mt-4 max-w-md font-serif text-dek text-teks-lembut">
        Pendaftaranmu lanjut dicek admin. Kamu tinggal tunggu kabarnya, setelah disetujui kamu
        bisa langsung masuk.
      </p>

      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <AppButton @click="navigateTo('/masuk')">Ke halaman masuk</AppButton>
        <AppButton variant="sekunder" @click="navigateTo('/')">Kembali ke beranda</AppButton>
      </div>
    </template>

    <!-- Kirim ulang -->
    <template v-else>
      <span
        v-if="status === 'gagal'"
        class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-bahaya/12 text-bahaya"
        aria-hidden="true"
      >
        <TriangleAlert class="h-6 w-6" />
      </span>

      <h1 class="mt-6 font-serif text-halaman font-bold text-teks-kuat">
        {{ status === 'gagal' ? 'Tautannya sudah tidak berlaku' : 'Kirim ulang email konfirmasi' }}
      </h1>

      <p class="mx-auto mt-4 max-w-md font-serif text-dek text-teks-lembut">
        Masukkan email yang kamu pakai saat mendaftar. Kami kirim tautan konfirmasi yang baru.
      </p>

      <form novalidate class="mx-auto mt-8 max-w-md space-y-5 text-left" @submit.prevent="kirim">
        <AppAlert v-if="terkirim" variant="info" title="Cek emailmu">{{ terkirim }}</AppAlert>
        <AppAlert v-if="pesan" variant="bahaya">{{ pesan }}</AppAlert>

        <AppInput
          v-model="email"
          type="email"
          label="Email"
          autocomplete="email"
          :error="galat"
          required
        />

        <AppButton type="submit" class="w-full" :loading="memuat">Kirim ulang</AppButton>

        <p class="text-center text-sm">
          <NuxtLink to="/masuk" class="text-aksen hover:underline">Kembali ke halaman masuk</NuxtLink>
        </p>
      </form>
    </template>
  </div>
</template>

<script setup lang="ts">
import { Check, TriangleAlert } from '@lucide/vue'

definePageMeta({ layout: 'publik', middleware: ['guest'] })
useHead({ title: 'Konfirmasi Email' })

const { kirimUlangVerifikasi } = useAuth()
const route = useRoute()

const status = computed(() => String(route.query.status ?? ''))
const berhasil = computed(() => status.value === 'berhasil' || status.value === 'sudah')

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
    const respons = await kirimUlangVerifikasi(email.value)

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
