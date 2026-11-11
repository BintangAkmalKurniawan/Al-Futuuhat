import type { StatusAkun, StatusMateri, StatusRevisi } from '~/types/api'

type VarianBadge = 'netral' | 'info' | 'sukses' | 'peringatan' | 'bahaya'

export function labelStatusAkun(status: StatusAkun): string {
  const label: Record<StatusAkun, string> = {
    menunggu: 'Menunggu persetujuan',
    aktif: 'Aktif',
    ditolak: 'Ditolak',
    nonaktif: 'Tidak aktif',
  }

  return label[status] ?? status
}

export function varianStatusAkun(status: StatusAkun): VarianBadge {
  const varian: Record<StatusAkun, VarianBadge> = {
    menunggu: 'peringatan',
    aktif: 'sukses',
    ditolak: 'bahaya',
    nonaktif: 'netral',
  }

  return varian[status] ?? 'netral'
}

export function labelStatusMateri(status: StatusMateri): string {
  const label: Record<StatusMateri, string> = {
    draf: 'Belum tayang',
    diajukan: 'Lagi dicek admin',
    terbit: 'Tayang untuk umum',
    ditolak: 'Belum disetujui',
  }

  return label[status] ?? status
}

export function varianStatusMateri(status: StatusMateri): VarianBadge {
  const varian: Record<StatusMateri, VarianBadge> = {
    draf: 'netral',
    diajukan: 'info',
    terbit: 'sukses',
    ditolak: 'bahaya',
  }

  return varian[status] ?? 'netral'
}

export function labelStatusRevisi(status: StatusRevisi): string {
  const label: Record<StatusRevisi, string> = {
    draf: 'Belum tayang',
    diajukan: 'Lagi dicek admin',
    disetujui: 'Pernah tayang',
    ditolak: 'Belum disetujui',
  }

  return label[status] ?? status
}