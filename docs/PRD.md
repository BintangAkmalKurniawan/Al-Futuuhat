# PRD — Sistem Informasi Manajemen Keanggotaan dan Pengelolaan Materi Al-Futuuhat

| | |
|---|---|
| **Dokumen** | Product Requirements Document (PRD) |
| **Sumber** | Bab III skripsi "Rancang Bangun Sistem Informasi Manajemen Keanggotaan dan Pengelolaan Materi Berbasis Web pada Al-Futuuhat" |
| **Metode pengembangan** | RAD (*Requirements Planning → User Design → Construction → Cutover*) |
| **Pengujian** | *Black Box Testing* |
| **Status** | Draf 2.1 — 9 November 2026 (Draf 2: lingkungan pengembangan lokal + Dockerfile per layanan; Draf 2.1: alat bantu koding beralih dari Codex ke Claude Code) |

> **Untuk Claude Code:** dokumen ini adalah sumber kebenaran untuk *apa* yang dibangun. Aturan *cara* membangunnya ada di `CLAUDE.md`, `.claude/skills/backend-laravel/SKILL.md`, dan `.claude/skills/frontend-nuxt/SKILL.md`. Jangan menambah fitur di luar dokumen ini tanpa diminta.

---

## 1. Ringkasan

Aplikasi web untuk organisasi Al-Futuuhat yang:

1. menggantikan keanggotaan lisan (bergabung = dimasukkan ke grup WhatsApp) dengan **pendaftaran daring yang diverifikasi admin**;
2. menampilkan **struktur kepengurusan per periode**;
3. menjadi **arsip materi** yang ditulis anggota dengan editor Quill, melalui alur **pengajuan → verifikasi admin → terbit**, lengkap dengan **riwayat versi**;
4. menyediakan **forum diskusi *real-time*** pada setiap materi terbit.

## 2. Latar belakang masalah

- Keanggotaan hanya lisan; tidak ada data anggota yang tersimpan rapi.
- Informasi dan pengetahuan tersebar di grup WhatsApp, mudah tenggelam, dan sulit dicari kembali.
- Materi tulisan anggota tidak memiliki alur persetujuan maupun riwayat perubahan.
- Tim Media kesulitan memperoleh ide konten yang berasal dari pemikiran anggota.

## 3. Tujuan dan ukuran keberhasilan

| Tujuan | Ukuran keberhasilan |
|---|---|
| Pendaftaran anggota tercatat dan terverifikasi | Seluruh skenario uji UC-01, UC-02, UC-04 lulus |
| Struktur kepengurusan dapat dilihat per periode | Skenario uji UC-03 dan UC-06 lulus |
| Materi terarsip dengan alur publikasi dan riwayat versi | Skenario uji UC-07 s.d. UC-10 lulus |
| Diskusi materi tampil tanpa memuat ulang halaman | Skenario uji UC-11 lulus pada dua peramban berbeda |
| Materi dapat dicari kembali | Skenario uji UC-12 lulus |

## 4. Ruang lingkup

### Termasuk
Semua kebutuhan fungsional KF-01 s.d. KF-16 (bagian 7) dan kebutuhan nonfungsional KNF-01 s.d. KNF-09 (bagian 9). KNF-09 serta beberapa pendukung (unggah gambar editor, notifikasi, dasbor, atur ulang kata sandi oleh admin) belum tertulis di Bab III.

### Tidak termasuk (versi ini)
- Lupa/reset kata sandi lewat email (reset dilakukan admin lewat Data Anggota).
- Notifikasi email, WhatsApp, atau *push*; notifikasi hanya di dalam aplikasi.
- Aplikasi *mobile* native.
- Materi yang bisa dibaca publik/tamu. Materi hanya untuk anggota.
- Absensi, iuran/keuangan, sertifikat, kegiatan/agenda.
- Moderasi komentar oleh admin (lihat Keputusan Terbuka).
- Desain visual akhir (tema akan ditentukan kemudian; sementara gunakan Tailwind yang rapi dan netral).

## 5. Aktor dan hak akses

| Aktor | Deskripsi | Hak akses |
|---|---|---|
| **Pengunjung (calon anggota)** | Belum punya akun aktif | Melihat beranda, profil, dan struktur organisasi; mendaftar |
| **Anggota** | Akun berstatus `aktif`, peran `anggota` (termasuk anggota Tim Media) | Login/logout; melihat struktur; menulis, menyimpan, mengajukan, dan merevisi materi miliknya; mencari dan membaca materi terbit; berdiskusi; melihat notifikasi |
| **Admin (pengurus/ketua)** | Akun berstatus `aktif`, peran `admin`. Admin juga anggota | Semua hak anggota + memverifikasi pendaftaran; mengelola data anggota; mengelola periode, divisi, jabatan, dan susunan pengurus; memverifikasi publikasi materi |

## 6. Daftar use case

| ID | Use case | Aktor | KF |
|---|---|---|---|
| UC-01 | Mendaftar sebagai Anggota | Pengunjung | KF-02, KF-03 |
| UC-02 | Login | Anggota, Admin | KF-03, KF-04 |
| UC-03 | Melihat Profil dan Struktur Organisasi | Pengunjung, Anggota | KF-01 |
| UC-04 | Memverifikasi Pendaftaran Anggota | Admin | KF-05 |
| UC-05 | Mengelola Data Anggota | Admin | KF-06 |
| UC-06 | Mengelola Struktur Organisasi | Admin | KF-07 |
| UC-07 | Mengelola Materi | Anggota | KF-08, KF-09 |
| UC-08 | Mengajukan Publikasi Materi | Anggota | KF-10 |
| UC-09 | Memverifikasi Publikasi Materi | Admin | KF-11 |
| UC-10 | Merevisi Materi | Anggota (penulis) | KF-12, KF-13 |
| UC-11 | Berdiskusi pada Forum Materi | Anggota | KF-14, KF-15 |
| UC-12 | Mencari dan Membaca Materi | Anggota | KF-13, KF-16 |

## 7. Kebutuhan fungsional dan kriteria penerimaan

Setiap KF memiliki kriteria penerimaan (KP). KP inilah yang diuji dengan *Black Box Testing*.

### KF-01 — Profil dan struktur kepengurusan per periode
*Sistem menampilkan profil organisasi dan struktur kepengurusan berdasarkan periode.*
- KP-01.1: Beranda dapat dibuka tanpa login.
- KP-01.2: Struktur yang tampil pertama kali adalah periode yang `is_aktif = true`.
- KP-01.3: Pengguna dapat memilih periode lain dan struktur berubah sesuai periode tersebut.
- KP-01.4: Pengurus dikelompokkan: pengurus inti (tanpa divisi, mis. Ketua) di atas, lalu per divisi. Setiap divisi dapat dibuka-tutup dan menampilkan **semua** pengurusnya, diurutkan berdasarkan `jabatan.urutan`.
- KP-01.5: Data publik hanya berisi nama, jabatan, dan divisi (KNF-02). Nomor HP, alamat, email, alasan bergabung, dan foto **tidak** dikirim oleh API publik.

### KF-02 — Formulir pendaftaran
*Pengunjung mendaftar melalui formulir berisi nama, email, kata sandi, nomor HP, alamat, alasan bergabung, dan foto.*
- KP-02.1: Isian wajib: nama, email, kata sandi, konfirmasi kata sandi, nomor HP, alamat, alasan bergabung. Foto opsional (lihat Keputusan Terbuka).
- KP-02.2: Email harus valid dan belum terdaftar → jika sudah ada, tampil pesan "Email sudah terdaftar."
- KP-02.3: Kata sandi minimal 8 karakter dan harus sama dengan konfirmasinya.
- KP-02.4: Foto hanya JPG/PNG, maksimal 2 MB (KNF-03).
- KP-02.5: Pesan kesalahan tampil di bawah isian yang salah; data yang sudah diisi tidak hilang.

### KF-03 — Status menunggu verifikasi
*Sistem menyimpan pendaftar berstatus menunggu dan menolak login akun yang belum disetujui.*
- KP-03.1: Setelah daftar, tersimpan 1 baris `users` (`role = anggota`, `status = menunggu`) dan 1 baris `anggota`, dalam satu transaksi.
- KP-03.2: Pengguna diarahkan ke halaman "Pendaftaran terkirim" yang menjelaskan bahwa akun menunggu verifikasi.
- KP-03.3: Login akun `menunggu` ditolak dengan pesan "Pendaftaran Anda masih menunggu verifikasi admin."

### KF-04 — Login, logout, menu sesuai peran
- KP-04.1: Email/kata sandi salah → "Email atau kata sandi tidak sesuai." (tidak membedakan mana yang salah).
- KP-04.2: Akun `ditolak` → pesan penolakan **beserta alasan dari admin**.
- KP-04.3: Akun `nonaktif` → "Akun Anda dinonaktifkan. Hubungi pengurus."
- KP-04.4: Akun `aktif` → mendapat token; anggota diarahkan ke `/dasbor`, admin ke `/admin`.
- KP-04.5: Menu admin hanya tampil untuk admin. Anggota yang membuka URL admin diarahkan keluar, dan API mengembalikan 403.
- KP-04.6: Logout menghapus token yang dipakai.

### KF-05 — Verifikasi pendaftaran
- KP-05.1: Admin melihat daftar pendaftar berstatus `menunggu` (bisa juga difilter `ditolak`).
- KP-05.2: Admin melihat detail pendaftar (termasuk foto dan alasan bergabung).
- KP-05.3: Setujui → `users.status = aktif`, `anggota.tanggal_bergabung = hari ini`, `anggota.diverifikasi_pada = sekarang`.
- KP-05.4: Tolak → `catatan_verifikasi` **wajib**; `users.status = ditolak`.
- KP-05.5: Pendaftar yang sudah diputuskan tidak bisa diputuskan ulang (422).

### KF-06 — Data anggota
- KP-06.1: Admin melihat daftar anggota (paginasi), mencari berdasarkan nama/email, dan memfilter status.
- KP-06.2: Admin mengubah data anggota (nama, no HP, alamat) dan peran (`anggota`/`admin`).
- KP-06.3: Admin menonaktifkan dan mengaktifkan kembali akun. Saat dinonaktifkan, semua token akun itu dihapus.
- KP-06.4: Admin dapat mengatur ulang kata sandi anggota (pengganti fitur lupa kata sandi).
- KP-06.5: Admin tidak dapat menonaktifkan atau menurunkan peran dirinya sendiri.

### KF-07 — Struktur organisasi
- KP-07.1: CRUD periode; hanya **satu** periode boleh aktif (mengaktifkan satu periode menonaktifkan yang lain, dalam transaksi).
- KP-07.2: CRUD divisi (data awal 6 divisi) dan jabatan (dengan `urutan`).
- KP-07.3: Menambah pengurus: pilih anggota aktif, periode, divisi (boleh kosong untuk pengurus inti), dan jabatan.
- KP-07.4: Satu anggota tidak boleh tercatat dua kali pada periode yang sama.
- KP-07.5: Divisi/jabatan/periode yang masih dipakai di `kepengurusan` tidak dapat dihapus (pesan jelas, bukan error 500).

### KF-08 — Menulis materi (draf)
- KP-08.1: Anggota menulis judul, ringkasan (opsional), dan isi dengan editor Quill (teks berformat dan gambar).
- KP-08.2: Gambar di editor diunggah ke server (bukan base64) — JPG/PNG maks 2 MB.
- KP-08.3: Simpan → 1 baris `materi` (`status = draf`) + 1 baris `revisi_materi` (`nomor_versi = 1`, `status = draf`) dalam satu transaksi.
- KP-08.4: `konten` disimpan sebagai Delta Quill (JSONB) dan `konten_html` disimpan **setelah disanitasi**.

### KF-09 — Mengubah/menghapus draf
- KP-09.1: Penulis dapat mengubah materi selama revisi terakhirnya berstatus `draf`.
- KP-09.2: Penulis dapat menghapus materi hanya jika `materi.status = draf` dan materi belum pernah terbit.
- KP-09.3: Bukan penulis → 403.

### KF-10 — Mengajukan publikasi
- KP-10.1: Hanya penulis, dan hanya jika revisi terakhir berstatus `draf`.
- KP-10.2: Revisi terakhir → `diajukan`. Jika materi belum pernah terbit, `materi.status → diajukan`.
- KP-10.3: Semua admin aktif menerima notifikasi dalam aplikasi.
- KP-10.4: Revisi yang sedang `diajukan` tidak bisa diubah.

### KF-11 — Verifikasi publikasi
- KP-11.1: Admin melihat antrean revisi berstatus `diajukan` (terlama di atas).
- KP-11.2: Admin melihat isi revisi; jika materi sudah pernah terbit, admin dapat membandingkannya dengan versi terbit saat ini.
- KP-11.3: Setujui → revisi `disetujui`; `materi.status = terbit`; `diterbitkan_pada` diisi jika masih kosong.
- KP-11.4: Tolak → `catatan` wajib; revisi `ditolak`; jika materi belum pernah terbit, `materi.status = ditolak`. Jika sudah pernah terbit, materi tetap `terbit` dengan versi lama.
- KP-11.5: Setiap keputusan dicatat di `verifikasi_materi` dan penulis menerima notifikasi berisi keputusan dan catatannya.
- KP-11.6: Hanya revisi `diajukan` yang dapat diverifikasi (selain itu 422).

### KF-12 — Revisi oleh penulis
- KP-12.1: Hanya penulis yang dapat membuat revisi (selain itu 403).
- KP-12.2: Revisi baru hanya boleh dibuat jika revisi terakhir berstatus `disetujui` atau `ditolak` (tidak ada draf/pengajuan yang menggantung).
- KP-12.3: Revisi baru = baris baru di `revisi_materi`, `nomor_versi` = terakhir + 1, `status = draf`, isi awal disalin dari revisi terakhir.
- KP-12.4: Untuk materi yang sudah pernah terbit, judul dan ringkasan **tidak** dapat diubah lewat revisi (hanya isi). Versi terbit tetap tampil sampai revisi baru disetujui.
- KP-12.5: Revisi yang `disetujui` tidak pernah diubah atau dihapus (KNF-06).

### KF-13 — Riwayat revisi dan verifikasi
- KP-13.1: Halaman materi menampilkan daftar versi: nomor versi, status, tanggal, catatan revisi.
- KP-13.2: Setiap versi menampilkan riwayat keputusan admin (keputusan, catatan, nama admin, waktu).
- KP-13.3: Anggota umum hanya melihat versi yang `disetujui`; penulis dan admin melihat semua versi.

### KF-14 — Forum diskusi
- KP-14.1: Forum hanya ada pada materi berstatus `terbit`.
- KP-14.2: Anggota mengirim komentar berupa teks, gambar, atau keduanya (minimal salah satu). Gambar JPG/PNG maks 2 MB.
- KP-14.3: Anggota dapat membalas komentar (satu tingkat balasan).
- KP-14.4: Anggota hanya dapat menghapus komentarnya sendiri (403 untuk komentar orang lain). Menghapus komentar induk ikut menghapus balasannya.

### KF-15 — Real-time
- KP-15.1: Komentar/balasan baru tampil di peramban anggota lain yang membuka materi yang sama tanpa memuat ulang.
- KP-15.2: Komentar yang dihapus hilang dari peramban lain tanpa memuat ulang.
- KP-15.3: Kanal bersifat *private* (`materi.{id}`); hanya pengguna login berstatus `aktif` yang dapat berlangganan.

### KF-16 — Pencarian dan membaca
- KP-16.1: Anggota mencari materi `terbit` berdasarkan kata kunci (judul + isi versi terbit, *full-text search* PostgreSQL) dan/atau nama penulis.
- KP-16.2: Hasil berpaginasi dan menampilkan judul, ringkasan, penulis, tanggal terbit, jumlah komentar.
- KP-16.3: Tidak ada hasil → pesan "Materi tidak ditemukan."
- KP-16.4: Halaman baca menampilkan isi **versi terbit**, penulis, tanggal, nomor versi, riwayat versi, dan forum.

## 8. Aturan bisnis dan status

### 8.1 Status akun (`users.status`)
```
menunggu ──(admin setujui)──▶ aktif ◀──(admin aktifkan)──┐
    │                            │                       │
    └──(admin tolak + alasan)──▶ ditolak    aktif ──(admin nonaktifkan)──▶ nonaktif
```
Hanya `aktif` yang bisa login dan memakai API selain endpoint publik.

### 8.2 Status materi (`materi.status`) dan revisi (`revisi_materi.status`)

| Kejadian | revisi terakhir | materi (belum pernah terbit) | materi (sudah pernah terbit) |
|---|---|---|---|
| Buat materi | `draf` (v1) | `draf` | — |
| Ajukan | `draf → diajukan` | `→ diajukan` | tetap `terbit` |
| Admin setujui | `diajukan → disetujui` | `→ terbit` | tetap `terbit` (konten berganti ke versi ini) |
| Admin tolak | `diajukan → ditolak` | `→ ditolak` | tetap `terbit` (versi lama) |
| Buat revisi baru | baris baru `draf` (v+1) | `ditolak → draf` | tetap `terbit` |

- **Versi terbit** = revisi dengan `nomor_versi` terbesar yang berstatus `disetujui`.
- Dalam satu materi hanya boleh ada **satu** revisi `draf` atau `diajukan` pada satu waktu.
- Revisi `disetujui` dan `ditolak` bersifat tetap (tidak diubah) sebagai riwayat.

### 8.3 Notifikasi (dalam aplikasi, tabel `notifications` bawaan Laravel)
| Pemicu | Penerima | Isi |
|---|---|---|
| Materi diajukan | Semua admin aktif | judul materi, nama penulis, nomor versi |
| Revisi diverifikasi | Penulis | judul materi, keputusan, catatan |

## 9. Kebutuhan nonfungsional

| Kode | Aspek | Kebutuhan |
|---|---|---|
| KNF-01 | Keamanan | Kata sandi di-*hash* (bcrypt). API memakai token Laravel Sanctum. Setiap aksi diperiksa sesuai peran lewat *middleware* dan *policy*. |
| KNF-02 | Privasi | No HP, alamat, dan alasan bergabung hanya dapat dilihat admin. Halaman publik hanya menampilkan nama dan jabatan pengurus. |
| KNF-03 | Validasi berkas | Unggahan foto/gambar hanya JPG/PNG, maks 2 MB. |
| KNF-04 | Kemudahan penggunaan | Responsif di komputer dan ponsel (Tailwind CSS). |
| KNF-05 | Real-time | Komentar disiarkan melalui Laravel Reverb. |
| KNF-06 | Integritas data | *Foreign key* + transaksi PostgreSQL; versi materi yang disetujui tidak dapat diubah. |
| KNF-07 | Portabilitas | Nuxt, Laravel, Reverb, dan PostgreSQL dapat dijalankan sebagai *container* Docker: *backend* dan *frontend* masing-masing memiliki Dockerfile, dan seluruh layanan dijalankan bersama dengan satu perintah `docker compose up --build`. |
| KNF-08 | Keterpeliharaan | Git + GitHub; setiap *endpoint* diuji dengan Postman sebelum diintegrasikan ke *frontend*. |
| KNF-09 | Performa query | Tidak ada query N+1. Semua relasi yang ditampilkan di daftar dimuat dengan *eager loading*; *lazy loading* dimatikan di lingkungan pengembangan. |

## 10. Model data

Semua tabel memakai `id BIGSERIAL` dan `created_at`/`updated_at`. Nama tabel **berbahasa Indonesia dan tunggal** sesuai ERD (model Laravel wajib menulis `protected $table`).

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `users` | nama, email (unique), password, role (`admin`/`anggota`), status (`menunggu`/`aktif`/`ditolak`/`nonaktif`) | 1:1 anggota; 1:N materi (penulis_id); 1:N komentar |
| `anggota` | user_id (unique), no_hp, alamat, alasan_bergabung, foto?, tanggal_bergabung?, catatan_verifikasi?, diverifikasi_pada? | N:1 users; 1:N kepengurusan |
| `periode_kepengurusan` | nama_periode, tahun_mulai, tahun_selesai, is_aktif | 1:N kepengurusan |
| `divisi` | nama_divisi (unique), deskripsi? | 1:N kepengurusan |
| `jabatan` | nama_jabatan (unique), urutan | 1:N kepengurusan |
| `kepengurusan` | anggota_id, periode_id, divisi_id? (null = pengurus inti), jabatan_id; unique(anggota_id, periode_id) | N:1 ke keempatnya |
| `materi` | penulis_id → users, judul, slug (unique), ringkasan?, status (`draf`/`diajukan`/`terbit`/`ditolak`), diterbitkan_pada? | 1:N revisi_materi; 1:N komentar |
| `revisi_materi` | materi_id, nomor_versi (unique bersama materi_id), konten (JSONB Delta), konten_html, catatan_revisi?, status (`draf`/`diajukan`/`disetujui`/`ditolak`) | 1:N verifikasi_materi |
| `verifikasi_materi` | revisi_id → revisi_materi, admin_id → users, keputusan (`disetujui`/`ditolak`), catatan? | — |
| `komentar` | materi_id, user_id, parent_id? → komentar (cascade), isi?, gambar? | rekursif (balasan) |

Tabel bawaan: `personal_access_tokens` (Sanctum), `notifications`.

Indeks tambahan:
- GIN *full-text* `to_tsvector('simple', judul)` pada `materi`.
- GIN *full-text* `to_tsvector('simple', regexp_replace(konten_html, '<[^>]+>', ' ', 'g'))` pada `revisi_materi`.
- Indeks biasa pada setiap kolom *foreign key* dan kolom `status`.

Data awal (*seeder*):
- 1 akun admin (ketua) dari variabel `.env` (`ADMIN_NAMA`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`) beserta baris `anggota`.
- 6 divisi: Advokasi, Bidang Sosial, Finance, Edukasi, Dewan Fatwa, Media.
- Jabatan awal: Ketua (urutan 1), Koordinator (urutan 2). Jabatan lain ditambah admin.
- *Factory* + *seeder* data contoh hanya untuk lingkungan lokal.

## 11. Kontrak API

Basis URL: `/api`. Semua respons JSON. Data memakai API Resource Laravel (`{ "data": ... }`, paginasi dengan `meta` dan `links`). Error validasi memakai format bawaan Laravel (422: `{ message, errors }`).

Keterangan akses: **P** = publik, **A** = anggota aktif (termasuk admin), **Adm** = admin.

### Autentikasi
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| POST | `/register` | P | multipart; KF-02, KF-03 |
| POST | `/login` | P | → `{ token, user }`; 403 berisi `status_akun` dan `catatan` jika tidak aktif |
| POST | `/logout` | A | hapus token saat ini |
| GET | `/me` | A | pengguna yang sedang login |

### Publik — struktur organisasi
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/divisi` | P | daftar divisi |
| GET | `/periode` | P | daftar periode (aktif ditandai) |
| GET | `/periode/{periode}/struktur` | P | pengurus inti + per divisi (hanya nama, jabatan, divisi) |

### Admin — pendaftaran & anggota
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/admin/pendaftaran?status=menunggu` | Adm | KF-05 |
| GET | `/admin/pendaftaran/{user}` | Adm | detail pendaftar |
| PATCH | `/admin/pendaftaran/{user}` | Adm | `{ keputusan: disetujui\|ditolak, catatan? }` |
| GET | `/admin/anggota?q=&status=&page=` | Adm | KF-06 |
| GET | `/admin/anggota/{user}` | Adm | |
| PUT | `/admin/anggota/{user}` | Adm | ubah data & peran |
| PATCH | `/admin/anggota/{user}/status` | Adm | `{ status: aktif\|nonaktif }` |
| PATCH | `/admin/anggota/{user}/password` | Adm | atur ulang kata sandi |

### Admin — struktur organisasi
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| POST/PUT/DELETE | `/admin/periode`, `/admin/periode/{periode}` | Adm | KF-07 |
| PATCH | `/admin/periode/{periode}/aktifkan` | Adm | hanya satu aktif |
| POST/PUT/DELETE | `/admin/divisi`, `/admin/divisi/{divisi}` | Adm | |
| GET/POST/PUT/DELETE | `/admin/jabatan`, `/admin/jabatan/{jabatan}` | Adm | |
| POST/PUT/DELETE | `/admin/kepengurusan`, `/admin/kepengurusan/{kepengurusan}` | Adm | |

### Materi
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/materi?q=&penulis=&page=` | A | hanya `terbit`; KF-16 |
| GET | `/materi/{slug}` | A | detail versi terbit; KP-16.4 |
| GET | `/materi/{materi}/riwayat` | A | versi + verifikasi; KF-13 |
| GET | `/materi-saya?status=` | A | semua materi milik pengguna |
| POST | `/materi` | A | buat materi + v1 draf; KF-08 |
| GET | `/materi/{materi}/edit` | A (penulis) | revisi terakhir |
| PUT | `/materi/{materi}` | A (penulis) | ubah draf; KF-09 |
| DELETE | `/materi/{materi}` | A (penulis) | hanya draf belum pernah terbit |
| POST | `/materi/{materi}/ajukan` | A (penulis) | KF-10 |
| POST | `/materi/{materi}/revisi` | A (penulis) | revisi baru; KF-12 |
| POST | `/unggah-gambar` | A | gambar untuk editor Quill → `{ url }` |

### Admin — verifikasi materi
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/admin/verifikasi-materi` | Adm | antrean revisi `diajukan` |
| GET | `/admin/revisi/{revisi}` | Adm | isi revisi + versi terbit pembanding |
| POST | `/admin/revisi/{revisi}/verifikasi` | Adm | `{ keputusan, catatan? }`; KF-11 |

### Forum
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/materi/{materi}/komentar?page=` | A | komentar induk + balasan |
| POST | `/materi/{materi}/komentar` | A | multipart `{ isi?, gambar?, parent_id? }` |
| DELETE | `/komentar/{komentar}` | A (pemilik) | |
| POST | `/broadcasting/auth` | A | otorisasi kanal Reverb |

*Event* di kanal privat `materi.{id}`: `komentar.dibuat` (data = KomentarResource), `komentar.dihapus` (`{ id, parent_id }`).

### Notifikasi & dasbor
| Metode | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/notifikasi` | A | notifikasi pengguna (paginasi) |
| PATCH | `/notifikasi/{id}/baca` | A | tandai dibaca |
| PATCH | `/notifikasi/baca-semua` | A | |
| GET | `/dasbor` | A | ringkasan pribadi (jumlah materi per status, notifikasi belum dibaca) |
| GET | `/admin/dasbor` | Adm | jumlah pendaftar menunggu, revisi diajukan, anggota aktif, materi terbit |

## 12. Halaman frontend (Nuxt)

| Route | Layout | Akses | Isi |
|---|---|---|---|
| `/` | publik | P | profil, divisi, struktur per periode (akordeon), cara bergabung |
| `/daftar` | publik | tamu | formulir pendaftaran |
| `/daftar/berhasil` | publik | tamu | status menunggu verifikasi |
| `/masuk` | publik | tamu | login + 4 jenis pesan (KP-04) |
| `/dasbor` | anggota | A | ringkasan & pintasan |
| `/materi` | anggota | A | arsip + pencarian |
| `/materi/[slug]` | anggota | A | baca materi + riwayat versi + forum *real-time* |
| `/materi-saya` | anggota | A | daftar materi milik sendiri per status |
| `/materi-saya/baru` | anggota | A | editor Quill |
| `/materi-saya/[id]/edit` | anggota | A (penulis) | editor Quill (draf/revisi) |
| `/struktur` | anggota | A | struktur organisasi |
| `/notifikasi` | anggota | A | daftar notifikasi |
| `/admin` | admin | Adm | dasbor admin |
| `/admin/pendaftaran`, `/admin/pendaftaran/[id]` | admin | Adm | verifikasi pendaftaran |
| `/admin/anggota`, `/admin/anggota/[id]` | admin | Adm | data anggota |
| `/admin/struktur` | admin | Adm | tab Periode, Divisi, Jabatan, Pengurus |
| `/admin/verifikasi-materi`, `/admin/verifikasi-materi/[revisiId]` | admin | Adm | antrean & keputusan |

Layout: `publik` (header + footer), `anggota` (menu di atas), `admin` (sidebar kiri). Desain visual detail menyusul.

## 13. Arsitektur dan lingkungan

```
[Nuxt (SPA, ssr:false)] ──REST JSON + Bearer token──▶ [Laravel API] ──▶ [PostgreSQL]
          ▲                                               │
          └──────── WebSocket (laravel-echo) ◀── [Laravel Reverb] ◀── broadcast event
```

Struktur repositori:
```
/
├─ CLAUDE.md               ← aturan proyek, dibaca Claude Code setiap sesi
├─ docs/PRD.md
├─ docs/ROADMAP.md
├─ .claude/
│  ├─ settings.json        ← izin perintah
│  └─ skills/              ← backend-laravel, frontend-nuxt, fitur (/fitur)
├─ backend/                ← Laravel
│  ├─ Dockerfile           ← php:8.4-apache; dipakai layanan backend & reverb
│  ├─ .dockerignore
│  └─ .env.docker.example  ← disalin menjadi .env.docker (tidak di-commit)
├─ frontend/               ← Nuxt
│  ├─ Dockerfile           ← build dua tahap, menjalankan .output/server/index.mjs
│  └─ .dockerignore
└─ docker-compose.yml      ← db, backend, reverb, frontend
```

### 13.1 Lingkungan pengembangan (sehari-hari) — lokal di Windows
- PHP 8.4, Composer, dan Node.js 24 dari **Laravel Herd** (versi PHP disamakan dengan Dockerfile).
- PostgreSQL memakai kontainer Docker yang sudah ada di laptop (`postgres-umum`, `127.0.0.1:5432`), basis data `alfutuuhat`.
- Dijalankan dengan tiga terminal: `php artisan serve` (port 8000), `php artisan reverb:start` (port 8080), `npm run dev` (port 3000).
- `backend/.env`: `DB_HOST=127.0.0.1`, `REVERB_HOST=localhost`. `frontend/.env`: `NUXT_PUBLIC_API_BASE=http://localhost:8000/api` dan `NUXT_PUBLIC_REVERB_*`.

### 13.2 Lingkungan Docker (demo, sidang, *deploy*) — `docker compose up -d --build`
| Layanan | Image | Port laptop | Keterangan |
|---|---|---|---|
| `db` | `postgres:16-alpine` | — (tidak dibuka) | volume `pgdata` |
| `backend` | dari `backend/Dockerfile` (Apache) | 8000 → 80 | volume `storage` untuk unggahan; `.env.docker` |
| `reverb` | image yang sama dengan `backend` | 8080 | `php artisan reverb:start` |
| `frontend` | dari `frontend/Dockerfile` | 3000 | `NUXT_PUBLIC_*` dibaca saat kontainer berjalan |

- Antarkontainer memakai nama layanan (`DB_HOST=db`, `REVERB_HOST=reverb`); konfigurasi yang dibaca peramban memakai `localhost`.
- Image *backend* dibangun dengan `composer install --no-dev`, sehingga *factory*/data contoh tidak tersedia di Docker; *seeder* yang memanggil *factory* harus dibatasi ke lingkungan `local`.
- Migrasi pertama: `docker compose exec -u www-data backend php artisan migrate --seed --force`.
- `.dockerignore` wajib mengecualikan `vendor/`, `node_modules/`, `.env*`, dan `bootstrap/cache/*.php` milik laptop.
- Nama berkas harus sama persis dengan nama *class* (huruf besar/kecil), karena Linux di dalam kontainer membedakannya.
- Tidak memakai *queue worker*: *event* memakai `ShouldBroadcastNow` dan notifikasi dikirim langsung (sinkron) agar sederhana.

## 14. Urutan pembangunan (tugas untuk Claude Code)

Kerjakan **satu tugas per sesi Claude Code** (ketik `/clear` sebelum tugas berikutnya). Setelah setiap tugas: jalankan migrasi/tes, uji di Postman, lalu *commit*.

| # | Tugas | Selesai jika |
|---|---|---|
| T1 | Kerangka repo: Laravel di `backend/`, Nuxt di `frontend/`, berjalan lokal (§13.1); Dockerfile + `.dockerignore` *backend* dan *frontend*, `docker-compose.yml`, `.env.docker.example`, README cara menjalankan (lokal dan Docker) | Ketiga proses lokal berjalan, dan `docker compose up --build` menjalankan keempat layanan |
| T2 | Migrasi, model, relasi, *factory*, *seeder* sesuai bagian 10 | `php artisan migrate:fresh --seed` sukses |
| T3 | Backend autentikasi & pendaftaran (KF-02 s.d. KF-05) | KP-02 s.d. KP-05 lulus di Postman |
| T4 | Backend data anggota & struktur organisasi (KF-01, KF-06, KF-07) | KP-01, KP-06, KP-07 lulus |
| T5 | Backend materi, revisi, verifikasi, notifikasi, unggah gambar (KF-08 s.d. KF-13) | KP-08 s.d. KP-13 lulus |
| T6 | Backend forum + Reverb + pencarian (KF-14 s.d. KF-16) | KP-14 s.d. KP-16 lulus |
| T7 | Frontend fondasi: `useApi`, `useAuth`, *middleware*, layout, plugin Echo, tipe data | login/logout berjalan |
| T8 | Frontend publik: beranda, daftar, masuk | KP-01 s.d. KP-04 lulus di peramban |
| T9 | Frontend admin: pendaftaran, anggota, struktur | KP-05 s.d. KP-07 lulus |
| T10 | Frontend materi: editor Quill, materi saya, ajukan, revisi, verifikasi admin | KP-08 s.d. KP-13 lulus |
| T11 | Frontend arsip, baca, forum *real-time*, notifikasi | KP-14 s.d. KP-16 lulus |
| T12 | Penyempurnaan: responsif, pesan kosong/muat/error, koleksi Postman, daftar skenario Black Box | semua KP lulus |

Contoh prompt untuk Claude Code (ganti nomor tugas; cara yang disarankan adalah `/fitur` sesuai `docs/ROADMAP.md`):
```
Baca docs/PRD.md. Kerjakan tugas T3 dari bagian 14 PRD saja.
Gunakan skill backend-laravel. Sebelum menulis kode, tampilkan rencana singkat
(file yang akan dibuat/diubah). Setelah selesai, jalankan tes, jelaskan cara
mengujinya di Postman, dan sebutkan KP mana saja yang sudah terpenuhi.
```

## 15. Rencana pengujian (Black Box)

Setiap KP pada bagian 7 menjadi minimal satu skenario uji dengan format:

| No | KF/KP | Skenario | Data uji | Hasil yang diharapkan | Hasil | Status |
|---|---|---|---|---|---|---|

Skenario wajib mencakup jalur normal dan pengecualian (data tidak valid, akses bukan pemilik → 403, akses bukan admin → 403, status tidak sesuai → 422, berkas > 2 MB atau bukan JPG/PNG).

## 16. Keputusan terbuka (asumsi sementara)

Asumsi berikut dipakai sampai Anda memutuskan lain. Ubah di sini jika berbeda.

| No | Pertanyaan | Asumsi sementara |
|---|---|---|
| 1 | Foto pendaftaran wajib atau opsional? | Opsional |
| 2 | Pendaftar yang ditolak boleh daftar ulang dengan email sama? | Tidak; menghubungi pengurus (admin dapat mengaktifkan dari Data Anggota) |
| 3 | Tambahan isian pendaftaran (tgl lahir, divisi minat)? | Tidak; sesuai KF-02 |
| 4 | Admin boleh menghapus komentar anggota lain (moderasi)? | Tidak; sesuai KF-14 |
| 5 | Kedalaman balasan komentar | Satu tingkat |
| 6 | Kategori/tag materi (mis. per divisi)? | Tidak ada (tidak ada di ERD) |
| 7 | Nama jabatan selain Ketua dan Koordinator | Ditambah admin melalui menu Jabatan |
| 8 | Tema visual | Ditentukan kemudian; sementara Tailwind netral |
