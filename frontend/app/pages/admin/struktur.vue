<template>
  <div>
    <header class="pb-6">
      <h1 class="font-serif text-halaman font-bold text-teks-kuat">Struktur organisasi</h1>
      <p class="mt-3 text-sm text-teks-lembut">
        Kelola susunan pengurus, divisi, dan jabatan Al-Futuuhat. Periode dibuat otomatis setiap
        tahun dan setiap perubahan tercatat di riwayat.
      </p>
    </header>

    <AppTabs v-model="tab" :tabs="daftarTab" />

    <div class="pt-6">
      <StrukturPengurus v-if="tab === 'pengurus'" />
      <StrukturDivisi v-else-if="tab === 'divisi'" />
      <StrukturJabatan v-else-if="tab === 'jabatan'" />
      <StrukturRiwayat v-else />
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: ['auth', 'admin'] })
useHead({ title: 'Struktur Organisasi' })

const route = useRoute()
const router = useRouter()

const daftarTab = [
  { kunci: 'pengurus', label: 'Pengurus' },
  { kunci: 'divisi', label: 'Divisi' },
  { kunci: 'jabatan', label: 'Jabatan' },
  { kunci: 'riwayat', label: 'Riwayat' },
]

const tab = ref(
  daftarTab.some((item) => item.kunci === route.query.tab) ? String(route.query.tab) : 'pengurus',
)

watch(tab, (nilai) => {
  router.replace({ query: nilai === 'pengurus' ? {} : { tab: nilai } })
})
</script>
