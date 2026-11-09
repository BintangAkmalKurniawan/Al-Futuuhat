# Peta Jalan Koding Per Fitur — Sistem Al-Futuuhat

| | |
|---|---|
| **Dasar** | `PRD-Sistem-Al-Futuuhat.md` / `docs/PRD.md` (Draf 2.1, 9 November 2026) |
| **Metode** | RAD — fase *User Design* ↔ *Construction* diulang per fitur |
| **Status** | Draf 2.1 — 9 November 2026 (Draf 2: F0 memakai pengembangan lokal + Dockerfile per layanan; Draf 2.1: Claude Code menggantikan Codex) |

> Dokumen ini menjelaskan **urutan** dan **skenario** pengerjaan. *Apa* yang dibangun tetap mengacu ke PRD. Kode KF/KP/UC di bawah merujuk ke PRD.

---

## 1. Pendekatan: fondasi dulu, lalu per fitur (*vertical slice*)

Ada tiga cara umum memulai:

| Pendekatan | Cara | Kelebihan | Kekurangan |
|---|---|---|---|
| **Per lapisan** (PRD §14 saat ini) | Semua *backend* selesai → baru semua *frontend* | Fokus satu teknologi dalam satu waktu | Baru ada yang bisa dilihat/didemokan setelah berminggu-minggu; kesalahan desain API baru ketahuan saat integrasi |
| **Per fitur murni** | Langsung fitur 1 dari nol, termasuk menyiapkan proyek di dalamnya | Cepat terlihat hasil | Fondasi (Docker, token, layout) dibuat setengah-setengah lalu dirombak |
| **Fondasi + per fitur** ✅ | Siapkan fondasi sekali, lalu setiap fitur dikerjakan tuntas *backend* → Postman → *frontend* → uji | Setiap fitur bisa didemokan; sesuai siklus RAD; mudah melapor progres ke pembimbing | Perlu disiplin: jangan pindah fitur sebelum KP lulus |

**Rekomendasi: Fondasi + per fitur.** Alasannya:
1. **Sesuai RAD.** Fase *User Design* dan *Construction* pada RAD berjalan berulang dengan umpan balik pengguna. Mengerjakan per fitur membuat setiap modul bisa ditunjukkan ke pengurus (mis. Fauzi/Ivan), lalu diperbaiki sebelum lanjut. *(Jika ini ditulis di skripsi, sertakan rujukan RAD — [SITASI DIPERLUKAN].)*
2. **Desain dibahas per modul.** Tata letak dan alur setiap halaman didiskusikan tepat sebelum modul itu dibangun.
3. **Risiko integrasi kecil.** Kesalahan kontrak API langsung ketahuan saat *frontend* fitur yang sama dikerjakan.
4. **Bahan Bab IV dan pengujian terkumpul bertahap.** Setiap fitur selesai = tangkapan layar + tabel *Black Box* bagian itu sudah terisi.

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
- **Satu fitur = satu branch.** Untuk Claude Code: satu sesi untuk *backend*, satu sesi untuk *frontend* fitur yang sama (`/clear` di antaranya).
- **Jangan lanjut ke fitur berikutnya** sebelum semua KP fitur tersebut lulus.
- **Pahami kode yang dihasilkan Claude Code.** Saat sidang, penguji dapat menanyakan potongan kode mana pun. Bacalah setiap *controller* dan *policy* sebelum *commit*.
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
                     └─▶ F5 Materi: Tulis & Kelola Draf
                          └─▶ F6 Pengajuan & Verifikasi Publikasi
                               └─▶ F7 Revisi & Riwayat Versi ...... ◆ M3: modul materi selesai
                                    └─▶ F8 Arsip, Pencarian & Baca
                                         └─▶ F9 Forum Diskusi Real-time  ◆ M4
                                              └─▶ F10 Notifikasi & Dasbor
                                                   └─▶ F11 Penyempurnaan & Pengujian Akhir
```

Mengapa urutannya demikian:
- **Keanggotaan dulu**, karena semua fitur lain membutuhkan akun aktif dan peran admin/anggota.
- **Struktur organisasi setelah data anggota**, karena pengurus dipilih dari anggota aktif.
- **Materi dipecah menjadi tiga** (draf → verifikasi → revisi), karena alur status materi adalah bagian paling rumit dan paling rawan salah.
- **Pencarian sebelum forum**, karena forum menempel pada halaman baca materi.
- **Dasbor terakhir**, karena isinya adalah ringkasan dari semua modul sebelumnya.

### Pemetaan ke tugas PRD §14

| Fitur | Tugas PRD §14 | KF |
|---|---|---|
| F0 | T1, T2, sebagian T7 | — |
| F1 | sebagian T3, T7, T8 | KF-02, KF-03, KF-04 |
| F2 | sebagian T3, T9 | KF-05 |
| F3 | sebagian T4, T9 | KF-06 |
| F4 | sebagian T4, T8, T9 | KF-01, KF-07 |
| F5 | sebagian T5, T10 | KF-08, KF-09 |
| F6 | sebagian T5, T10 | KF-10, KF-11 |
| F7 | sebagian T5, T10 | KF-12, KF-13 |
| F8 | sebagian T6, T11 | KF-16 |
| F9 | sebagian T6, T11 | KF-14, KF-15 |
| F10 | sebagian T5, T11 | notifikasi & dasbor (pendukung) |
| F11 | T12 | semua |

---

## 4. Skenario per fitur

### F0 — Fondasi

**Tujuan:** proyek bisa dijalankan secara lokal dan di Docker, basis data lengkap, dan *frontend* bisa memanggil API.

**Lingkungan (PRD §13)**
- Lokal: Laravel Herd (PHP 8.4, Composer, Node 24); basis data `alfutuuhat` di kontainer `postgres-umum`.
- Docker: `backend/Dockerfile`, `frontend/Dockerfile`, `.dockerignore` masing-masing, `docker-compose.yml`, `backend/.env.docker.example`; tambahkan `.env.docker` ke `backend/.gitignore`.

**Backend**
- Laravel di `backend/`, pasang Sanctum (`install:api`) dan Reverb (`install:broadcasting`), atur CORS, `php artisan storage:link`.
- **Semua** migrasi, model (`protected $table`), relasi, *factory*, dan *seeder* sesuai PRD §10 dibuat sekarang (ERD sudah final, sehingga tidak perlu migrasi bolak-balik).
- *Seeder*: admin dari `.env`, 6 divisi, jabatan Ketua & Koordinator. *Seeder* data contoh (memakai *factory*) hanya dipanggil jika lingkungan `local`, karena image Docker tidak memuat paket *dev*.
- Aktifkan `Model::preventLazyLoading()` di lingkungan lokal (KNF-09).

**Frontend**
- Nuxt di `frontend/` (`ssr: false`), Tailwind.
- `runtimeConfig.public`: `apiBase`, `reverbKey`, `reverbHost`, `reverbPort`, `reverbScheme` (diisi lewat `NUXT_PUBLIC_*`).
- Composable `useApi` (basis URL, *header* `Authorization: Bearer`, tangani 401).
- Tiga layout kosong: `publik`, `anggota`, `admin`.

**Selesai jika**
- [ ] Lokal: `php artisan serve`, `php artisan reverb:start`, dan `npm run dev` berjalan bersamaan.
- [ ] `php artisan migrate:fresh --seed` sukses; admin dan 6 divisi ada di basis data.
- [ ] Halaman Nuxt terbuka dan dapat memanggil satu *endpoint* uji.
- [ ] `docker compose up -d --build` menjalankan `db`, `backend`, `reverb`, `frontend`; setelah migrasi di kontainer, halaman di `localhost:3000` dapat memanggil *endpoint* uji yang sama.

---

### F1 — Pendaftaran & Login (UC-01, UC-02)

**Tujuan:** pengunjung dapat mendaftar; pengguna dapat login/logout sesuai status dan peran.

**Backend:** `POST /register`, `POST /login`, `POST /logout`, `GET /me`; *middleware* peran admin dan status aktif.
**Frontend:** `/daftar`, `/daftar/berhasil`, `/masuk`; composable `useAuth`; *middleware* `auth`, `guest`, `admin`; kerangka menu layout anggota & admin.

**Skenario alur**
1. Pengunjung membuka `/daftar`, mengisi formulir, lalu mengirim.
2. Sistem menyimpan `users` (status `menunggu`) + `anggota` dalam satu transaksi, lalu mengarahkan ke `/daftar/berhasil`.
3. Pendaftar mencoba login → ditolak karena masih menunggu.
4. Admin hasil *seeder* login → diarahkan ke `/admin`.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Daftar dengan data lengkap dan valid | Tersimpan, diarahkan ke halaman berhasil | 02.1, 03.1, 03.2 |
| 2 | Email sudah terdaftar | "Email sudah terdaftar." | 02.2 |
| 3 | Kata sandi 7 karakter / konfirmasi berbeda | Pesan kesalahan di bawah isian | 02.3 |
| 4 | Foto PDF / foto 3 MB | Ditolak dengan pesan | 02.4 |
| 5 | Kirim dengan isian salah | Isian lain tidak hilang | 02.5 |
| 6 | Login dengan email/kata sandi salah | "Email atau kata sandi tidak sesuai." | 04.1 |
| 7 | Login akun `menunggu` | Pesan menunggu verifikasi | 03.3 |
| 8 | Login akun `ditolak` (ubah status lewat basis data dulu) | Pesan penolakan + alasan | 04.2 |
| 9 | Login akun `nonaktif` | "Akun Anda dinonaktifkan. Hubungi pengurus." | 04.3 |
| 10 | Login anggota aktif / admin | Ke `/dasbor` / ke `/admin` | 04.4 |
| 11 | Anggota membuka `/admin` dan memanggil API admin | Dialihkan; API 403 | 04.5 |
| 12 | Logout lalu panggil `/me` dengan token lama | 401 | 04.6 |

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

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Buka daftar pendaftar | Hanya status `menunggu`; filter `ditolak` berfungsi | 05.1 |
| 2 | Buka detail | Foto dan alasan bergabung tampil | 05.2 |
| 3 | Setujui | Status `aktif`; `tanggal_bergabung` & `diverifikasi_pada` terisi | 05.3 |
| 4 | Tolak tanpa catatan | 422 | 05.4 |
| 5 | Tolak dengan catatan | Status `ditolak`, catatan tersimpan | 05.4 |
| 6 | Putuskan ulang pendaftar yang sudah diputuskan | 422 | 05.5 |
| 7 | Anggota memanggil endpoint ini | 403 | 04.5 |

---

### F3 — Data Anggota (UC-05)

**Backend:** `GET/PUT /admin/anggota...`, `PATCH .../status`, `PATCH .../password`.
**Frontend:** `/admin/anggota` (tabel berpaginasi, cari, filter status), `/admin/anggota/[id]` (ubah data & peran, aktifkan/nonaktifkan, atur ulang kata sandi).

**Skenario alur**
1. Admin mencari anggota A, mengubah nomor HP-nya.
2. A sedang login di peramban lain; admin menonaktifkan A → permintaan berikutnya dari A ditolak (token terhapus).
3. Admin mengaktifkan kembali A dan mengatur ulang kata sandinya → A login dengan kata sandi baru.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Cari berdasarkan nama/email, filter status | Hasil sesuai, berpaginasi | 06.1 |
| 2 | Ubah nama, HP, alamat, peran | Tersimpan | 06.2 |
| 3 | Nonaktifkan akun yang sedang login | Token terhapus, akun tidak bisa memakai API | 06.3 |
| 4 | Aktifkan kembali | Bisa login lagi | 06.3 |
| 5 | Atur ulang kata sandi | Login dengan kata sandi baru berhasil, yang lama gagal | 06.4 |
| 6 | Admin menonaktifkan / menurunkan peran dirinya sendiri | Ditolak dengan pesan | 06.5 |

**Cek N+1:** daftar anggota harus memuat relasi `anggota` dengan *eager loading*.

**◆ Milestone M1** — modul keanggotaan selesai. Waktu yang baik untuk demo pertama ke pengurus.

---

### F4 — Struktur Organisasi: Admin + Beranda Publik (UC-06, UC-03)

**Urutan di dalam fitur:** periode → divisi & jabatan → pengurus → tampilan publik.

**Backend:** CRUD `/admin/periode`, `/admin/divisi`, `/admin/jabatan`, `/admin/kepengurusan`, `PATCH /admin/periode/{}/aktifkan`; publik `GET /divisi`, `GET /periode`, `GET /periode/{periode}/struktur`.
**Frontend:** `/admin/struktur` (tab Periode, Divisi, Jabatan, Pengurus); `/` (profil, divisi, struktur akordeon, cara bergabung); `/struktur` untuk anggota.

**Skenario alur**
1. Admin membuat periode 2025/2026 dan 2026/2027, lalu mengaktifkan 2026/2027.
2. Admin menambah jabatan (mis. Sekretaris), lalu mengisi pengurus: Ketua (tanpa divisi), Koordinator per divisi, dan anggota divisi.
3. Pengunjung (tanpa login) membuka beranda → melihat struktur periode aktif, lalu berganti ke periode lain.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Aktifkan periode B saat A aktif | A otomatis nonaktif | 07.1 |
| 2 | CRUD divisi & jabatan | Berhasil; urutan jabatan tersimpan | 07.2 |
| 3 | Tambah pengurus inti (divisi kosong) | Tersimpan | 07.3 |
| 4 | Anggota yang sama dua kali di periode yang sama | 422 dengan pesan | 07.4 |
| 5 | Hapus divisi/jabatan/periode yang masih dipakai | Pesan jelas, bukan error 500 | 07.5 |
| 6 | Buka beranda tanpa login | Terbuka; periode aktif tampil pertama | 01.1, 01.2 |
| 7 | Ganti periode | Struktur berubah | 01.3 |
| 8 | Lihat susunan | Pengurus inti di atas; per divisi dapat dibuka-tutup; urut sesuai `jabatan.urutan` | 01.4 |
| 9 | Periksa respons API publik di Postman | Tidak ada no HP, alamat, email, alasan, foto | 01.5 |

**Perlu disiapkan:** teks profil organisasi dan "cara bergabung" untuk beranda (minta dari pengurus).

**◆ Milestone M2** — tujuan keanggotaan + struktur tercapai.

---

### F5 — Materi: Tulis & Kelola Draf (UC-07)

**Backend:** `POST /materi`, `GET /materi/{materi}/edit`, `PUT /materi/{materi}`, `DELETE /materi/{materi}`, `GET /materi-saya`, `POST /unggah-gambar`; sanitasi HTML sebelum menyimpan `konten_html` (mis. memakai paket HTML Purifier untuk Laravel).
**Frontend:** `/materi-saya` (tab per status), `/materi-saya/baru` dan `/materi-saya/[id]/edit` dengan Quill; *handler* gambar Quill mengunggah ke `/unggah-gambar` lalu menyisipkan URL.

**Skenario alur**
1. Anggota membuka "Tulis Materi", mengisi judul, ringkasan, isi berformat, dan menyisipkan gambar.
2. Simpan → muncul di tab Draf.
3. Anggota mengubah lalu menghapus salah satu draf.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Simpan materi baru | 1 baris `materi` (`draf`) + 1 `revisi_materi` (v1, `draf`) | 08.1, 08.3 |
| 2 | Sisipkan gambar | Konten berisi URL server, bukan base64 | 08.2 |
| 3 | Sisipkan gambar 3 MB / GIF | Ditolak | 08.2 |
| 4 | Kirim `<script>` di konten lewat Postman | `konten_html` tersimpan tanpa skrip | 08.4 |
| 5 | Ubah draf milik sendiri | Tersimpan | 09.1 |
| 6 | Hapus draf yang belum pernah terbit | Terhapus | 09.2 |
| 7 | Anggota lain mengubah/menghapus | 403 | 09.3 |

---

### F6 — Pengajuan & Verifikasi Publikasi (UC-08, UC-09)

**Backend:** `POST /materi/{materi}/ajukan`; `GET /admin/verifikasi-materi`, `GET /admin/revisi/{revisi}`, `POST /admin/revisi/{revisi}/verifikasi`; kelas *Notification* (kanal `database`) untuk "materi diajukan" dan "revisi diverifikasi".
**Frontend:** tombol Ajukan; `/admin/verifikasi-materi` (antrean); `/admin/verifikasi-materi/[revisiId]` (isi revisi, Setujui/Tolak + catatan). Halaman notifikasi lengkap menyusul di F10; di tahap ini notifikasi cukup dicek lewat Postman/basis data.

**Skenario alur**
1. Anggota A mengajukan materi X → semua admin mendapat notifikasi.
2. Admin menolak X dengan catatan → A melihat status `ditolak` beserta catatan.
3. Anggota A mengajukan materi Y → admin menyetujui → Y berstatus `terbit`.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Penulis mengajukan draf | Revisi `diajukan`; materi `diajukan` | 10.1, 10.2 |
| 2 | Bukan penulis mengajukan | 403 | 10.1 |
| 3 | Notifikasi admin | Semua admin aktif menerima | 10.3 |
| 4 | Ubah materi yang sedang diajukan | Ditolak | 10.4 |
| 5 | Antrean admin | Urut terlama di atas | 11.1 |
| 6 | Setujui | Revisi `disetujui`; materi `terbit`; `diterbitkan_pada` terisi | 11.3 |
| 7 | Tolak tanpa catatan / dengan catatan | 422 / materi `ditolak` | 11.4 |
| 8 | Riwayat keputusan & notifikasi penulis | Baris `verifikasi_materi` ada; penulis menerima notifikasi | 11.5 |
| 9 | Verifikasi revisi yang bukan `diajukan` | 422 | 11.6 |

---

### F7 — Revisi & Riwayat Versi (UC-10)

**Backend:** `POST /materi/{materi}/revisi`, `GET /materi/{materi}/riwayat`; lengkapi `GET /admin/revisi/{revisi}` dengan versi terbit sebagai pembanding (KP-11.2).
**Frontend:** tombol "Buat Revisi"; editor mengunci judul & ringkasan jika materi sudah pernah terbit; komponen Riwayat Versi (dipakai lagi di F8); tampilan pembanding di halaman verifikasi admin.

**Skenario alur (skenario terpenting — cocok untuk demo sidang)**
1. Materi X (v1 ditolak) → penulis membuat revisi v2 → ajukan → admin setujui → X terbit dengan v2.
2. Penulis membuat revisi v3 atas X yang sudah terbit → ajukan. Selama menunggu, pembaca tetap melihat **v2**.
3. Admin membandingkan v3 dengan v2, lalu menolak → X tetap terbit dengan v2.
4. Penulis membuat v4 → disetujui → pembaca melihat v4.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Bukan penulis membuat revisi | 403 | 12.1 |
| 2 | Buat revisi saat masih ada draf/diajukan | 422 | 12.2 |
| 3 | Buat revisi baru | `nomor_versi` +1, `draf`, isi tersalin | 12.3 |
| 4 | Ubah judul pada revisi materi terbit | Tidak berubah; versi terbit tetap tampil | 12.4 |
| 5 | Ubah/hapus revisi `disetujui` lewat Postman | Ditolak | 12.5 |
| 6 | Lihat riwayat | Nomor versi, status, tanggal, catatan, keputusan admin | 13.1, 13.2 |
| 7 | Anggota lain melihat riwayat | Hanya versi `disetujui` | 13.3 |
| 8 | Admin membuka revisi materi terbit | Versi terbit tampil sebagai pembanding | 11.2 |

**◆ Milestone M3** — alur materi lengkap.

---

### F8 — Arsip, Pencarian & Baca (UC-12)

**Backend:** `GET /materi?q=&penulis=&page=` (*full-text search* PostgreSQL atas judul + isi versi terbit; indeks GIN sudah dibuat di F0), `GET /materi/{slug}`.
**Frontend:** `/materi` (kotak cari, filter penulis, kartu hasil, paginasi); `/materi/[slug]` (isi versi terbit, penulis, tanggal, nomor versi, riwayat versi, tempat forum).

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Cari kata yang ada di judul | Ditemukan | 16.1 |
| 2 | Cari kata yang hanya ada di isi | Ditemukan | 16.1 |
| 3 | Cari kata yang hanya ada di revisi yang belum disetujui | **Tidak** ditemukan | 16.1 |
| 4 | Filter nama penulis (dengan/tanpa kata kunci) | Sesuai | 16.1 |
| 5 | Materi draf/diajukan/ditolak | Tidak muncul | 16.1 |
| 6 | Tampilan hasil | Judul, ringkasan, penulis, tanggal, jumlah komentar; berpaginasi | 16.2 |
| 7 | Kata kunci acak | "Materi tidak ditemukan." | 16.3 |
| 8 | Buka halaman baca | Versi terbit + riwayat | 16.4 |

**Catatan:** konfigurasi `simple` tidak melakukan *stemming*, sehingga "mengajar" tidak cocok dengan "ajar". Ini wajar dan perlu disebut sebagai batasan.

---

### F9 — Forum Diskusi Real-time (UC-11)

**Dikerjakan dua langkah:**
- **9a — Forum biasa:** `GET/POST /materi/{materi}/komentar`, `DELETE /komentar/{komentar}`; komponen forum di `/materi/[slug]` (teks, gambar, balas satu tingkat, hapus milik sendiri). Pastikan berjalan dengan *refresh* dulu.
- **9b — Real-time:** *event* `KomentarDibuat` & `KomentarDihapus` (`ShouldBroadcastNow`), otorisasi kanal privat `materi.{id}` di `routes/channels.php`, `/broadcasting/auth` dengan Sanctum; plugin Echo di Nuxt, berlangganan saat halaman dibuka dan berhenti saat halaman ditutup.

**Skenario alur**
1. Anggota A (Chrome) dan B (Firefox) membuka materi yang sama.
2. A berkomentar → komentar muncul di layar B tanpa memuat ulang.
3. B membalas → balasan muncul di layar A.
4. A menghapus komentarnya → komentar beserta balasannya hilang di layar B.

**Skenario uji**

| No | Skenario | Hasil yang diharapkan | KP |
|---|---|---|---|
| 1 | Forum pada materi yang belum terbit | Tidak tersedia | 14.1 |
| 2 | Komentar teks / gambar saja / keduanya | Tersimpan | 14.2 |
| 3 | Komentar tanpa teks dan tanpa gambar | 422 | 14.2 |
| 4 | Gambar 3 MB / bukan JPG/PNG | Ditolak | 14.2 |
| 5 | Balas komentar; balas sebuah balasan | Berhasil; balasan tingkat kedua ditolak/diratakan | 14.3 |
| 6 | Hapus komentar orang lain | 403 | 14.4 |
| 7 | Hapus komentar induk | Balasan ikut terhapus | 14.4 |
| 8 | Komentar baru di dua peramban | Muncul tanpa memuat ulang | 15.1 |
| 9 | Komentar dihapus di dua peramban | Hilang tanpa memuat ulang | 15.2 |
| 10 | Pengguna tidak login/nonaktif berlangganan kanal | `/broadcasting/auth` menolak | 15.3 |

**Tips:** periksa koneksi WebSocket di tab *Network → WS* pada DevTools peramban. Pastikan Reverb berjalan (lokal: `php artisan reverb:start --debug`; Docker: kontainer `reverb`). `REVERB_APP_KEY` di *backend* harus sama dengan `NUXT_PUBLIC_REVERB_KEY` di *frontend*; di Docker, `REVERB_HOST` *backend* bernilai `reverb`, sedangkan *frontend* tetap `localhost`.

**◆ Milestone M4** — semua fitur inti selesai.

---

### F10 — Notifikasi & Dasbor

**Backend:** `GET /notifikasi`, `PATCH /notifikasi/{id}/baca`, `PATCH /notifikasi/baca-semua`, `GET /dasbor`, `GET /admin/dasbor`.
**Frontend:** lencana jumlah notifikasi di *header*; `/notifikasi`; `/dasbor` (jumlah materi per status, pintasan); `/admin` (pendaftar menunggu, revisi diajukan, anggota aktif, materi terbit).

**Skenario uji:** angka dasbor sama dengan data di basis data; tandai satu/semua dibaca mengurangi lencana; klik notifikasi membuka materi/revisi terkait; anggota memanggil `/admin/dasbor` → 403.

---

### F11 — Penyempurnaan & Pengujian Akhir (*Cutover*)

- [ ] Tampilan responsif di ponsel (KNF-04).
- [ ] Keadaan kosong, memuat, dan galat di setiap halaman.
- [ ] Cek N+1 di semua daftar (log `preventLazyLoading` bersih).
- [ ] Koleksi Postman rapi per modul, diekspor ke repo.
- [ ] Tabel *Black Box* lengkap untuk semua KP (format PRD §15), diisi hasil aktual.
- [ ] README cara menjalankan (lokal dan Docker); data contoh untuk demo.
- [ ] Repo yang baru di-*clone* dapat dijalankan dengan `docker compose up -d --build` hanya dengan menyalin `.env.docker` dan menjalankan migrasi (bukti KNF-07).
- [ ] Tangkapan layar setiap halaman untuk Bab IV.
- [ ] Uji coba oleh pengurus dan catat umpan baliknya.

---

## 5. Perintah Claude Code per fitur

Cara utama: skill `/fitur` (berkas `.claude/skills/fitur/SKILL.md`).

```
/fitur F3 backend
/fitur F3 frontend
```

Sebelum mengetik perintah, tekan `Shift+Tab` sampai muncul **plan mode** agar Claude Code menyusun rencana dulu dan baru mengubah berkas setelah Anda setujui. Untuk bagian *frontend*, tata letak halaman dibahas dulu dengan Anda.

Versi prompt lengkap (jika tidak memakai `/fitur`):
```
Baca docs/PRD.md dan docs/ROADMAP.md. Kerjakan fitur F3 (Data Anggota), bagian
BACKEND saja, mengikuti skill backend-laravel. Tampilkan rencana singkat (berkas
yang dibuat/diubah) sebelum menulis kode. Setelah selesai, jalankan tes, jelaskan
cara menguji setiap endpoint di Postman, dan sebutkan KP mana yang sudah terpenuhi.
```

## 6. Log implementasi (isi setiap selesai fitur)

| Tanggal | Fitur | KP lulus | Kendala | Solusi | Commit |
|---|---|---|---|---|---|
| | | | | | |
