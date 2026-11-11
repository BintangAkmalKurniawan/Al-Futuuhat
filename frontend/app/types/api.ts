// Tipe data API. Bentuknya mengikuti app/Http/Resources di backend.

// ------------------------------------------------------------ Bentuk umum

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

/** Respons daftar berpaginasi: { data, links, meta }. */
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

/** Respons satu data: { data }. */
export interface ResponsData<T> {
  data: T
}

/** Bentuk galat validasi Laravel (HTTP 422). */
export interface GalatValidasi {
  message: string
  errors: Record<string, string[]>
}

/** Bentuk umum galat API; sebagian endpoint menambah kolom khusus. */
export interface GalatApi {
  message: string
  status_akun?: StatusAkun
  catatan?: string | null
  errors?: Record<string, string[]>
}

// ---------------------------------------------------------------- Pengguna

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

// ------------------------------------------------------ Struktur organisasi

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
  /** Draf 2.5: periode aktif dari tahun lalu; perubahan berikutnya memulai periode baru. */
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

/** Pengurus yang tampil di halaman publik (tanpa data pribadi). */
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

// ------------------------------------------- Riwayat struktur (Draf 2.5, KP-07.7–07.8)

export type AksiRiwayatStruktur = 'tambah' | 'ubah' | 'hapus' | 'periode_baru'

export interface PotretPengurus {
  nama: string | null
  jabatan: string | null
  divisi: string | null
}

/** Satu catatan log perubahan struktur. */
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
  /** null bila periode ini tidak punya pembanding (periode pertama). */
  perbandingan: PerbandinganStruktur | null
  log: LogStruktur[]
}

// ------------------------------------------------------------------ Materi

export type StatusMateri = 'draf' | 'diajukan' | 'terbit' | 'ditolak'
export type StatusRevisi = 'draf' | 'diajukan' | 'disetujui' | 'ditolak'
export type KeputusanVerifikasi = 'disetujui' | 'ditolak'

/** Delta Quill disimpan apa adanya (JSON). */
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
  /** Draf 2.3: bagian materi disimpan per versi (KP-12.4, KP-13.4). */
  judul: string
  ringkasan: string | null
  /** URL gambar sampul versi ini. */
  sampul: string | null
  /** Hanya ikut bila relasinya dimuat backend. */
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
    /** Terisi bila materi pernah terbit; dipakai subteks antrean admin. */
    diterbitkan_pada?: string | null
    penulis?: RingkasPengguna
  }
  verifikasi?: Verifikasi[]
}

/** Pengajuan penarikan materi dari publik (KF-20). */
export type StatusPenarikan = 'diajukan' | 'disetujui' | 'ditolak' | 'dibatalkan'

export interface Penarikan {
  id: number
  status: StatusPenarikan
  /** Alasan & catatan hanya dikirim kepada penulis dan admin (KP-20.7). */
  alasan?: string
  catatan?: string | null
  diajukan_pada: string | null
  diputuskan_pada: string | null
  /** Ringkasan materi; ada di antrean admin (KP-20.4). */
  materi?: {
    id: number
    judul: string
    slug: string
    status: StatusMateri
    penulis?: RingkasPengguna
  }
}

/** Kategori materi (KF-19). Semua materi wajib punya minimal satu kategori. */
export interface Kategori {
  id: number
  nama: string
  slug: string
  /** Jumlah materi pemakai; hanya dikirim di daftar admin (KP-19.1). */
  jumlah_materi?: number
}

export interface MateriRingkas {
  id: number
  judul: string
  slug: string
  ringkasan: string | null
  /** URL gambar sampul untuk thumbnail daftar (KF-18). */
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
  /** Kategori revisi terakhir untuk chip di baris daftar (KP-19.3, KP-19.4). */
  kategori?: Kategori[]
  /** Versi publik yang sedang tampil; null bila materi tidak terbit (KP-16.4). */
  versi_publik?: VersiPublik | null
  /** Versi publik terakhir, tetap ada setelah ditarik (KP-10.7). */
  versi_publik_terakhir?: VersiPublik | null
  /** Pengajuan penarikan yang menunggu (KF-20). */
  penarikan?: Penarikan | null
  /** Perkiraan waktu baca dari Resource (DESIGN.md §9). */
  menit_baca?: number | null
  dibuat_pada: string | null
  diperbarui_pada: string | null
}

/** Respons GET /materi/{id}/edit dan simpan materi. */
export interface DataEditor {
  materi: MateriRingkas
  /** Path sampul apa adanya (isi `sampul` di MateriRingkas berupa URL). */
  sampul_path: string | null
  /** Id kategori terpilih, untuk mencentang pilihan di editor (KP-19.2). */
  kategori_ids: number[]
  revisi: Revisi | null
  /** Versi publik terakhir untuk perbandingan sebelum mengajukan (KP-10.7). */
  versi_publik_terakhir: VersiPublik | null
}

/** Isi formulir editor Quill. */
export interface PayloadMateri {
  judul: string
  ringkasan?: string | null
  /** Path berkas hasil POST /unggah-sampul, bukan URL. */
  sampul?: string | null
  /** Id kategori terpilih; wajib minimal satu (KF-19, KP-19.2). */
  kategori: number[]
  konten: KontenQuill
  konten_html: string
  catatan_revisi?: string | null
}

export interface ResponsUnggahGambar {
  url: string
}

/** Respons POST /unggah-sampul (KF-18). */
export interface ResponsUnggahSampul {
  path: string
  url: string
}

/** Nomor versi yang sedang terbit untuk umum (KP-16.4). */
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
  /** Isi revisi terakhir yang ditampilkan di halaman anggota. */
  revisi?: Revisi | null
  /** URL sampul revisi terakhir. */
  sampul?: string | null
  /** Hanya terisi selama materi terbit (KP-16.4). */
  versi_publik?: VersiPublik | null
  /** Tetap ada setelah materi ditarik (KP-10.7). */
  versi_publik_terakhir?: VersiPublik | null
  penarikan?: Penarikan | null
  riwayat?: RevisiRingkas[]
  jumlah_komentar?: number
  /** Kategori materi untuk chip di halaman baca (KF-19, KP-19.4). */
  kategori?: Kategori[]
}

/** Respons GET /admin/revisi/{id}: revisi diajukan + versi publik pembanding (KP-11.2). */
export interface DataTinjauRevisi {
  revisi: Revisi
  versi_terbit: Revisi | null
}

// -------------------------------------------------------- Publikasi (KF-17)

/** Kartu daftar publikasi; sengaja tanpa data internal. */
export interface MateriPublikRingkas {
  id: number
  judul: string
  slug: string
  ringkasan: string | null
  /** URL gambar sampul kartu artikel (KF-18). */
  sampul: string | null
  penulis?: { nama: string }
  diterbitkan_pada: string | null
  /** Kategori artikel untuk chip di kartu (KF-19, KP-19.4). */
  kategori?: Kategori[]
  /** Perkiraan waktu baca dari Resource (DESIGN.md §9). */
  menit_baca?: number | null
}

export interface MateriPublikDetail extends MateriPublikRingkas {
  /** Isi versi publik (revisi disetujui terakhir). */
  konten_html: string | null
}

// ------------------------------------------------------------------ Forum

export interface Komentar {
  id: number
  materi_id: number
  parent_id: number | null
  isi: string
  gambar: string | null
  user?: RingkasPengguna
  /** Terisi hanya pada komentar induk: balasan diambil terpisah (KP-14.5). */
  balasan?: Komentar[]
  jumlah_balasan?: number
  dibuat_pada: string | null
}

// ------------------------------------------------------------------ Dasbor

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
  /** Antrean penarikan materi (KF-20). */
  penarikan_diajukan: number
  anggota_aktif: number
  materi_terbit: number
}

// ----------------------------------------------------------------- Notifikasi

/** Isi notifikasi (`data` pada tabel notifications) menurut jenisnya. */
export interface DataNotifikasi {
  tipe: 'materi_diajukan' | 'revisi_diverifikasi' | 'penarikan_diajukan' | 'penarikan_diputuskan' | 'komentar_baru'
  judul: string
  /** Nama penulis; hanya ada pada notifikasi untuk admin. */
  penulis?: string
  /** Hanya ada pada notifikasi hasil verifikasi. */
  keputusan?: KeputusanVerifikasi
  /** Catatan admin; hanya dikirim ke penulis materi. */
  catatan?: string | null
  /** Alasan penarikan; hanya pada `penarikan_diajukan` (KF-20). */
  alasan?: string
  /** Tidak ada pada notifikasi penarikan. */
  nomor_versi?: number
  materi_id: number
  revisi_id?: number
  penarikan_id?: number
  /** KF-23 (`komentar_baru`): `komentar` untuk penulis materi, `balasan` untuk yang dibalas. */
  jenis?: 'komentar' | 'balasan'
  materi_slug?: string
  komentar_id?: number
  pengirim?: string
  cuplikan?: string
  pesan: string
}

export interface Notifikasi {
  /** UUID notifikasi (tabel notifications bawaan Laravel). */
  id: string
  data: DataNotifikasi
  dibaca: boolean
  dibaca_pada: string | null
  dibuat_pada: string | null
}

// ------------------------------------------------------ Beranda pengunjung (KF-18)

/** Konten beranda yang diatur admin: cerita Al-Futuuhat dan kontak organisasi. */
export interface PengaturanBeranda {
  cerita_judul: string | null
  /** HTML cerita yang sudah disanitasi backend. */
  cerita_isi: string | null
  /** Halaman Tentang kami (KF-26): visi teks biasa, misi satu poin per baris. */
  visi?: string | null
  misi?: string | null
  /** Kontak organisasi di kaki halaman publik (KF-22); kolom kosong = null. */
  kontak?: KontakOrganisasi
  diperbarui_pada: string | null
}

export interface KontakOrganisasi {
  alamat: string | null
  /** Angka saja dengan awalan 62, siap dipakai di wa.me. */
  whatsapp: string | null
  email: string | null
  /** Nama akun tanpa "@". */
  instagram: string | null
}

// ------------------------------------------------------ Statistik admin (KF-25)

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
// ------------------------------------------------------ Program kegiatan (KF-21)

export type StatusProgram = 'aktif' | 'selesai'
export type JenisDokumentasi = 'foto' | 'video' | 'youtube'

/** Program pada daftar publik `/program`. PIC hanya berisi nama (KNF-02). */
export interface ProgramRingkas {
  id: number
  nama: string
  slug: string
  ringkasan: string | null
  jadwal: string | null
  /** URL gambar sampul; null bila tidak ada. */
  sampul: string | null
  status: StatusProgram
  pic: { nama: string | null }[]
  jumlah_foto?: number
  /** Video MP4 + tautan YouTube. */
  jumlah_video?: number
}

/** Halaman baca satu program: ditambah penjelasan rinci (HTML tersanitasi). */
export interface ProgramDetail extends ProgramRingkas {
  deskripsi: string
  diperbarui_pada: string | null
}

/** Program untuk halaman admin: PIC beserta id anggota untuk formulir. */
export interface ProgramAdmin extends Omit<ProgramRingkas, 'pic'> {
  deskripsi?: string
  pic: PicProgram[]
  diperbarui_pada: string | null
}

export interface PicProgram {
  anggota_id: number
  nama: string | null
}

/** Satu butir dokumentasi program (KP-21.3). */
export interface DokumentasiProgram {
  id: number
  jenis: JenisDokumentasi
  /** Berkas di server (foto/video) atau tautan menonton di YouTube. */
  url: string
  /** Alamat pemutar sematan YouTube; null untuk foto dan video MP4. */
  embed: string | null
  /** Gambar kecil untuk grid; null untuk video MP4. */
  thumbnail: string | null
  keterangan: string | null
  dibuat_pada: string | null
}
