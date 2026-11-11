/**
 * Memulihkan data pengguna dari token yang tersimpan saat aplikasi dimuat ulang.
 * Dengan begitu menu dan middleware langsung tahu peran pengguna.
 */
export default defineNuxtPlugin(async () => {
  const token = useCookie<string | null>('token')

  if (!token.value) return

  try {
    await useAuth().fetchMe()
  } catch {
    // Token tidak berlaku: useApi sudah menghapus cookie dan mengarahkan ke /masuk.
  }
})