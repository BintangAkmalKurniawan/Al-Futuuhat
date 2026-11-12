import type { Notifikasi, ResponsHalaman } from '~/types/api'

// Notifikasi dalam aplikasi
export function useNotifikasi() {
  const api = useApi()

  const daftar = (page = 1) =>
    api.get<ResponsHalaman<Notifikasi>>('/notifikasi', { query: { page } })

  const tandaiDibaca = (id: string) =>
    api.patch<{ message: string; data: Notifikasi }>(`/notifikasi/${id}/baca`)

  const tandaiSemuaDibaca = () =>
    api.patch<{ message: string; jumlah: number }>('/notifikasi/baca-semua')

  return { daftar, tandaiDibaca, tandaiSemuaDibaca }
}