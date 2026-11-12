import tailwindcss from '@tailwindcss/vite'
import Aura from '@primeuix/themes/aura'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  ssr: false,

  css: ['~/assets/css/main.css'],

  modules: ['@primevue/nuxt-module'],

  primevue: {
    autoImport: false,
    components: { include: ['OrganizationChart'] },
    options: {
      ripple: false,
      locale: {
        aria: { expand: 'Buka cabang', collapse: 'Tutup cabang' },
      },
      theme: {
        preset: Aura,
        options: {
          darkModeSelector: '.app-gelap',
        },
      },
    },
  },

  vite: {
    plugins: [tailwindcss()],
  },

  components: [{ path: '~/components', pathPrefix: false }],

  app: {
    head: {
      htmlAttrs: { lang: 'id', class: 'app-gelap' },
      title: 'Sistem Informasi Al-Futuuhat',
      meta: [
        {
          name: 'description',
          content: 'Sistem informasi keanggotaan dan pengelolaan materi Al-Futuuhat.',
        },
        { name: 'theme-color', content: '#121212' },
      ],
    },
  },

  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api',
      reverbKey: 'ylaloclb5ljzhsi9kjnc',
      reverbHost: 'localhost',
      reverbPort: 8080,
      reverbScheme: 'http',
    },
  },
})