import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare module '#app' {
  interface NuxtApp {
    $echo: Echo | null
  }
}

// Klien Laravel Echo
export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('token')

  if (!config.public.reverbKey) {
    return { provide: { echo: null } }
  }

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
        get Authorization() {
          return token.value ? `Bearer ${token.value}` : ''
        },
      },
    },
  })

  return { provide: { echo } }
})