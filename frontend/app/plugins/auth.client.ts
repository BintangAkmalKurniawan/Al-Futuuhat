// Pulihkan sesi login
export default defineNuxtPlugin(async () => {
  const token = useCookie<string | null>('token')

  if (!token.value) return

  try {
    await useAuth().fetchMe()
  } catch {
  }
})