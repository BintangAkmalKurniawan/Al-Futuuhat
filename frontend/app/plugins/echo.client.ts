import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare module '#app' {
  interface NuxtApp {
    $echo: Echo | null
  }
}

/**
 * Klien siaran Laravel Reverb untuk forum real-time (KF-15).
 * Bila kunci Reverb belum diatur, forum tetap berjalan tanpa pembaruan otomatis.
 */
export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('token')

  if (!config.public.reverbKey) {
    return { provide: { echo: null } }
  }

  // Laravel Echo memakai protokol Pusher; objeknya perlu tersedia di window.
  ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

  const echo = new Echo({
    broadcaster: 'reverb',
    key: config.public.reverbKey,
    wsHost: config.public.reverbHost,
    wsPort: Number(config.public.reverbPort),
    wssPort: Number(config.public.reverbPort),
    forceTLS: config.public.reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${config.public.apiBase}/broadcasting/auth`,
    auth: {
      headers: {
        Accept: 'application/json',
        // Dibaca saat otorisasi kanal, sehingga token hasil login terbaru
        // tetap terpakai tanpa perlu memuat ulang halaman.
        get Authorization() {
          return token.value ? `Bearer ${token.value}` : ''
        },
      },
    },
  })

  return { provide: { echo } }
})