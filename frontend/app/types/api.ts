
// Bentuk umum

export interface TautanHalaman {
  url: string | null
  label: string
  active: boolean
}

export interface MetaHalaman {
  current_page: number
  from: number | null
  last_page: number
  links: TautanHalaman[]
  path: string
  per_page: number
  to: number | null
  total: number
}

// Respons daftar berpaginasi
export interface ResponsHalaman<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: MetaHalaman
}

// Respons satu data
export interface ResponsData<T> {
  data: T
}

// Galat validasi
export interface GalatValidasi {
  message: string
  errors: Record<string, string[]>
}

// Bentuk umum galat API
export interface GalatApi {
  message: string
  status_akun?: StatusAkun
  email_terkonfirmasi?: boolean
  catatan?: string | null
  errors?: Record<string, string[]>
}

// Pengguna

export type Peran = 'admin' | 'anggota'
export type StatusAkun = 'menunggu' | 'aktif' | 'ditolak' | 'nonaktif'

export interface Anggota {
  id: number
  no_hp: string | null
  alamat: string | null
  alasan_bergabung: string | null
  foto: string | null
  tanggal_bergabung: string | null
  catatan_verifikasi: string | null
  diverifikasi_pada: string | null
}

export interface User {
  id: number
  nama: string
  email: string
  email_terkonfirmasi: boolean
  role: Peran
  status: StatusAkun
  terdaftar_pada: string | null
  anggota?: Anggota | null
}

export interface RingkasPengguna {
  id: number
  nama: string
}

export interface ResponsLogin {
  token: string
  user: User
}

// Struktur organisasi

export interface Divisi {
  id: number
  nama_divisi: string
  deskripsi: string | null
}

export interface Jabatan {
  id: number
  nama_jabatan: string
  urutan: number
}

export interface Periode {
  id: number
  nama_periode: string
  tahun_mulai: number
  tahun_selesai: number
  is_aktif: boolean
  akan_berganti: boolean
}

export interface Kepengurusan {
  id: number
  anggota_id: number
  periode_id: number
  divisi_id: number | null
  jabatan_id: number
  nama?: string
  divisi?: Divisi | null
  jabatan?: Jabatan | null
}

// Pengurus publik
export interface PengurusPublik {
  nama: string
  jabatan: string
  divisi: string | null
}

export interface DivisiPublik extends Divisi {
  pengurus: PengurusPublik[]
}

export interface StrukturPublik {
  periode: Periode
  inti: PengurusPublik[]
  divisi: DivisiPublik[]
}

// Riwayat struktur

export type AksiRiwayatStruktur = 'tambah' | 'ubah' | 'hapus' | 'periode_baru'

export interface PotretPengurus {
  nama: string | null
  jabatan: string | null
  divisi: string | null
}

// Log struktur
export interface LogStruktur {
  id: number
  aksi: AksiRiwayatStruktur
  keterangan: string
  admin: { nama: string } | null
  data_sebelum: PotretPengurus | null
  data_sesudah: PotretPengurus | null
  dibuat_pada: string | null
}

export interface PerbandinganStruktur {
  masuk: { nama: string | null; posisi: string }[]
  keluar: { nama: string | null; posisi: string }[]
  pindah: { nama: string | null; dari: string; menjadi: string }[]
}

export interface RiwayatStruktur {
  periode: Periode
  periode_sebelumnya: Periode | null
  perbandingan: PerbandinganStruktur | null
  log: LogStruktur[]
}

// Materi

export type StatusMateri = 'draf' | 'diajukan' | 'terbit' | 'ditolak'
export type StatusRevisi = 'draf' | 'diajukan' | 'disetujui' | 'ditolak'
export type KeputusanVerifikasi = 'disetujui' | 'ditolak'

// Konten Quill
export type KontenQuill = Record<string, unknown>

export interface Verifikasi {
  id: number
  keputusan: KeputusanVerifikasi
  catatan: string | null
  admin?: RingkasPengguna
  dibuat_pada: string | null
}

export interface RevisiRingkas {
  id: number
  nomor_versi: number
  status: StatusRevisi
  catatan_revisi: string | null
  dibuat_pada: string | null
  verifikasi?: Verifikasi[]
}

export interface Revisi {
  id: number
  materi_id: number
  nomor_versi: number
  status: StatusRevisi
  judul: string
  ringkasan: string | null
  sampul: string | null
  kategori?: Kategori[]
  konten: KontenQuill
  konten_html: string
  catatan_revisi: string | null
  dibuat_pada: string | null
  materi?: {
    id: number
    judul: string
    slug: string
    status: StatusMateri
    diterbitkan_pada?: string | null
    penulis?: RingkasPengguna
  }
  verifikasi?: Verifikasi[]
}

// Penarikan materi
export type StatusPenarikan = 'diajukan' | 'disetujui' | 'ditolak' | 'dibatalkan'

export interface Penarikan {
  id: number
  status: StatusPenarikan
  alasan?: string
  catatan?: string | null
  diajukan_pada: string | null
  diputuskan_pada: string | null
  materi?: {
    id: number
    judul: string
    slug: string
    status: StatusMateri
    penulis?: RingkasPengguna
  }
}

// Kategori materi
export interface Kategori {
  id: number
  nama: string
  slug: string
  jumlah_materi?: number
}

export interface MateriRingkas {
  id: number
  judul: string
  slug: string
  ringkasan: string | null
  sampul: string | null
  status: StatusMateri
  penulis?: RingkasPengguna
  diterbitkan_pada: string | null
  nomor_versi?: number | null
  revisi_terakhir?: {
    id: number
    nomor_versi: number
    status: StatusRevisi
  } | null
  jumlah_komentar?: number
  kategori?: Kategori[]
  versi_publik?: VersiPublik | null
  versi_publik_terakhir?: VersiPublik | null
  penarikan?: Penarikan | null
  menit_baca?: number | null
  dibuat_pada: string | null
  diperbarui_pada: string | null
}

// Data editor
export interface DataEditor {
  materi: MateriRingkas
  sampul_path: string | null
  kategori_ids: number[]
  revisi: Revisi | null
  versi_publik_terakhir: VersiPublik | null
}

// Isi formulir editor Quill
export interface PayloadMateri {
  judul: string
  ringkasan?: string | null
  sampul?: string | null
  kategori: number[]
  konten: KontenQuill
  konten_html: string
  catatan_revisi?: string | null
}

export interface ResponsUnggahGambar {
  url: string
}

// Respons unggah sampul
export interface ResponsUnggahSampul {
  path: string
  url: string
}

// Versi publik
export interface VersiPublik {
  id: number
  nomor_versi: number
}

export interface MateriDetail {
  id: number
  judul: string
  slug: string
  ringkasan: string | null
  status: StatusMateri
  diterbitkan_pada: string | null
  penulis?: RingkasPengguna
  revisi?: Revisi | null
  sampul?: string | null
  versi_publik?: VersiPublik | null
  versi_publik_terakhir?: VersiPublik | null
  penarikan?: Penarikan | null
  riwayat?: RevisiRingkas[]
  jumlah_komentar?: number
  kategori?: Kategori[]
}

// Data tinjau revisi
export interface DataTinjauRevisi {
  revisi: Revisi
  versi_terbit: Revisi | null
}

// Kartu daftar publikasi
export interface MateriPublikRingkas {
  id: number
  judul: string
  slug: string
  ringkasan: string | null
  sampul: string | null
  penulis?: { nama: string }
  diterbitkan_pada: string | null
  kategori?: Kategori[]
  menit_baca?: number | null
}

export interface MateriPublikDetail extends MateriPublikRingkas {
  konten_html: string | null
}

// Forum

export interface Komentar {
  id: number
  materi_id: number
  parent_id: number | null
  isi: string
  gambar: string | null
  user?: RingkasPengguna
  balasan?: Komentar[]
  jumlah_balasan?: number
  dibuat_pada: string | null
}

// Dasbor

export interface DasborAnggota {
  materi: {
    draf: number
    diajukan: number
    terbit: number
    ditolak: number
    total: number
  }
  notifikasi_belum_dibaca: number
}

export interface DasborAdmin {
  pendaftar_menunggu: number
  revisi_diajukan: number
  penarikan_diajukan: number
  anggota_aktif: number
  materi_terbit: number
}

// Data notifikasi
export interface DataNotifikasi {
  tipe: 'materi_diajukan' | 'revisi_diverifikasi' | 'penarikan_diajukan' | 'penarikan_diputuskan' | 'komentar_baru'
  judul: string
  penulis?: string
  keputusan?: KeputusanVerifikasi
  catatan?: string | null
  alasan?: string
  nomor_versi?: number
  materi_id: number
  revisi_id?: number
  penarikan_id?: number
  jenis?: 'komentar' | 'balasan'
  materi_slug?: string
  komentar_id?: number
  pengirim?: string
  cuplikan?: string
  pesan: string
}

export interface Notifikasi {
  id: string
  data: DataNotifikasi
  dibaca: boolean
  dibaca_pada: string | null
  dibuat_pada: string | null
}

// Pengaturan beranda
export interface PengaturanBeranda {
  cerita_judul: string | null
  cerita_isi: string | null
  visi?: string | null
  misi?: string | null
  kontak?: KontakOrganisasi
  diperbarui_pada: string | null
}

export interface KontakOrganisasi {
  alamat: string | null
  whatsapp: string | null
  email: string | null
  instagram: string | null
}

// Statistik admin

export interface StatistikAdmin {
  materi_terpopuler: {
    id: number
    judul: string
    slug: string
    status: StatusMateri
    penulis: string | null
    jumlah_dibaca: number
  }[]
  anggota_teraktif: {
    id: number
    nama: string
    jumlah_materi: number
    jumlah_komentar: number
    total: number
  }[]
}
// Program kegiatan

export type StatusProgram = 'aktif' | 'selesai'
export type JenisDokumentasi = 'foto' | 'video' | 'youtube'

// Program ringkas
export interface ProgramRingkas {
  id: number
  nama: string
  slug: string
  ringkasan: string | null
  jadwal: string | null
  sampul: string | null
  status: StatusProgram
  pic: { nama: string | null }[]
  jumlah_foto?: number
  jumlah_video?: number
}

// Halaman baca satu program
export interface ProgramDetail extends ProgramRingkas {
  deskripsi: string
  diperbarui_pada: string | null
}

// Program untuk halaman admin
export interface ProgramAdmin extends Omit<ProgramRingkas, 'pic'> {
  deskripsi?: string
  pic: PicProgram[]
  diperbarui_pada: string | null
}

export interface PicProgram {
  anggota_id: number
  nama: string | null
}

// Satu butir dokumentasi program
export interface DokumentasiProgram {
  id: number
  jenis: JenisDokumentasi
  url: string
  embed: string | null
  thumbnail: string | null
  keterangan: string | null
  dibuat_pada: string | null
}
