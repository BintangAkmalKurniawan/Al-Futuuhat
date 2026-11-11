import tailwindcss from '@tailwindcss/vite'
import Aura from '@primeuix/themes/aura'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  // Aplikasi ini SPA: seluruh data diambil dari API Laravel (PRD §13).
  ssr: false,

  css: ['~/assets/css/main.css'],

  // PrimeVue hanya dipakai untuk komponen OrganizationChart pada bagan
  // struktur organisasi (DESIGN.md §9). Komponen lain memakai komponen
  // sendiri di components/ui.
  modules: ['@primevue/nuxt-module'],

  primevue: {
    // Impor manual di komponen bagan; tidak ada komponen PrimeVue lain.
    autoImport: false,
    components: { include: ['OrganizationChart'] },
    options: {
      ripple: false,
      // Label pembaca layar berbahasa Indonesia untuk tombol buka-tutup bagan.
      locale: {
        aria: { expand: 'Buka cabang', collapse: 'Tutup cabang' },
      },
      theme: {
        // Tema bawaan Aura; warnanya disesuaikan dengan DESIGN.md lewat
        // penimpaan token CSS di app/assets/css/main.css.
        preset: Aura,
        options: {
          // Aplikasi selalu bertema gelap; kelas .app-gelap dipasang pada
          // elemen <html> di bawah (DESIGN.md §9.2).
          darkModeSelector: '.app-gelap',
        },
      },
    },
  },

  vite: {
    plugins: [tailwindcss()],
  },

  // Nama komponen mengikuti nama berkas tanpa awalan folder,
  // sehingga components/ui/AppButton.vue dipakai sebagai <AppButton />.
  components: [{ path: '~/components', pathPrefix: false }],

  app: {
    head: {
      // .app-gelap = penanda mode gelap untuk tema PrimeVue (DESIGN.md §9.2).
      htmlAttrs: { lang: 'id', class: 'app-gelap' },
      title: 'Sistem Informasi Al-Futuuhat',
      meta: [
        {
          name: 'description',
          content: 'Sistem informasi keanggotaan dan pengelolaan materi Al-Futuuhat.',
        },
        // Tema gelap: warna bilah peramban di ponsel ikut gelap.
        { name: 'theme-color', content: '#121212' },
      ],
    },
  },

  runtimeConfig: {
    public: {
      // Dapat ditimpa lewat NUXT_PUBLIC_API_BASE di .env.
      apiBase: 'http://localhost:8000/api',
      // Dipakai pada F9 (forum real-time via Laravel Reverb). Samakan dengan
      // REVERB_APP_KEY di backend/.env; dapat ditimpa lewat NUXT_PUBLIC_REVERB_KEY.
      reverbKey: 'ylaloclb5ljzhsi9kjnc',
      reverbHost: 'localhost',
      reverbPort: 8080,
      reverbScheme: 'http',
    },
  },
})