# Peta Jalan Koding Per Fitur — Sistem Al-Futuuhat

|            |                                                                                                                                                                                                                                                                                                                |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dasar**  | `PRD-Sistem-Al-Futuuhat.md` / `docs/PRD.md` (Draf 2.2, 10 November 2026)                                                                                                                                                                                                                                       |
| **Metode** | RAD — fase _User Design_ ↔ _Construction_ diulang per fitur                                                                                                                                                                                                                                                    |
| **Status** | Draf 2.2 — 10 November 2026 (Draf 2: F0 memakai pengembangan lokal + Dockerfile per layanan; Draf 2.1: Claude Code menggantikan Codex; **Draf 2.2: urutan F5–F12 disusun ulang — materi internal & forum dikerjakan sebelum publikasi untuk umum; tambah batal pengajuan; catatan admin hanya untuk penulis**) |

> Dokumen ini menjelaskan **urutan** dan **skenario** pengerjaan. _Apa_ yang dibangun tetap mengacu ke PRD. Kode KF/KP/UC di bawah merujuk ke PRD.

---

## 1. Pendekatan: fondasi dulu, lalu per fitur (_vertical slice_)

Ada tiga cara umum memulai:

| Pendekatan                 | Cara                                                                                               | Kelebihan                                                                           | Kekurangan                                                                                                       |
| -------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| **Per lapisan** (PRD §14)  | Semua _backend_ selesai → baru semua _frontend_                                                    | Fokus satu teknologi dalam satu waktu                                               | Baru ada yang bisa dilihat/didemokan setelah berminggu-minggu; kesalahan desain API baru ketahuan saat integrasi |
| **Per fitur murni**        | Langsung fitur 1 dari nol, termasuk menyiapkan proyek di dalamnya                                  | Cepat terlihat hasil                                                                | Fondasi (Docker, token, layout) dibuat setengah-setengah lalu dirombak                                           |
| **Fondasi + per fitur** ✅ | Siapkan fondasi sekali, lalu setiap fitur dikerjakan tuntas _backend_ → Postman → _frontend_ → uji | Setiap fitur bisa didemokan; sesuai siklus RAD; mudah melapor progres ke pembimbing | Perlu disiplin: jangan pindah fitur sebelum KP lulus                                                             |

**Rekomendasi: Fondasi + per fitur.** Alasannya:

1. **Sesuai RAD.** Fase _User Design_ dan _Construction_ pada RAD berjalan berulang dengan umpan balik pengguna. Mengerjakan per fitur membuat setiap modul bisa ditunjukkan ke pengurus (mis. Fauzi/Ivan), lalu diperbaiki sebelum lanjut. _(Jika ini ditulis di skripsi, sertakan rujukan RAD — [SITASI DIPERLUKAN].)_
2. **Desain dibahas per modul.** Tata letak dan alur setiap halaman didiskusikan tepat sebelum modul itu dibangun.
3. **Risiko integrasi kecil.** Kesalahan kontrak API langsung ketahuan saat _frontend_ fitur yang sama dikerjakan.
4. **Bahan Bab IV dan pengujian terkumpul bertahap.** Setiap fitur selesai = tangkapan layar + tabel _Black Box_ bagian itu sudah terisi.

---

## 2. Siklus kerja yang diulang di setiap fitur

```
 ┌─ 1. Diskusi layout & alur halaman (User Design)
 │  2. Buat branch git: fitur/<nama>
 │  3. Backend: validasi (FormRequest), policy, controller, resource, route
 │  4. Uji setiap endpoint di Postman → simpan ke koleksi
 │  5. Frontend: composable → halaman → komponen
 │  6. Uji Black Box di peramban → isi tabel skenario
 │  7. Commit, merge ke main, catat di log implementasi
 └─ 8. (Opsional) demo ke pengurus → umpan balik → kembali ke 1 jika perlu
```

**Aturan penting**

- **Satu fitur = satu branch.** Untuk Claude Code: satu sesi untuk _backend_, satu sesi untuk _frontend_ fitur yang sama (`/clear` di antaranya).
- **Jangan lanjut ke fitur berikutnya** sebelum semua KP fitur tersebut lulus.
- **Pahami kode yang dihasilkan Claude Code.** Saat sidang, penguji dapat menanyakan potongan kode mana pun. Bacalah setiap _controller_ dan _policy_ sebelum _commit_.
- **Catat log implementasi** (tanggal, fitur, KP yang lulus, kendala, solusi). Ini bahan langsung untuk Bab IV.
- **Uji Docker di setiap milestone (M1–M4).** Pengembangan sehari-hari berjalan lokal; jalankan `docker compose up -d --build` setiap milestone agar galat khusus Linux (mis. huruf besar/kecil nama berkas) ketahuan lebih awal.

---

## 3. Urutan fitur dan ketergantungannya

```
F0 Fondasi
 └─▶ F1 Pendaftaran & Login
      └─▶ F2 Verifikasi Pendaftaran
           └─▶ F3 Data Anggota ............................ ◆ M1: modul keanggotaan selesai
                └─▶ F4 Struktur Organisasi (admin + beranda publik) ◆ M2
                     └─▶ F5 Materi: Tulis & Kelola
                          └─▶ F6 Arsip Anggota, Pencarian & Baca
                               └─▶ F7 Forum Diskusi Real-time ...... ◆ M3: materi & diskusi internal selesai
                                    └─▶ F8 Pengajuan & Verifikasi Publikasi
                                         └─▶ F9 Revisi & Riwayat Versi
                                              └─▶ F10 Halaman Publikasi untuk Umum ◆ M4
                                                   └─▶ F11 Notifikasi & Dasbor
                                                        └─▶ F13 Konten Beranda & Sampul Artikel (KF-18)
                                                             └─▶ F14 Kategori Materi (KF-19)
                                                                  └─▶ F15 Penarikan Materi & Bagian Materi per Versi (KF-20)
                                                                       └─▶ F16 Program & Dokumentasi (KF-21)
                                                                            └─▶ F17 Periode Otomatis & Riwayat Struktur (KF-01, KF-07)
                                                                                 └─▶ F18 Kenyamanan Pengguna (KF-22–KF-25)
                                                                                      └─▶ F12 Penyempurnaan & Pengujian Akhir
```

Mengapa urutannya demikian:

- **Keanggotaan dulu**, karena semua fitur lain membutuhkan akun aktif dan peran admin/anggota.
- **Struktur organisasi setelah data anggota**, karena pengurus dipilih dari anggota aktif.
- **Materi internal sebelum publikasi.** Materi langsung dapat dibaca dan didiskusikan anggota sejak draf, tanpa persetujuan admin. Jadi alur yang paling sering dipakai (tulis → baca → diskusi) dibangun dan didemokan lebih dulu; publikasi kepada umum adalah lapisan tambahan di atasnya.
- **Arsip & halaman baca sebelum forum**, karena forum menempel pada halaman baca materi.
- **Publikasi dipecah menjadi tiga** (pengajuan & verifikasi → revisi → halaman publik), karena alur status publikasi adalah bagian paling rumit dan paling rawan salah.
- **Konten beranda setelah dasbor**, karena poster, cerita, dan sampul artikel adalah lapisan tampilan di atas modul materi & publikasi yang sudah jadi; tidak ada modul lain yang bergantung padanya.
- **Kategori setelah publikasi**, karena kategori menempel pada materi (dan ikut terkunci saat materi terbit), jadi materi, arsip, halaman baca, dan daftar publik harus ada lebih dulu. Menaruhnya di akhir juga membuat penyaring kategori bisa langsung dipasang di seluruh halaman yang sudah jadi.
- **Penarikan materi setelah kategori**, karena F15 memindahkan judul, ringkasan, sampul, dan kategori ke tingkat versi; semua bagian itu (termasuk kategori dari F14) harus sudah ada lebih dulu.
- **Program setelah penarikan materi**, karena F16 berdiri sendiri: hanya membutuhkan akun admin, data anggota aktif (untuk PIC), editor Quill, dan layout publik/admin yang sudah ada. Fitur ini ditambahkan pada Draf 2.4 PRD.
- **Periode otomatis setelah program**, karena F17 membongkar sebagian F4 (CRUD periode dihapus, akses periode lama dipindah ke anggota) dan menyentuh beranda yang sudah dirapikan di F13/F16. Ditambahkan pada Draf 2.5 PRD.
- **Dasbor sebelum penyempurnaan**, karena isinya adalah ringkasan dari semua modul sebelumnya.

> **Catatan Draf 2.3:** F15 mengubah aturan F8, F9, F13, dan F14 (kunci judul/ringkasan/sampul/kategori dihapus; publikasi tidak dapat diajukan selama materi terbit). Skenario uji fitur-fitur itu yang terdampak sudah disesuaikan di bawah dan diberi tanda _(Draf 2.3)_.

> **Catatan Draf 2.6:** admin hanya mengelola dan tidak menulis materi (KP-08.6). Skenario uji F5, F8, F9, dan F15 yang memakai "penulis" berlaku untuk anggota biasa; admin yang mencoba aksi penulis mendapat 403. Admin tetap membaca materi dan berkomentar di forum (F6, F7).

> **Catatan Draf 2.2:** F0–F4 tidak berubah. Jika F0 (migrasi) sudah selesai, tidak ada migrasi yang perlu diubah — koreksi alur materi hanya mengubah aturan akses, bukan struktur tabel.

### Pemetaan ke tugas PRD §14

| Fitur | Tugas PRD §14       | KF                              |
| ----- | ------------------- | ------------------------------- |
| F0    | T1, T2, sebagian T7 | —                               |
| F1    | sebagian T3, T7, T8 | KF-02, KF-03, KF-04             |
| F2    | sebagian T3, T9     | KF-05                           |
| F3    | sebagian T4, T9     | KF-06                           |
| F4    | sebagian T4, T8, T9 | KF-01, KF-07                    |
| F5    | sebagian T5, T10    | KF-08, KF-09                    |
| F6    | sebagian T5, T10    | KF-16                           |
| F7    | sebagian T5, T10    | KF-14, KF-15                    |
| F8    | sebagian T6, T11    | KF-10, KF-11                    |
| F9    | sebagian T6, T11    | KF-12, KF-13                    |
| F10   | sebagian T6, T11    | KF-17                           |
| F11   | sebagian T6, T11    | notifikasi & dasbor (pendukung) |
| F13   | T13                 | KF-18                           |
| F14   | T14                 | KF-19                           |
| F15   | T15                 | KF-20 (+ perubahan KF-09–KF-12, KF-18, KF-19) |
| F16   | T16                 | KF-21                           |
| F17   | T17                 | perubahan KF-01, KF-07          |
| F18   | T18                 | KF-22–KF-25                     |
| F12   | T12                 | semua                           |

---

## 4. Skenario per fitur

### F0 — Fondasi

**Tujuan:** proyek bisa dijalankan secara lokal dan di Docker, basis data lengkap, dan _frontend_ bisa memanggil API.

**Lingkungan (PRD §13)**

- Lokal: Laravel Herd (PHP 8.4, Composer, Node 24); basis data `alfutuuhat` di kontainer `postgres-umum`.
- Docker: `backend/Dockerfile`, `frontend/Dockerfile`, `.dockerignore` masing-masing, `docker-compose.yml`, `backend/.env.docker.example`; tambahkan `.env.docker` ke `backend/.gitignore`.

**Backend**

- Laravel di `backend/`, pasang Sanctum (`install:api`) dan Reverb (`install:broadcasting`), atur CORS, `php artisan storage:link`.
- **Semua** migrasi, model (`protected $table`), relasi, _factory_, dan _seeder_ sesuai PRD §10 dibuat sekarang (ERD sudah final, sehingga tidak perlu migrasi bolak-balik).
- _Seeder_: admin dari `.env`, 6 divisi, jabatan Ketua & Koordinator. _Seeder_ data contoh (memakai _factory_) hanya dipanggil jika lingkungan `local`, karena image Docker tidak memuat paket _dev_.
- Aktifkan `Model::preventLazyLoading()` di lingkungan lokal (KNF-09).

**Frontend**

- Nuxt di `frontend/` (`ssr: false`), Tailwind.
- `runtimeConfig.public`: `apiBase`, `reverbKey`, `reverbHost`, `reverbPort`, `reverbScheme` (diisi lewat `NUXT_PUBLIC_*`).
- Composable `useApi` (basis URL, _header_ `Authorization: Bearer`, tangani 401).
- Tiga layout kosong: `publik`, `anggota`, `admin`.

**Selesai jika**

- [ ] Lokal: `php artisan serve`, `php artisan reverb:start`, dan `npm run dev` berjalan bersamaan.
- [ ] `php artisan migrate:fresh --seed` sukses; admin dan 6 divisi ada di basis data.
- [ ] Halaman Nuxt terbuka dan dapat memanggil satu _endpoint_ uji.
- [ ] `docker compose up -d --build` menjalankan `db`, `backend`, `reverb`, `frontend`; setelah migrasi di kontainer, halaman di `localhost:3000` dapat memanggil _endpoint_ uji yang sama.

---

### F1 — Pendaftaran & Login (UC-01, UC-02)

**Tujuan:** pengunjung dapat mendaftar; pengguna dapat login/logout sesuai status dan peran.

**Backend:** `POST /register`, `POST /login`, `POST /logout`, `GET /me`; _middleware_ peran admin dan status aktif.
**Frontend:** `/daftar`, `/daftar/berhasil`, `/masuk`; composable `useAuth`; _middleware_ `auth`, `guest`, `admin`; kerangka menu layout anggota & admin.

**Skenario alur**

1. Pengunjung membuka `/daftar`, mengisi formulir, lalu mengirim.
2. Sistem menyimpan `users` (status `menunggu`) + `anggota` dalam satu transaksi, lalu mengarahkan ke `/daftar/berhasil`.
3. Pendaftar mencoba login → ditolak karena masih menunggu.
4. Admin hasil _seeder_ login → diarahkan ke `/admin`.

**Skenario uji**

| No  | Skenario                                                 | Hasil yang diharapkan                        | KP               |
| --- | -------------------------------------------------------- | -------------------------------------------- | ---------------- |
| 1   | Daftar dengan data lengkap dan valid                     | Tersimpan, diarahkan ke halaman berhasil     | 02.1, 03.1, 03.2 |
| 2   | Email sudah terdaftar                                    | "Email sudah terdaftar."                     | 02.2             |
| 3   | Kata sandi 7 karakter / konfirmasi berbeda               | Pesan kesalahan di bawah isian               | 02.3             |
| 4   | Foto PDF / foto 3 MB                                     | Ditolak dengan pesan                         | 02.4             |
| 5   | Kirim dengan isian salah                                 | Isian lain tidak hilang                      | 02.5             |
| 6   | Login dengan email/kata sandi salah                      | "Email atau kata sandi tidak sesuai."        | 04.1             |
| 7   | Login akun `menunggu`                                    | Pesan menunggu verifikasi                    | 03.3             |
| 8   | Login akun `ditolak` (ubah status lewat basis data dulu) | Pesan penolakan + alasan                     | 04.2             |
| 9   | Login akun `nonaktif`                                    | "Akun Anda dinonaktifkan. Hubungi pengurus." | 04.3             |
| 10  | Login anggota aktif / admin                              | Ke `/dasbor` / ke `/admin`                   | 04.4             |
| 11  | Anggota membuka `/admin` dan memanggil API admin         | Dialihkan; API 403                           | 04.5             |
| 12  | Logout lalu panggil `/me` dengan token lama              | 401                                          | 04.6             |

**Hal yang sering keliru:** membedakan pesan "email salah" dan "kata sandi salah"; menyimpan `users` tanpa transaksi sehingga ada `users` tanpa `anggota`.

---

### F2 — Verifikasi Pendaftaran (UC-04)

**Backend:** `GET /admin/pendaftaran`, `GET /admin/pendaftaran/{user}`, `PATCH /admin/pendaftaran/{user}`.
**Frontend:** `/admin/pendaftaran` (daftar + filter `menunggu`/`ditolak`), `/admin/pendaftaran/[id]` (detail, foto, alasan, tombol Setujui/Tolak; Tolak membuka isian catatan wajib).

**Skenario alur**

1. Daftarkan dua akun baru (A dan B).
2. Admin menyetujui A → A berhasil login ke `/dasbor`.
3. Admin menolak B dengan alasan → B login dan melihat alasan tersebut.

**Skenario uji**

| No  | Skenario                                       | Hasil yang diharapkan                                            | KP   |
| --- | ---------------------------------------------- | ---------------------------------------------------------------- | ---- |
| 1   | Buka daftar pendaftar                          | Hanya status `menunggu`; filter `ditolak` berfungsi              | 05.1 |
| 2   | Buka detail                                    | Foto dan alasan bergabung tampil                                 | 05.2 |
| 3   | Setujui                                        | Status `aktif`; `tanggal_bergabung` & `diverifikasi_pada` terisi | 05.3 |
| 4   | Tolak tanpa catatan                            | 422                                                              | 05.4 |
| 5   | Tolak dengan catatan                           | Status `ditolak`, catatan tersimpan                              | 05.4 |
| 6   | Putuskan ulang pendaftar yang sudah diputuskan | 422                                                              | 05.5 |
| 7   | Anggota memanggil endpoint ini                 | 403                                                              | 04.5 |

---

### F3 — Data Anggota (UC-05)

**Backend:** `GET/PUT /admin/anggota...`, `PATCH .../status`, `PATCH .../password`.
**Frontend:** `/admin/anggota` (tabel berpaginasi, cari, filter status), `/admin/anggota/[id]` (ubah data & peran, aktifkan/nonaktifkan, atur ulang kata sandi).

**Skenario alur**

1. Admin mencari anggota A, mengubah nomor HP-nya.
2. A sedang login di peramban lain; admin menonaktifkan A → permintaan berikutnya dari A ditolak (token terhapus).
3. Admin mengaktifkan kembali A dan mengatur ulang kata sandinya → A login dengan kata sandi baru.

**Skenario uji**

| No  | Skenario                                               | Hasil yang diharapkan                                  | KP   |
| --- | ------------------------------------------------------ | ------------------------------------------------------ | ---- |
| 1   | Cari berdasarkan nama/email, filter status             | Hasil sesuai, berpaginasi                              | 06.1 |
| 2   | Ubah nama, HP, alamat, peran                           | Tersimpan                                              | 06.2 |
| 3   | Nonaktifkan akun yang sedang login                     | Token terhapus, akun tidak bisa memakai API            | 06.3 |
| 4   | Aktifkan kembali                                       | Bisa login lagi                                        | 06.3 |
| 5   | Atur ulang kata sandi                                  | Login dengan kata sandi baru berhasil, yang lama gagal | 06.4 |
| 6   | Admin menonaktifkan / menurunkan peran dirinya sendiri | Ditolak dengan pesan                                   | 06.5 |

**Cek N+1:** daftar anggota harus memuat relasi `anggota` dengan _eager loading_.

**◆ Milestone M1** — modul keanggotaan selesai. Waktu yang baik untuk demo pertama ke pengurus.

---

### F4 — Struktur Organisasi: Admin + Beranda Publik (UC-06, UC-03)

**Urutan di dalam fitur:** periode → divisi & jabatan → pengurus → tampilan publik.

**Backend:** CRUD `/admin/periode`, `/admin/divisi`, `/admin/jabatan`, `/admin/kepengurusan`, `PATCH /admin/periode/{}/aktifkan`; publik `GET /divisi`, `GET /periode`, `GET /periode/{periode}/struktur`.
**Frontend:** `/admin/struktur` (tab Periode, Divisi, Jabatan, Pengurus); `/` (profil, divisi, struktur akordeon, cara bergabung); `/struktur` untuk anggota. Tautan "Publikasi" di header publik boleh disiapkan sekarang, halamannya dibuat di F10.

**Skenario alur**

1. Admin membuat periode 2025/2026 dan 2026/2027, lalu mengaktifkan 2026/2027.
2. Admin menambah jabatan (mis. Sekretaris), lalu mengisi pengurus: Ketua (tanpa divisi), Koordinator per divisi, dan anggota divisi.
3. Pengunjung (tanpa login) membuka beranda → melihat struktur periode aktif, lalu berganti ke periode lain.

**Skenario uji**

| No  | Skenario                                        | Hasil yang diharapkan                                                              | KP         |
| --- | ----------------------------------------------- | ---------------------------------------------------------------------------------- | ---------- |
| 1   | Aktifkan periode B saat A aktif                 | A otomatis nonaktif                                                                | 07.1       |
| 2   | CRUD divisi & jabatan                           | Berhasil; urutan jabatan tersimpan                                                 | 07.2       |
| 3   | Tambah pengurus inti (divisi kosong)            | Tersimpan                                                                          | 07.3       |
| 4   | Anggota yang sama dua kali di periode yang sama | 422 dengan pesan                                                                   | 07.4       |
| 5   | Hapus divisi/jabatan/periode yang masih dipakai | Pesan jelas, bukan error 500                                                       | 07.5       |
| 6   | Buka beranda tanpa login                        | Terbuka; periode aktif tampil pertama                                              | 01.1, 01.2 |
| 7   | Ganti periode                                   | Struktur berubah                                                                   | 01.3       |
| 8   | Lihat susunan                                   | Pengurus inti di atas; per divisi dapat dibuka-tutup; urut sesuai `jabatan.urutan` | 01.4       |
| 9   | Periksa respons API publik di Postman           | Tidak ada no HP, alamat, email, alasan, foto                                       | 01.5       |

**Perlu disiapkan:** teks profil organisasi dan "cara bergabung" untuk beranda (minta dari pengurus).

**◆ Milestone M2** — tujuan keanggotaan + struktur tercapai.

---

### F5 — Materi: Tulis & Kelola (UC-07)

**Tujuan:** anggota menulis materi yang **langsung menjadi milik bersama anggota** (tanpa verifikasi admin).

**Backend:** `POST /materi`, `GET /materi/{materi}/edit`, `PUT /materi/{materi}`, `DELETE /materi/{materi}`, `GET /materi-saya`, `POST /unggah-gambar`; sanitasi HTML sebelum menyimpan `konten_html` (mis. memakai paket HTML Purifier untuk Laravel).
**Frontend:** `/materi-saya` (tab per status), `/materi-saya/baru` dan `/materi-saya/[id]/edit` dengan Quill; _handler_ gambar Quill mengunggah ke `/unggah-gambar` lalu menyisipkan URL. Tombol "Ajukan Publikasi" belum ada (F8).

**Skenario alur**

1. Anggota membuka "Tulis Materi", mengisi judul, ringkasan, isi berformat, dan menyisipkan gambar.
2. Simpan → muncul di tab Draf (dan, setelah F6, langsung terlihat di arsip anggota lain).
3. Anggota mengubah lalu menghapus salah satu materi.

**Skenario uji**

| No  | Skenario                                 | Hasil yang diharapkan                                                                     | KP               |
| --- | ---------------------------------------- | ----------------------------------------------------------------------------------------- | ---------------- |
| 1   | Simpan materi baru                       | 1 baris `materi` (`draf`) + 1 `revisi_materi` (v1, `draf`); tidak ada notifikasi ke admin | 08.1, 08.3, 08.5 |
| 2   | Sisipkan gambar                          | Konten berisi URL server, bukan base64                                                    | 08.2             |
| 3   | Sisipkan gambar 3 MB / GIF               | Ditolak                                                                                   | 08.2             |
| 4   | Kirim `<script>` di konten lewat Postman | `konten_html` tersimpan tanpa skrip                                                       | 08.4             |
| 5   | Ubah materi milik sendiri                | Tersimpan pada revisi v1 yang sama (tidak membuat versi baru)                             | 09.1             |
| 6   | Hapus materi yang belum pernah terbit    | Terhapus beserta revisinya                                                                | 09.2             |
| 7   | Anggota lain mengubah/menghapus          | 403                                                                                       | 09.3             |

---

### F6 — Arsip Anggota, Pencarian & Baca (UC-12)

**Tujuan:** seluruh anggota dapat menemukan dan membaca **semua** materi, termasuk yang masih draf.

**Backend:** `GET /materi?q=&penulis=&status=&page=` (_full-text search_ PostgreSQL atas judul + isi **revisi terakhir**; indeks GIN sudah dibuat di F0), `GET /materi/{slug}`.
**Frontend:** `/materi` (kotak cari, filter penulis & status publikasi, kartu hasil dengan label status, paginasi); `/materi/[slug]` (isi revisi terakhir, penulis, tanggal, nomor versi, label status publikasi, tempat riwayat versi & forum).

**Skenario alur**

1. Anggota A menulis materi X (draf) di F5.
2. Anggota B login → X muncul di `/materi` dengan label "Draf" → B membuka dan membacanya.
3. A mengubah isi X → B memuat ulang halaman dan melihat isi terbaru.

**Skenario uji**

| No  | Skenario                                                         | Hasil yang diharapkan                                                                     | KP         |
| --- | ---------------------------------------------------------------- | ----------------------------------------------------------------------------------------- | ---------- |
| 1   | Draf milik A dibuka di arsip oleh B                              | Muncul dan dapat dibaca                                                                   | 08.5, 16.1 |
| 2   | Cari kata yang ada di judul                                      | Ditemukan                                                                                 | 16.1       |
| 3   | Cari kata yang hanya ada di isi revisi terakhir                  | Ditemukan                                                                                 | 16.1       |
| 4   | Filter nama penulis / status publikasi (dengan/tanpa kata kunci) | Sesuai                                                                                    | 16.1       |
| 5   | Tampilan hasil                                                   | Judul, ringkasan, penulis, tanggal diperbarui, label status, jumlah komentar; berpaginasi | 16.2       |
| 6   | Kata kunci acak                                                  | "Materi tidak ditemukan."                                                                 | 16.3       |
| 7   | Buka halaman baca                                                | Isi revisi terakhir + label status                                                        | 16.4       |
| 8   | Panggil `GET /materi` tanpa token                                | 401                                                                                       | KNF-02     |

**Catatan:** konfigurasi `simple` tidak melakukan _stemming_, sehingga "mengajar" tidak cocok dengan "ajar". Ini wajar dan perlu disebut sebagai batasan.

---

### F7 — Forum Diskusi Real-time (UC-11)

**Dikerjakan dua langkah:**

- **7a — Forum biasa:** `GET/POST /materi/{materi}/komentar`, `GET /komentar/{komentar}/balasan`, `DELETE /komentar/{komentar}`; komponen forum di `/materi/[slug]` (teks, gambar, balas satu tingkat, hapus milik sendiri). Balasan tidak ikut pada daftar komentar: pembaca membukanya sendiri (KP-14.5). Forum berlaku untuk materi **status apa pun**. Pastikan berjalan dengan _refresh_ dulu.
- **7b — Real-time:** _event_ `KomentarDibuat` & `KomentarDihapus` (`ShouldBroadcastNow`), otorisasi kanal privat `materi.{id}` di `routes/channels.php`, `/broadcasting/auth` dengan Sanctum; plugin Echo di Nuxt, berlangganan saat halaman dibuka dan berhenti saat halaman ditutup.

**Skenario alur**

1. Anggota A (Chrome) dan B (Firefox) membuka materi X yang **masih draf**.
2. A berkomentar → komentar muncul di layar B tanpa memuat ulang.
3. B membalas → angka "Lihat n balasan" di layar A bertambah; bila A sudah membuka balasan induk itu, balasannya ikut tampil.
4. A menghapus komentarnya → komentar beserta balasannya hilang di layar B.

**Skenario uji**

| No  | Skenario                                         | Hasil yang diharapkan                             | KP   |
| --- | ------------------------------------------------ | ------------------------------------------------- | ---- |
| 1   | Forum pada materi `draf`                         | Tersedia; komentar dapat dikirim                  | 14.1 |
| 2   | Panggil API komentar tanpa token                 | 401                                               | 14.1 |
| 3   | Komentar teks / gambar saja / keduanya           | Tersimpan                                         | 14.2 |
| 4   | Komentar tanpa teks dan tanpa gambar             | 422                                               | 14.2 |
| 5   | Gambar 3 MB / bukan JPG/PNG                      | Ditolak                                           | 14.2 |
| 6   | Balas komentar; balas sebuah balasan             | Berhasil; balasan tingkat kedua ditolak/diratakan | 14.3 |
| 7   | Hapus komentar orang lain                        | 403                                               | 14.4 |
| 8   | Hapus komentar induk                             | Balasan ikut terhapus                             | 14.4 |
| 9   | Komentar baru di dua peramban                    | Muncul tanpa memuat ulang                         | 15.1 |
| 10  | Komentar dihapus di dua peramban                 | Hilang tanpa memuat ulang                         | 15.2 |
| 11  | Pengguna tidak login/nonaktif berlangganan kanal | `/broadcasting/auth` menolak                      | 15.3 |
| 12  | Hapus materi (F5) yang sudah punya komentar      | Komentar ikut terhapus                            | 09.2 |
| 13  | Muat daftar komentar materi berbalasan           | Hanya induk + `jumlah_balasan`; balasan tidak ikut | 14.5 |
| 14  | Klik "Lihat n balasan"                           | Balasan diambil dari `GET /komentar/{id}/balasan`  | 14.5 |
| 15  | Minta balasan dari sebuah balasan                | 404                                               | 14.5 |

**Tips:** periksa koneksi WebSocket di tab _Network → WS_ pada DevTools peramban. Pastikan Reverb berjalan (lokal: `php artisan reverb:start --debug`; Docker: kontainer `reverb`). `REVERB_APP_KEY` di _backend_ harus sama dengan `NUXT_PUBLIC_REVERB_KEY` di _frontend_; di Docker, `REVERB_HOST` _backend_ bernilai `reverb`, sedangkan _frontend_ tetap `localhost`.

**◆ Milestone M3** — materi & diskusi internal selesai. Ini kebutuhan utama anggota; waktu yang baik untuk demo kedua ke pengurus.

---

### F8 — Pengajuan & Verifikasi Publikasi (UC-08, UC-09)

**Tujuan:** materi yang ingin dibaca umum diajukan penulis dan diputuskan admin.

**Backend:** `POST /materi/{materi}/ajukan`, `POST /materi/{materi}/batal-ajukan`; `GET /admin/verifikasi-materi`, `GET /admin/revisi/{revisi}`, `POST /admin/revisi/{revisi}/verifikasi`; kelas _Notification_ (kanal `database`) untuk "materi diajukan" dan "revisi diverifikasi".
**Frontend:** tombol "Ajukan Publikasi" dan "Batalkan Pengajuan" di editor/`/materi-saya`; `/admin/verifikasi-materi` (antrean); `/admin/verifikasi-materi/[revisiId]` (isi revisi, Setujui/Tolak + catatan). Halaman notifikasi lengkap menyusul di F11; di tahap ini notifikasi cukup dicek lewat Postman/basis data.

**Skenario alur**

1. Anggota A mengajukan materi X → semua admin mendapat notifikasi. Anggota lain tetap dapat membaca dan berdiskusi di X.
2. Admin menolak X dengan catatan → A melihat status `ditolak` beserta catatan; X tetap ada di arsip anggota.
3. Anggota A mengajukan materi Z, lalu ingin memperbaikinya → membatalkan pengajuan → Z kembali `draf`, hilang dari antrean admin, dan dapat diubah lagi.
4. Anggota A mengajukan materi Y → admin menyetujui → Y berstatus `terbit` (tampil untuk umum setelah F10).

**Skenario uji**

| No  | Skenario                                                             | Hasil yang diharapkan                                                   | KP         |
| --- | -------------------------------------------------------------------- | ----------------------------------------------------------------------- | ---------- |
| 1   | Penulis mengajukan materi                                            | Revisi `diajukan`; materi `diajukan`                                    | 10.1, 10.2 |
| 2   | Bukan penulis mengajukan                                             | 403                                                                     | 10.1       |
| 3   | Notifikasi admin                                                     | Semua admin aktif menerima                                              | 10.3       |
| 4   | Ubah materi yang sedang diajukan                                     | Ditolak                                                                 | 10.4       |
| 5   | Anggota lain membuka & berkomentar di materi yang diajukan           | Tetap bisa                                                              | 10.5       |
| 6   | Antrean admin                                                        | Urut terlama di atas                                                    | 11.1       |
| 7   | Setujui                                                              | Revisi `disetujui`; materi `terbit`; `diterbitkan_pada` terisi          | 11.3       |
| 8   | Tolak tanpa catatan / dengan catatan                                 | 422 / materi `ditolak`, tetap di arsip anggota                          | 11.4       |
| 9   | Riwayat keputusan & notifikasi penulis                               | Baris `verifikasi_materi` ada; penulis menerima notifikasi              | 11.5       |
| 10  | Verifikasi revisi yang bukan `diajukan`                              | 422                                                                     | 11.6       |
| 11  | Hapus materi yang sedang diajukan / sudah terbit                     | Ditolak                                                                 | 09.2       |
| 12  | Penulis membatalkan pengajuan                                        | Revisi & materi kembali `draf`; hilang dari antrean admin; dapat diubah | 10.6       |
| 13  | Bukan penulis membatalkan / membatalkan revisi yang sudah diputuskan | 403 / 422                                                               | 10.6       |
| 14  | Admin memverifikasi revisi yang pengajuannya sudah dibatalkan        | 422                                                                     | 11.6       |

---

### F9 — Revisi & Riwayat Versi (UC-10)

**Backend:** `POST /materi/{materi}/revisi`, `GET /materi/{materi}/riwayat`, `GET /materi/{materi}/riwayat/{revisi}` (isi satu versi, KP-13.4); lengkapi `GET /admin/revisi/{revisi}` dengan versi publik sebagai pembanding (KP-11.2); lengkapi `GET /materi/{slug}` dengan nomor versi publik.
**Frontend:** tombol "Buat Revisi"; editor mengunci judul & ringkasan jika materi sudah pernah terbit (_dihapus di Draf 2.3, lihat F15_); komponen Riwayat Versi di `/materi/[slug]` beserta pembacaan isi versi lama di kolom baca (baris arsip + "Kembali ke versi terbaru"); keterangan "Versi publik: vN" bila berbeda dari revisi terakhir; tampilan pembanding di halaman verifikasi admin.

**Skenario alur (skenario terpenting — cocok untuk demo sidang)**

1. Materi X (v1 ditolak) → penulis membuat revisi v2 → anggota langsung membaca v2 → ajukan → admin setujui → X terbit untuk umum dengan v2.
2. Penulis membuat revisi v3 atas X → **anggota langsung melihat v3**, sedangkan **versi publik tetap v2** (dicek ulang lewat halaman publik di F10).
3. _(Draf 2.3)_ Penulis mencoba mengajukan v3 → 422 karena X masih terbit. Penulis mengajukan penarikan → admin menyetujui → X hilang dari publik (status `draf`).
4. _(Draf 2.3)_ v3 diajukan; admin membandingkan v3 dengan v2, lalu menyetujui → X terbit lagi dan versi publik menjadi v3.
5. Anggota membuka "Riwayat versi" pada X → menekan "Lihat isi versi ini" pada baris v1 → isi v1 tampil di kolom baca dengan baris "Anda membaca arsip v1" → "Kembali ke versi terbaru" mengembalikan isi v4.

**Skenario uji**

| No  | Skenario                                                       | Hasil yang diharapkan                                                   | KP         |
| --- | -------------------------------------------------------------- | ----------------------------------------------------------------------- | ---------- |
| 1   | Bukan penulis membuat revisi                                   | 403                                                                     | 12.1       |
| 2   | Buat revisi saat masih ada draf/diajukan                       | 422                                                                     | 12.2       |
| 3   | Buat revisi baru                                               | `nomor_versi` +1, `draf`, isi tersalin                                  | 12.3       |
| 4   | _(Draf 2.3)_ Ubah judul pada revisi materi terbit              | Judul revisi berubah untuk anggota; judul di halaman publik tetap      | 12.4       |
| 5   | Anggota membuka materi terbit yang punya revisi v+1 draf       | Melihat v+1 dengan keterangan "Versi publik: vN"                        | 12.4, 16.4 |
| 6   | Ubah/hapus revisi `disetujui` lewat Postman                    | Ditolak                                                                 | 12.5       |
| 7   | Penulis melihat riwayat                                        | Nomor versi, status, tanggal, catatan revisi, keputusan & catatan admin | 13.1, 13.2 |
| 8   | Anggota lain melihat riwayat (cek juga respons API di Postman) | Semua versi & status tampil; catatan admin **tidak** ada                | 13.3       |
| 9   | Admin membuka revisi materi terbit                             | Versi publik tampil sebagai pembanding                                  | 11.2       |
| 10  | `GET /materi/{id}/riwayat/{versiLama}` (Postman)               | Isi versi lama utuh; `konten_html` sama seperti saat versi itu disimpan | 13.4       |
| 11  | `GET /materi/{id}/riwayat/{versiDariMateriLain}`               | 404                                                                     | 13.4       |
| 12  | Anggota menekan "Lihat isi versi ini" pada sebuah versi        | Isi versi itu tampil di kolom baca + baris arsip vN & tombol kembali    | 13.4       |

---

### F10 — Halaman Publikasi untuk Umum (UC-13)

**Tujuan:** pengunjung tanpa login dapat mencari dan membaca materi yang sudah disetujui admin.

**Backend:** `GET /publik/materi?q=&page=` (hanya `terbit`; _full-text search_ atas judul + isi **versi publik**), `GET /publik/materi/{slug}` (versi publik saja; 404 jika belum terbit). Gunakan _Resource_ tersendiri (mis. `MateriPublikResource`) agar riwayat, catatan admin, dan data pribadi penulis tidak ikut terkirim.
**Frontend:** `/publikasi` (kotak cari, kartu hasil, paginasi) dan `/publikasi/[slug]` (judul, ringkasan, nama penulis, tanggal terbit, isi versi publik) dengan layout `publik`; menu "Publikasi" di header dan tautan di beranda.

**Skenario alur**

1. Tanpa login, buka `/publikasi` → hanya materi yang disetujui admin yang tampil.
2. Buka materi X dari skenario F9 saat v3 masih draf → yang tampil v2.
3. Coba buka slug materi yang masih draf lewat URL publik → halaman "tidak ditemukan".

**Skenario uji**

| No  | Skenario                                                           | Hasil yang diharapkan                                         | KP   |
| --- | ------------------------------------------------------------------ | ------------------------------------------------------------- | ---- |
| 1   | Buka `/publikasi` tanpa login                                      | Terbuka; hanya materi `terbit`                                | 17.1 |
| 2   | Materi `draf`/`diajukan`/`ditolak`                                 | Tidak muncul di daftar publik                                 | 17.1 |
| 3   | Cari kata di judul / isi versi publik                              | Ditemukan                                                     | 17.1 |
| 4   | Materi terbit yang punya revisi lebih baru (draf/diajukan/ditolak) | Halaman publik menampilkan versi publik, bukan revisi terbaru | 17.2 |
| 5   | Slug materi belum terbit lewat `/publik/materi/{slug}`             | 404                                                           | 17.3 |
| 6   | Periksa respons API publik di Postman                              | Tidak ada forum, riwayat, catatan admin, email/HP penulis     | 17.4 |
| 7   | Cari kata yang hanya ada di revisi belum disetujui                 | **Tidak** ditemukan                                           | 17.5 |

**◆ Milestone M4** — semua fitur inti selesai.

---

### F11 — Notifikasi & Dasbor

**Backend:** `GET /notifikasi`, `PATCH /notifikasi/{id}/baca`, `PATCH /notifikasi/baca-semua`, `GET /dasbor`, `GET /admin/dasbor`.
**Frontend:** lencana jumlah notifikasi di _header_; `/notifikasi`; `/dasbor` (jumlah materi per status publikasi, pintasan); `/admin` (pendaftar menunggu, pengajuan publikasi, anggota aktif, materi terbit untuk umum).

**Skenario uji:** angka dasbor sama dengan data di basis data; tandai satu/semua dibaca mengurangi lencana; klik notifikasi membuka materi/revisi terkait; anggota memanggil `/admin/dasbor` → 403.

---

### F13 — Konten Beranda & Sampul Artikel (UC-14)

**Tujuan:** beranda pengunjung tidak lagi polos — ada poster dan cerita Al-Futuuhat yang diatur admin — dan daftar artikel memakai kartu bersampul.

**Backend:** tabel `pengaturan_beranda` (satu baris); `GET /publik/beranda` (publik), `GET|POST /admin/beranda` (admin, multipart); kolom `sampul` pada `materi`; `POST /unggah-sampul`; `sampul` ikut terkirim di `MateriRingkasResource` dan `MateriPublikResource`; `sampul_path` dikirim di `GET /materi/{materi}/edit` (Resource mengirim URL, formulir butuh path).
**Frontend:** beranda (`/`) menampilkan poster (bila ada) dan cerita dari API dengan teks bawaan sebagai cadangan; `/publikasi` memakai grid `KartuArtikel`; `IsianSampul` di `FormMateri`; halaman admin `/admin/beranda`.

**Skenario alur**

1. Admin membuka `/admin/beranda`, mengunggah poster, menulis judul dan isi cerita, lalu menyimpan.
2. Pengunjung tanpa login membuka `/` → poster dan cerita tampil; struktur dan "cara bergabung" tetap ada.
3. Anggota menulis materi baru dengan sampul → sampul tampil di arsip anggota; setelah terbit, sampul tampil di kartu `/publikasi`.

**Skenario uji**

| No  | Skenario                                                        | Hasil yang diharapkan                                                        | KP          |
| --- | --------------------------------------------------------------- | ---------------------------------------------------------------------------- | ----------- |
| 1   | Admin simpan poster + cerita, lalu buka `/` tanpa login         | Poster dan cerita tampil; isi cerita tidak memuat `<script>`                  | 18.1, 18.3  |
| 2   | Ganti poster, lalu hapus poster                                 | Poster lama tergantikan, berkas lama hilang dari server, beranda rapi kembali | 18.1        |
| 3   | Belum ada pengaturan sama sekali, buka `/`                      | Terbuka tanpa galat; bagian poster hilang; cerita memakai teks bawaan         | 18.2        |
| 4   | Anggota biasa / tamu memanggil `POST /admin/beranda`            | 403 / 401                                                                     | 18.3        |
| 5   | Unggah poster atau sampul bukan JPG/PNG, atau > 2 MB            | 422 dengan pesan jelas                                                        | 18.1, 18.4  |
| 6   | Kirim `sampul` berisi path dari folder lain di server           | 422 "Sampul tidak dikenal."                                                   | 18.4        |
| 7   | Materi terbit lalu sampulnya diganti                            | Sampul tidak berubah (terkunci)                                               | 18.4        |
| 8   | Buka `/publikasi` dengan materi bersampul dan tanpa sampul      | Kartu tampil rapi pada keduanya; kartu tanpa sampul memakai kotak kosong       | 18.5        |

**◆ Milestone M4** — lihat F10; F13 adalah lapisan tampilan di atasnya.

---

### F14 — Kategori Materi (UC-15)

**Tujuan:** materi dapat dikelompokkan menurut bidang ilmu (Fiqih, Akhlak, Politik Islam, Sejarah Islam, dst.), datanya diatur admin, dan pembaca dapat menyaring daftar materi menurut kategori.

**Backend:** tabel `kategori` + tabel pivot `kategori_materi`; `GET /kategori` (publik); `GET|POST /admin/kategori`, `PUT|DELETE /admin/kategori/{kategori}` (admin, hapus ditolak bila masih dipakai); `kategori` wajib diisi minimal satu saat menyimpan materi dan terkunci setelah materi pernah terbit; `?kategori=slug` pada `GET /materi` dan `GET /publik/materi`; `kategori` pada Resource materi dan `kategori_ids` pada `GET /materi/{materi}/edit`.

**Frontend (sesi terpisah):** `/admin/kategori` (tabel Nama · Slug · Jumlah materi · Aksi, modal tambah/ubah, konfirmasi hapus) + menu "Kategori" di grup Materi pada menu samping admin; `PemilihKategori` di `FormMateri` (terkunci bila materi sudah pernah terbit); `ChipKategori` di `StoryRow` `/materi`, `ArticleHeader` `/materi/[slug]` dan `/publikasi/[slug]`, serta `KartuArtikel` `/publikasi` (label rubrik di atas judul); `FilterKategori` di `/materi` dan `/publikasi` — sidebar untuk layar lebar, satu baris pil yang digulir mendatar di ponsel — dengan pilihan tersimpan di URL (`?kategori=slug`). Karena kategori dapat bertambah banyak, daftar pil diringkas 8 pil + tombol "Selengkapnya" dan kotak cari kategori, baik di penyaring sidebar maupun di pemilih editor.

**Skenario alur**

1. Admin membuka `/admin/kategori`, menambah "Politik Islam" → muncul di daftar dengan jumlah materi 0.
2. Anggota menulis materi dan memilih satu atau lebih kategori → kategori tampil sebagai chip di arsip dan halaman baca.
3. _(Draf 2.3)_ Materi terbit → penulis mengubah kategori pada revisi `draf` → anggota langsung melihat kategori baru, sedangkan halaman publik tetap memakai kategori versi publik.
4. Pembaca memilih kategori "Fiqih" di `/materi` atau `/publikasi` → hanya materi berkategori itu yang tampil.

**Skenario uji**

| No  | Skenario                                                        | Hasil yang diharapkan                                                             | KP          |
| --- | --------------------------------------------------------------- | --------------------------------------------------------------------------------- | ----------- |
| 1   | Admin menambah kategori dengan nama baru                        | 201; slug terbentuk otomatis dari nama                                             | 19.1        |
| 2   | Admin menambah/mengubah kategori dengan nama yang sudah dipakai | 422 "Nama kategori sudah ada."                                                     | 19.1        |
| 3   | Admin menghapus kategori yang belum dipakai materi              | Terhapus dan hilang dari daftar                                                    | 19.1        |
| 4   | Admin menghapus kategori yang masih dipakai materi              | 422 "Kategori masih dipakai oleh materi."; kategori tetap ada                      | 19.1        |
| 5   | Anggota biasa / tamu memanggil endpoint admin kategori          | 403 / 401                                                                          | 19.1        |
| 6   | Menyimpan materi tanpa memilih kategori                         | 422 "Pilih minimal satu kategori."                                                 | 19.2        |
| 7   | Menyimpan materi dengan dua kategori                            | Keduanya tersimpan; `GET /materi/{materi}/edit` mengembalikan kedua id di `kategori_ids` | 19.2   |
| 8   | Menyimpan materi dengan id kategori yang tidak ada              | 422 "Kategori tidak dikenal."                                                      | 19.2        |
| 9   | Materi belum pernah terbit, kategori diubah (Fiqih → Akhlak)    | Kategori materi berubah menjadi Akhlak                                             | 19.2        |
| 10  | _(Draf 2.3)_ Materi terbit, kategori revisi `draf` diubah lewat API | Kategori revisi berubah (chip & penyaring anggota); kategori publik tetap      | 19.3        |
| 11  | Buka `/materi?kategori=fiqih`                                   | Hanya materi berkategori Fiqih yang tampil                                         | 19.4        |
| 12  | Buka `/publik/materi?kategori=akhlak&q=puasa`                   | Penyaring kategori digabung dengan pencarian kata kunci                            | 19.4        |
| 13  | Buka halaman baca anggota & publik materi yang berkategori      | Chip kategori tampil pada kedua halaman                                            | 19.4        |

---

### F15 — Penarikan Materi & Bagian Materi per Versi (UC-16, UC-17)

**Tujuan:** penulis bebas mengubah seluruh bagian materinya kapan saja sebagai draf tanpa mengubah versi publik; versi publik materi yang sudah terbit hanya dapat diganti setelah materi ditarik dari publik atas persetujuan admin.

**Backend:**
- Migrasi: kolom `judul`, `ringkasan`, `sampul` pada `revisi_materi` (diisi dari `materi` untuk data lama), pivot `kategori_revisi` (diisi dari `kategori_materi`), tabel `penarikan_materi`, indeks GIN judul pada `revisi_materi`.
- `PUT /materi/{materi}`: semua bagian disimpan ke revisi `draf`; data di tabel `materi` ikut disamakan hanya bila materi tidak `terbit`. Kunci `bisa_ubah_judul` dihapus. Berkas sampul lama hanya dihapus bila tidak dipakai versi lain/data publik.
- `POST /materi/{materi}/revisi` menyalin judul, ringkasan, sampul, dan kategori dari revisi terakhir.
- `POST /materi/{materi}/ajukan` → 422 bila materi `terbit`. Lengkapi KP-10.6 (`POST /materi/{materi}/batal-ajukan`), yang tercantum di PRD tetapi belum ada di `routes/api.php`.
- Verifikasi publikasi (KP-11.3) menyalin judul, slug, ringkasan, sampul, dan kategori revisi ke `materi`.
- `POST /materi/{materi}/tarik`, `POST /materi/{materi}/batal-tarik`, `GET /admin/penarikan-materi`, `POST /admin/penarikan/{penarikan}/verifikasi`; _Notification_ "penarikan diajukan" (admin) dan "penarikan diputuskan" (penulis).
- `GET /materi/{materi}/riwayat/{revisi}` memuat judul, ringkasan, sampul, dan kategori versi itu; Resource materi (ringkas & detail) dan `GET /materi/{materi}/edit` memuat `versi_publik_terakhir` (id & nomor), yang tetap ada setelah materi ditarik (KP-10.7, KP-16.4).
- Resource area anggota memakai judul/ringkasan/sampul/kategori revisi terakhir; pencarian & penyaring kategori `GET /materi` dan `GET /materi-saya` atas revisi terakhir; Resource publik tetap memakai kolom `materi`.
- Dasbor admin menghitung pengajuan penarikan.
- Sesuaikan tes lama yang mengandalkan kunci judul/kategori/sampul (`RevisiMateriTest`, `KategoriMateriTest`, `SampulMateriTest`, `VerifikasiMateriTest`).

**Frontend (sesi terpisah; tata letak dibahas dulu):** `FormMateri` tanpa kunci judul/ringkasan/sampul/kategori; tombol per status di `/materi-saya` dan editor mengikuti DESIGN.md §7 (Ubah selalu ada kecuali saat diajukan; "Ajukan tarik materi" menggantikan "Ajukan publikasi" bila terbit; "Buat revisi" digabung ke "Ubah"); modal alasan penarikan; keterangan "Menunggu penarikan"; tab Publikasi / Penarikan di `/admin/verifikasi-materi` dengan modal tolak (catatan wajib); jenis notifikasi baru di `/notifikasi`. Komponen `PerbandinganVersi` (dua kolom, label "Berubah", sorot kata dengan paket npm `diff`/jsdiff — paket baru, BSD-3) dipakai di dialog konfirmasi "Ajukan publikasi" materi yang pernah terbit (KP-10.7) **dan** di halaman tinjau admin `/admin/verifikasi-materi/[revisiId]` (KP-11.2). Keterangan "Versi publik: vN" di `/materi/[slug]` menjadi tautan yang membuka versi publik di kolom baca (KP-16.4); pembacaan versi lama ikut menampilkan judul, ringkasan, sampul, dan kategori versi itu (KP-13.4).

**Skenario alur (cocok untuk demo sidang)**

1. Materi X terbit dengan v2. Penulis menekan "Ubah" → revisi v3 `draf` dibuat; penulis mengganti judul, sampul, kategori, dan isi → anggota langsung melihat v3, sedangkan `/publikasi/x` tetap menampilkan v2 apa adanya.
2. Penulis mencoba mengajukan v3 → ditolak (422) dengan pesan agar menarik materi lebih dulu.
3. Penulis mengajukan tarik materi dengan alasan → semua admin mendapat notifikasi; X tetap tampil di publik; anggota melihat keterangan "Menunggu penarikan".
4. Admin menolak penarikan dengan catatan → X tetap terbit; penulis menerima catatan. Penulis mengajukan penarikan lagi → admin menyetujui → X hilang dari `/publikasi` (404) dan berstatus `draf`.
5. Penulis menekan "Ajukan publikasi" → dialog konfirmasi menampilkan v2 (versi publik terakhir) di kiri dan v3 di kanan, dengan label "Berubah" dan kata yang berubah disorot → "Ya, ajukan" → admin melihat perbandingan yang sama lalu menyetujui → X terbit lagi dengan judul, sampul, kategori, dan isi v3 (slug publik mengikuti judul baru).
6. Selama X masih terbit dengan draf v3, anggota menekan "Versi publik: v2" di halaman materi → kolom baca menampilkan v2 lengkap dengan baris "Anda membaca versi publik v2" → "Kembali ke draf v3".

**Skenario uji**

| No  | Skenario                                                                    | Hasil yang diharapkan                                                                        | KP           |
| --- | --------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- | ------------ |
| 1   | Penulis mengubah judul, ringkasan, sampul, kategori revisi `draf` materi terbit | 200; `GET /materi/{slug}` memakai data baru; `GET /publik/materi/{slug}` tetap data versi publik | 09.1, 12.4, 18.4, 19.3 |
| 2   | Buat revisi baru                                                            | Judul, ringkasan, sampul, kategori, dan isi tersalin dari revisi terakhir                    | 12.3         |
| 3   | Ajukan publikasi saat materi `terbit`                                       | 422 "Tarik materi dari publik terlebih dahulu sebelum mengajukan publikasi."                 | 10.1         |
| 4   | Penulis mengajukan penarikan dengan alasan                                  | 201; `penarikan_materi` `diajukan`; semua admin aktif menerima notifikasi; materi tetap terbit | 20.1, 20.2 |
| 5   | Ajukan penarikan tanpa alasan / materi tidak `terbit` / sudah ada yang menunggu | 422                                                                                      | 20.1         |
| 6   | Bukan penulis mengajukan atau membatalkan penarikan                         | 403                                                                                          | 20.1, 20.3   |
| 7   | Penulis membatalkan penarikan                                               | `dibatalkan`; hilang dari antrean admin; membatalkan lagi → 422                              | 20.3         |
| 8   | Antrean penarikan admin                                                     | Hanya `diajukan`, terlama di atas, memuat judul, penulis, alasan, tanggal                    | 20.4         |
| 9   | Admin menyetujui penarikan                                                  | Materi `draf`; publik 404; `diterbitkan_pada` tetap; revisi `disetujui` lama utuh; penulis menerima notifikasi | 20.5, 17.3 |
| 10  | Admin menolak penarikan tanpa / dengan catatan                              | 422 / materi tetap terbit; penulis menerima catatan                                          | 20.6         |
| 11  | Admin memutuskan penarikan yang sudah dibatalkan/diputuskan                 | 422                                                                                          | 20.7         |
| 12  | Anggota lain membuka materi yang penarikannya menunggu (cek respons API)     | Keterangan "Menunggu penarikan" ada; alasan & catatan **tidak** dikirim                      | 20.7, 16.4   |
| 13  | Setelah ditarik, penulis mengajukan publikasi lalu admin menyetujui         | Materi terbit; data publik (judul, slug, ringkasan, sampul, kategori) sama dengan revisi itu | 11.3         |
| 14  | Hapus materi yang pernah terbit lalu ditarik                                | 422                                                                                          | 09.2         |
| 15  | Penulis membatalkan pengajuan publikasi                                     | Revisi & materi kembali `draf`                                                               | 10.6         |
| 16  | Sampul diganti pada revisi `draf` materi terbit                             | Berkas sampul versi publik tetap ada di server                                               | 18.4         |
| 17  | Admin menghapus kategori yang hanya dipakai versi lama                      | 422 "Kategori masih dipakai oleh materi."                                                    | 19.1         |
| 18  | Cari `/materi?q=` dengan kata dari judul revisi `draf` baru                 | Materi ditemukan; `/publik/materi?q=` dengan kata itu **tidak** menemukan                    | 16.1, 17.5   |
| 19  | `GET /materi/{id}/riwayat/{versiPublik}` setelah judul & kategori draf diubah | Judul, ringkasan, sampul, kategori, dan isi versi publik sesuai saat disetujui               | 13.4         |
| 20  | `GET /materi/{slug}` dan `GET /materi/{materi}/edit` materi yang sudah ditarik | `versi_publik_terakhir` tetap berisi id & nomor versi yang dulu disetujui                    | 10.7, 16.4   |
| 21  | Penulis menekan "Ajukan publikasi" pada materi yang pernah terbit (peramban) | Dialog perbandingan dua kolom tampil; bagian berbeda berlabel "Berubah"; kata berubah disorot; "Batal" tidak mengirim apa pun | 10.7 |
| 22  | Penulis menekan "Ajukan publikasi" pada materi yang belum pernah terbit    | Konfirmasi biasa tanpa perbandingan                                                          | 10.7         |
| 23  | Anggota mengklik "Versi publik: vN" di halaman materi                      | Versi publik tampil lengkap di kolom baca + baris "Anda membaca versi publik vN" + tombol kembali | 16.4 |
| 24  | Admin membuka tinjau revisi materi yang pernah terbit                       | Perbandingan dua kolom yang sama dengan dialog penulis                                       | 11.2         |

---

### F16 — Program & Dokumentasi (UC-18, UC-19)

**Tujuan:** organisasi dapat menampilkan program kegiatan rutinnya — penjelasan rinci, jadwal, PIC, dan dokumentasi foto/video — kepada pengunjung dan anggota. Hanya admin yang mengelola.

**Backend:**
- Migrasi `program`, `pic_program` (pivot ke `anggota`), `dokumentasi_program`; model `Program` (konstanta status, `slugUnik()`, scope `denganJumlahDokumentasi` & `urutanTampil`, `semuaBerkas()`) dan `DokumentasiProgram` (konstanta jenis, `ambilIdYoutube()`).
- `SimpanProgramRequest` (nama unik, penjelasan tidak boleh kosong setelah tag dibuang, PIC minimal satu & harus anggota aktif, sampul JPG/PNG 2 MB) dan `UnggahDokumentasiRequest` (aturan per jenis: foto 2 MB, MP4 50 MB, tautan YouTube).
- `Admin\ProgramController` (index, show, store, update lewat POST multipart, destroy), `Admin\DokumentasiProgramController` (store, destroy), `ProgramPublikController` (index, show, dokumentasi).
- Resource publik hanya mengirim nama PIC (`ProgramResource`, `ProgramDetailResource`); admin memakai `ProgramAdminResource` (dengan `anggota_id`).
- Berkas sampul/dokumentasi dihapus dari disk setelah barisnya terhapus.
- `backend/Dockerfile`: `upload_max_filesize=55M`, `post_max_size=60M`.

**Frontend:** `useProgram`; halaman `/program`, `/program/[slug]` (tab Tentang program / Dokumentasi), `/admin/program`, `/admin/program/baru`, `/admin/program/[id]` (tab Informasi / Dokumentasi); komponen `KartuProgram`, `LencanaStatusProgram`, `GaleriDokumentasi`, `PenampilDokumentasi`, `PemilihPic`, `FormProgram`, `UnggahDokumentasi`; menu publik Struktur → Program; menu admin grup "Kegiatan". `EditorQuill` dapat memuat isi awal dari HTML (penjelasan program hanya disimpan sebagai HTML).

**Skenario alur (cocok untuk demo sidang)**

1. Admin membuka Program → Tambah program → mengisi nama, ringkasan, jadwal, sampul, memilih dua PIC (ketua dan seorang anggota), menulis penjelasan → Simpan → diarahkan ke tab Dokumentasi.
2. Admin memilih tiga foto sekaligus (satu di antaranya 3 MB) → foto 3 MB langsung ditandai "Ukuran foto maksimal 2 MB." dan tidak dikirim; dua lainnya terunggah satu per satu.
3. Admin mengunggah satu video MP4 (± 30 MB) dan menempelkan satu tautan YouTube.
4. Pengunjung (tanpa login) membuka menu Program → kartu program → tab Dokumentasi → menyaring Video → memutar video MP4 dan YouTube.
5. Admin menghapus satu foto → hilang dari galeri publik dan berkasnya terhapus dari server.

**Skenario uji**

| No  | Skenario                                                                      | Hasil yang diharapkan                                                              | KP     |
| --- | ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ------ |
| 1   | Admin menambah program lengkap dengan sampul                                  | 201; slug otomatis; HTML penjelasan tersanitasi; sampul tersimpan                  | 21.1   |
| 2   | Nama sama / penjelasan kosong / PIC kosong                                    | 422 "Nama program sudah ada." / "Penjelasan program wajib diisi." / "Pilih minimal satu PIC." | 21.1, 21.2 |
| 3   | Sampul PDF atau > 2 MB                                                        | 422                                                                                | 21.1   |
| 4   | Anggota biasa / tamu mengelola program atau dokumentasi                       | 403 / 401                                                                          | 21.1   |
| 5   | Admin mengubah nama, status, sampul, dan PIC                                  | 200; slug mengikuti nama baru; sampul lama terhapus                                | 21.1   |
| 6   | PIC anggota nonaktif / tidak ada                                              | 422 "PIC harus anggota aktif."                                                     | 21.2   |
| 7   | PIC dua orang                                                                 | 201; kedua nama tampil                                                             | 21.2   |
| 8   | Unggah foto JPG, video MP4, tautan youtu.be                                   | 201; tiap butir punya `url`; YouTube punya `embed` & `thumbnail`                   | 21.3   |
| 9   | Foto > 2 MB / video > 50 MB                                                   | 422 "Ukuran foto maksimal 2 MB." / "Ukuran video maksimal 50 MB."                  | 21.3   |
| 10  | Foto GIF / video AVI / tautan Vimeo                                           | 422 dengan pesan sesuai PRD                                                        | 21.3   |
| 11  | Hapus satu dokumentasi                                                        | 200; berkas hilang dari disk                                                       | 21.4   |
| 12  | Hapus program                                                                 | Program, PIC, dokumentasi, sampul, dan berkas hilang                               | 21.4   |
| 13  | `GET /publik/program` tanpa login                                             | Aktif di atas; hanya nama PIC; tanpa no HP/email/`anggota_id`; tanpa `deskripsi`   | 21.5   |
| 14  | `GET /publik/program/{slug}` / slug tidak ada                                 | 200 dengan `deskripsi` / 404                                                       | 21.5   |
| 15  | `GET /publik/program/{slug}/dokumentasi?jenis=video`                          | Hanya MP4 + YouTube, terbaru di atas, 12 per halaman                               | 21.6   |
| 16  | Header publik (peramban)                                                      | Beranda · Artikel · Program · Cara bergabung; tidak ada Struktur; bagan tetap di beranda | 21.7 |
| 17  | Video 30 MB di lingkungan Docker                                              | Terunggah (batas PHP sudah dinaikkan)                                              | 21.3   |

---

### F17 — Periode Otomatis & Riwayat Struktur (UC-03, UC-06; Draf 2.5)

**Tujuan:** admin tidak lagi mengelola periode secara manual; setiap perubahan susunan pengurus tercatat; anggota dapat menelusuri periode lama dan apa yang diubah admin, sedangkan pengunjung hanya melihat periode saat ini.

**Backend:** migrasi `riwayat_struktur`; model `RiwayatStruktur`; `PeriodeKepengurusan::saatIni()` (membuat periode tahun berjalan + menyalin susunan + mencatat `periode_baru`, dengan `lockForUpdate` di dalam transaksi), `aktif()`, `akanBerganti()`, `sebelumnya()`; `Kepengurusan::ringkasan()` dan `teksPosisi()`; `KepengurusanController` tanpa `periode_id`, menolak periode lama (422), memetakan baris ke salinannya di periode baru, mencatat riwayat; `StrukturController` dengan `publik`, `periode`, `struktur`, `riwayat` (perbandingan masuk/keluar/pindah); `Admin\PeriodeController` + `PeriodeRequest` dihapus; `GET /periode*` dipindah ke grup login.

**Frontend:** `useStruktur` (tanpa CRUD periode; `strukturPublik`, `riwayatPeriode`); `BaganStruktur` varian `ringkas` memakai `/publik/struktur` tanpa pemilih periode, varian `halaman` memancarkan periode terpilih; komponen `PanelRiwayatStruktur` dan `StrukturRiwayat` (tab admin); `StrukturPengurus` tanpa pemilih periode + peringatan "Tahun sudah berganti"; tab Periode dihapus.

**Skenario alur (cocok untuk demo sidang)**

1. Admin membuka Struktur → tab Pengurus menampilkan "Periode saat ini: 2026/2027" → menambah satu koordinator → toast; tab Riwayat → Log perubahan memuat "Menambahkan … sebagai Koordinator Divisi …" beserta nama admin.
2. (Simulasi tahun baru: ubah tanggal server/`travelTo` di tes) admin mengubah jabatan seseorang → periode "2027" muncul otomatis, susunan 2026/2027 tersalin, dan log periode 2027 diawali "Periode 2027 dimulai otomatis; n pengurus disalin…".
3. Admin mencoba mengubah baris periode lama lewat API → 422 "Periode lama hanya dapat dibaca.".
4. Anggota membuka `/struktur` → memilih periode lama → bagan berganti; bagian Riwayat menampilkan Masuk/Keluar/Pindah jabatan dibanding periode sebelumnya.
5. Pengunjung membuka beranda → bagan periode saat ini tanpa pemilih; `GET /api/periode` tanpa token → 401.

**Skenario uji**

| No  | Skenario                                                              | Hasil yang diharapkan                                                         | KP          |
| --- | --------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ----------- |
| 1   | Tambah pengurus saat belum ada periode                                | Periode tahun berjalan dibuat; log `periode_baru` + `tambah`                  | 07.1, 07.7  |
| 2   | Tambah pengurus pada periode aktif tahun berjalan                     | Tidak ada periode baru; log berisi kalimat, admin, data sesudah               | 07.3, 07.7  |
| 3   | Ubah pengurus setelah tahun berganti                                  | Periode baru + salinan susunan; perubahan hanya pada salinan; arsip utuh      | 07.1        |
| 4   | Ubah/hapus baris periode tidak aktif                                  | 422 "Periode lama hanya dapat dibaca."                                        | 07.6        |
| 5   | Anggota yang sama ditambahkan dua kali                                | 422 "Anggota ini sudah tercatat pada periode ini."                            | 07.4, 07.6  |
| 6   | Pengurus dari anggota nonaktif                                        | 422; tidak ada periode baru tertinggal                                        | 07.3        |
| 7   | Ubah tanpa perbedaan / hapus                                          | Tidak dicatat / dicatat "Menghapus … dari susunan pengurus."                  | 07.7        |
| 8   | `POST /admin/periode`                                                 | 404 (rute dihapus)                                                            | 07.1        |
| 9   | `GET /publik/struktur` tanpa login                                    | Struktur periode aktif; `data` null bila belum ada periode                    | 01.2        |
| 10  | `GET /periode`, `/periode/{id}/struktur`, `/periode/{id}/riwayat` tanpa login | 401                                                                   | 01.3, 07.8  |
| 11  | Anggota membuka struktur periode lama                                 | 200; inti di atas, per divisi sesuai urutan jabatan                           | 01.3, 01.4  |
| 12  | Riwayat periode dengan pembanding                                     | Masuk/keluar/pindah benar; log terbaru di atas dengan nama admin              | 07.8        |
| 13  | Riwayat periode pertama                                               | `periode_sebelumnya` dan `perbandingan` null                                  | 07.8        |
| 14  | Struktur publik tidak memuat data pribadi                             | Tanpa email, no HP, alamat, alasan, foto                                      | 01.5        |
| 15  | Beranda & `/struktur` di peramban                                     | Beranda tanpa pemilih periode; `/struktur` dengan pemilih + Riwayat perubahan | 01.2, 01.3, 07.8 |

---

### F18 — Kenyamanan Pengguna (Draf 2.8)

Beranda menampilkan artikel & program terbaru; tombol Bagikan; kontak organisasi di kaki halaman (diatur admin); tombol A− / A+ ukuran tulisan; notifikasi komentar baru; halaman `/profil`; statistik di dasbor admin. Tes: `FiturKenyamananTest` (kontak, notifikasi komentar tanpa duplikat, profil, ganti kata sandi, jumlah dibaca tanpa mengubah `updated_at`, statistik). Fitur tampilan (beranda, bagikan, A− / A+) dicek manual di peramban.

---

### F12 — Penyempurnaan & Pengujian Akhir (_Cutover_)

- [ ] Tampilan responsif di ponsel (KNF-04), termasuk halaman `/publikasi` (grid kartu menjadi satu kolom).
- [ ] Keadaan kosong, memuat, dan galat di setiap halaman.
- [ ] Cek N+1 di semua daftar (log `preventLazyLoading` bersih).
- [ ] Koleksi Postman rapi per modul, diekspor ke repo.
- [ ] Tabel _Black Box_ lengkap untuk semua KP (format PRD §15), diisi hasil aktual.
- [ ] README cara menjalankan (lokal dan Docker); data contoh untuk demo.
- [ ] Repo yang baru di-_clone_ dapat dijalankan dengan `docker compose up -d --build` hanya dengan menyalin `.env.docker` dan menjalankan migrasi (bukti KNF-07).
- [ ] Tangkapan layar setiap halaman untuk Bab IV.
- [ ] Uji coba oleh pengurus dan catat umpan baliknya.

---

## 5. Perintah Claude Code per fitur

Cara utama: skill `/fitur` (berkas `.claude/skills/fitur/SKILL.md`).

```
/fitur F5 backend
/fitur F5 frontend
```

Sebelum mengetik perintah, tekan `Shift+Tab` sampai muncul **plan mode** agar Claude Code menyusun rencana dulu dan baru mengubah berkas setelah Anda setujui. Untuk bagian _frontend_, tata letak halaman dibahas dulu dengan Anda.

Versi prompt lengkap (jika tidak memakai `/fitur`):

```
Baca docs/PRD.md dan docs/ROADMAP.md. Kerjakan fitur F5 (Materi: Tulis & Kelola),
bagian BACKEND saja, mengikuti skill backend-laravel. Tampilkan rencana singkat
(berkas yang dibuat/diubah) sebelum menulis kode. Setelah selesai, jalankan tes,
jelaskan cara menguji setiap endpoint di Postman, dan sebutkan KP mana yang sudah terpenuhi.
```

## 6. Log implementasi (isi setiap selesai fitur)

| Tanggal | Fitur | KP lulus | Kendala | Solusi | Commit |
| ------- | ----- | -------- | ------- | ------ | ------ |
| 2026-11-10 | F9 — laci diskusi & balasan dimuat saat dibuka (backend + frontend) | KP-14.5 (baru); KP-14.1–14.4, KP-15.1–15.2 tetap lulus | Daftar komentar ikut mengirim seluruh balasan sehingga muatannya berat; bilah format komposer ada di bawah kotak tulis; laci diskusi sempat memakai komposer yang sama dengan halaman sehingga gaya pil `sticky` milik editor materi ikut terbawa | Daftar hanya mengirim induk + `jumlah_balasan`, balasan diambil dari `GET /komentar/{komentar}/balasan` (PRD §11, KP-14.5); forum dipindah ke laci kanan `PanelDiskusi` 560 px; siaran Reverb menambah angka balasan, atau menempelkan balasannya bila induknya sedang terbuka; gaya pil `.ql-toolbar` dilepas di mode komentar | — |
| 2026-11-10 | F13 — konten beranda & sampul artikel (backend + frontend) | KP-18.1 s.d. KP-18.5 | `GET /publik/beranda` sempat mengembalikan 201 karena `firstOrCreate` menandai model sebagai baru; `firstOrCreate(['id' => 1])` juga gagal karena `id` tidak `$fillable` dan urutan *sequence* PostgreSQL tidak ikut di-*rollback* saat tes | Endpoint baca memakai `pengaturan() ?? new PengaturanBeranda()` (tidak pernah membuat baris), penyimpanan memakai `pengaturanAtauBuat()`; formulir admin memakai `POST` (bukan `PUT`) karena mengirim berkas | — |
| 2026-11-10 | F8/F16 — pencarian arsip & materi saya | KP-16.1, KP-16.3 | Kolom cari penulis terpisah membuat dua gerbang; `plainto_tsquery` tidak mencocokkan awalan kata sehingga hasil baru muncul setelah kata lengkap | Satu `SearchPill` real-time (jeda ±350 ms) di `/materi` dan `/materi-saya`; `to_tsquery` berawalan `:*` untuk judul + isi revisi terakhir; nama penulis digabung lewat `orWhereHas`; parameter `penulis` tetap tersedia di API | — |
| 2026-11-10 | F9 — isi versi lama dapat dibaca kembali (backend + frontend) | KP-13.4 (baru); KP-13.1–13.3, KP-12.1–12.5 tetap lulus | Riwayat versi hanya memuat ringkasan, jadi isi versi lama tidak dapat dibuka dari antarmuka (datanya sendiri tidak pernah hilang). Satu tes sempat "gagal" karena `RequestGuard` mengingat pengguna dari request pertama, sehingga request kedua dalam satu metode tes berjalan sebagai pengguna pertama | Endpoint `GET /materi/{materi}/riwayat/{revisi}` (PRD §11, KP-13.4) memuat isi satu versi dan 404 bila versi bukan milik materi itu; aksi "Lihat isi versi ini" per baris riwayat mengganti isi kolom baca dengan baris arsip + "Kembali ke versi terbaru"; tiap metode tes hanya memakai satu identitas (atau `forgetGuards()` bila perlu berpindah pengguna) | — |
| 2026-11-10 | F14 — kategori materi (backend) | KP-19.1 s.d. KP-19.4 | Fitur ini belum ada di PRD (semula §14 no. 6 "Tidak ada"), jadi PRD diamandemen lebih dulu (KF-19, UC-15, T14). Aturan `kategori` yang baru membuat seluruh tes lama yang menyimpan materi lewat API gagal 422; `slugUnik()` kini terduplikasi di `MateriController` dan `Admin\KategoriController` | Kategori dibuat sebagai relasi banyak-ke-banyak (`kategori` + pivot `kategori_materi`) dengan `sync()` di dalam transaksi; katakan `kategori` wajib (`required|array|min:1`) dan `sync()` dilewati saat `diterbitkan_pada` sudah terisi (KP-19.3); penyaring memakai slug lewat `whereHas`; duplikasi `slugUnik()` dibiarkan agar tiap controller tetap sederhana dan mandiri (kandidat dirapikan di F12 bila diperlukan); semua tes lama ditambahi `kategori` | — |
| 2026-11-10 | F14 — kategori materi (frontend) | KP-19.1 s.d. KP-19.4 | Pil kategori pada kartu/baris menuju `?kategori=slug` di halaman yang sama, sedangkan Vue Router tidak memasang ulang halaman itu sehingga penyaringnya tidak ikut berubah; isian pencarian dan penyaring lain dapat memicu muat ulang berganda; `.seg` di `main.css` hanya satu pil yang tidak membungkus, tidak cocok untuk jumlah kategori yang bebas | Komponen baru `ChipKategori`, `FilterKategori`, dan `PemilihKategori`; tiap halaman daftar memantau `route.query.kategori` agar pilihan disamakan dengan URL; penyaring dijalankan satu pengawas gabungan per halaman (`kataKunciDicari` + `tab` + `kategori`) sehingga satu perubahan = satu muat ulang; pil kategori dibuat sendiri (membungkus ke baris berikutnya, atau satu baris bergulir mendatar di ponsel) alih-alih memakai `.seg` | — |
| 2026-11-10 | F14 — peringkas & pencarian kategori (frontend) | KP-19.1, KP-19.2, KP-19.4 | Kategori akan bertambah banyak, sehingga daftar pil di sidebar dan di editor dapat memanjang dan mendorong bagian lain ke bawah; `SearchPill` tidak dapat dipakai di editor karena ia membungkus dirinya dalam `<form>` sementara editor sudah berupa `<form>` (form bersarang tidak sah), dan Enter di kotak cari akan ikut mengirim formulir | Kedua komponen kategori meringkas daftar jadi 8 pil + tombol teks "Selengkapnya (n)" / "Sembunyikan" (`aria-expanded`) dan menambah kotak cari pil (penyaring di sidebar menampilkannya setelah dibuka, pemilih editor menampilkannya langsung); pil yang dipilih selalu ikut tampil walau daftar ringkas; kotak cari di editor memakai `<input>` biasa dengan `@keydown.enter.prevent`; ambang 8 pil dipilih agar peringkas muncul pada jumlah kategori yang wajar, dan seeder contoh ditambah jadi 9 kategori supaya terlihat saat demo | — |
| 2026-11-11 | F15 — penarikan materi & bagian materi per versi (backend) | KP-20.1 s.d. KP-20.7; KP-09.1, KP-10.1, KP-10.6 (rute `batal-ajukan` yang sebelumnya belum ada), KP-10.7 (data `versi_publik_terakhir`), KP-11.3, KP-12.3, KP-12.4, KP-13.4, KP-18.4, KP-19.1, KP-19.3 versi Draf 2.3; 172 tes lulus | Judul, ringkasan, sampul, dan kategori hanya ada satu salinan di tabel `materi`, sehingga mengubahnya pada draf ikut mengubah halaman publik; `slugUnik()` dibutuhkan tiga controller; menghapus berkas sampul lama dapat merusak riwayat versi; tes yang berganti pengguna dalam satu metode terkena `RequestGuard` yang mengingat pengguna pertama | Kolom judul/ringkasan/sampul + pivot `kategori_revisi` di tingkat versi (data lama disalin oleh migrasi), kolom di `materi` menjadi data versi publik yang disamakan lewat `Materi::samakanDenganRevisi()` (saat draf disimpan selama tidak terbit, saat publikasi disetujui, dan saat penarikan disetujui); `slugUnik()` dipindah ke model; berkas sampul hanya dihapus bila tidak dipakai versi mana pun; `bisa_ubah_judul` sementara selalu `true` agar editor lama tetap jalan sampai frontend F15; tes baru memakai helper `sebagai()` yang memanggil `forgetGuards()` | — |
| 2026-11-11 | F15 — penarikan materi & bagian materi per versi (frontend) | Sisi antarmuka KP-10.6, KP-10.7, KP-12.4, KP-13.4, KP-16.4, KP-19.3, KP-20.1 s.d. KP-20.7 (perlu dicek manual di peramban); backend tetap 172 tes lulus | Perbandingan versi butuh sorot kata, sedangkan `AppModal` hanya selebar `max-w-md`; dua tab antrean admin perlu angka masing-masing; `.compare` lama di `main.css` memakai hex hijau/merah di luar token; `bisa_ubah_judul` sementara di backend | Paket `diff@8.0.4` (jsdiff, BSD-3, versi persis) untuk `diffWords`; komponen baru `PerbandinganVersi` (dua kolom, label "Berubah", isi dibandingkan sebagai teks polos per paragraf, tab di ponsel) dipakai di `DialogAjukanPublikasi` dan halaman tinjau admin; `ModalTarikMateri` untuk alasan penarikan; `AppModal` mendapat prop `lebar`; `StatCard` mendapat prop `keterangan`; antrean admin memuat kedua antrean sekaligus dan menyimpan tab di `?tab=`; `.compare` dihapus; `bisa_ubah_judul` dihapus dari backend & tipe; satu baris `diterbitkan_pada` ditambah di blok `materi` pada `RevisiResource` untuk subteks antrean | — |
