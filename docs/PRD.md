# PRD — Sistem Informasi Manajemen Keanggotaan dan Pengelolaan Materi Al-Futuuhat

|                         |                                                                                                                                                                                                                                                                                                                                                                                  |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dokumen**             | Product Requirements Document (PRD)                                                                                                                                                                                                                                                                                                                                              |
| **Sumber**              | Bab III skripsi "Rancang Bangun Sistem Informasi Manajemen Keanggotaan dan Pengelolaan Materi Berbasis Web pada Al-Futuuhat"                                                                                                                                                                                                                                                     |
| **Metode pengembangan** | RAD (_Requirements Planning → User Design → Construction → Cutover_)                                                                                                                                                                                                                                                                                                             |
| **Pengujian**           | _Black Box Testing_                                                                                                                                                                                                                                                                                                                                                              |
| **Status**              | Draf 2.10 — 11 November 2026 (Draf 2: lingkungan pengembangan lokal + Dockerfile per layanan; Draf 2.1: Claude Code menggantikan Codex; **Draf 2.2: koreksi alur materi — materi langsung dapat dibaca dan didiskusikan seluruh anggota sejak draf; verifikasi admin hanya untuk publikasi kepada umum; penulis dapat membatalkan pengajuan; catatan admin hanya untuk penulis**; **Draf 2.3: penarikan materi dari publik atas persetujuan admin (KF-20); seluruh bagian materi — judul, ringkasan, sampul, kategori, isi — dapat diubah kapan saja dan disimpan per versi; konfirmasi pengajuan menampilkan perbandingan dengan versi publik terakhir; versi publik dapat dibaca dari halaman materi**; **Draf 2.4: program kegiatan rutin beserta PIC dan dokumentasi foto/video, dikelola admin (KF-21); menu publik Struktur diganti Program**; **Draf 2.5: periode kepengurusan dibuat otomatis per tahun, periode lama hanya-baca, riwayat perubahan struktur (log + perbandingan antarperiode) untuk anggota; pengunjung hanya melihat periode saat ini**; **Draf 2.6: admin hanya mengelola dan tidak menulis materi (KP-08.6); admin tetap membaca materi dan berkomentar di forum**; **Draf 2.7: seluruh teks antarmuka dan pesan galat mengikuti Panduan Bahasa UI di CLAUDE.md — santai untuk anggota & pengunjung, formal untuk admin, tanpa istilah teknis**; **Draf 2.8: artikel & program terbaru di beranda, tombol bagikan, kontak organisasi, ukuran tulisan artikel, notifikasi komentar, profil sendiri, statistik admin (KF-22–KF-25)**; **Draf 2.9: fitur poster beranda dihapus**; **Draf 2.10: halaman Tentang kami (cerita, visi, misi) dan cerita beranda diringkas (KF-26)**) |

> **Untuk Claude Code:** dokumen ini adalah sumber kebenaran untuk _apa_ yang dibangun. Aturan _cara_ membangunnya ada di `CLAUDE.md`, `.claude/skills/backend-laravel/SKILL.md`, dan `.claude/skills/frontend-nuxt/SKILL.md`. Jangan menambah fitur di luar dokumen ini tanpa diminta.

---

## 1. Ringkasan

Aplikasi web untuk organisasi Al-Futuuhat yang:

1. menggantikan keanggotaan lisan (bergabung = dimasukkan ke grup WhatsApp) dengan **pendaftaran daring yang diverifikasi admin**;
2. menampilkan **struktur kepengurusan per periode**: pengunjung melihat periode saat ini; anggota dapat membuka periode lama beserta **riwayat perubahannya**. Periode dibuat otomatis setiap tahun;
3. menjadi **ruang materi internal**: materi yang ditulis anggota dengan editor Quill **langsung dapat dibaca dan didiskusikan seluruh anggota** sejak masih draf, tanpa persetujuan admin, dengan **forum diskusi _real-time_**;
4. menyediakan **publikasi untuk umum**: materi yang ingin dibaca pengunjung/tamu harus **diajukan penulis → diverifikasi admin → terbit untuk umum**, lengkap dengan **riwayat versi**. Materi yang sudah terbit baru dapat diajukan ulang setelah **ditarik dari publik** atas persetujuan admin.
5. menampilkan **program kegiatan rutin** organisasi: penjelasan rinci, PIC (penanggung jawab), dan **dokumentasi foto/video** per program. Program hanya dikelola admin; pengunjung dan anggota hanya melihat.

## 2. Latar belakang masalah

- Keanggotaan hanya lisan; tidak ada data anggota yang tersimpan rapi.
- Informasi dan pengetahuan tersebar di grup WhatsApp, mudah tenggelam, dan sulit dicari kembali.
- Materi tulisan anggota tidak memiliki alur persetujuan maupun riwayat perubahan sebelum disebarkan ke luar organisasi.
- Tim Media kesulitan memperoleh ide konten yang berasal dari pemikiran anggota.

## 3. Tujuan dan ukuran keberhasilan

| Tujuan                                                                              | Ukuran keberhasilan                                |
| ----------------------------------------------------------------------------------- | -------------------------------------------------- |
| Pendaftaran anggota tercatat dan terverifikasi                                      | Seluruh skenario uji UC-01, UC-02, UC-04 lulus     |
| Struktur kepengurusan dapat dilihat per periode                                     | Skenario uji UC-03 dan UC-06 lulus                 |
| Materi dapat ditulis, dicari, dan dibaca seluruh anggota tanpa menunggu persetujuan | Skenario uji UC-07 dan UC-12 lulus                 |
| Diskusi materi tampil tanpa memuat ulang halaman                                    | Skenario uji UC-11 lulus pada dua peramban berbeda |
| Publikasi kepada umum melalui verifikasi admin, dengan riwayat versi                | Skenario uji UC-08 s.d. UC-10, UC-16, UC-17 lulus  |
| Materi publik dapat dibaca dan dicari pengunjung tanpa login                        | Skenario uji UC-13 lulus                           |

## 4. Ruang lingkup

### Termasuk

Semua kebutuhan fungsional KF-01 s.d. KF-26 (bagian 7) dan kebutuhan nonfungsional KNF-01 s.d. KNF-09 (bagian 9). KNF-09 serta beberapa pendukung (unggah gambar editor, notifikasi, dasbor, atur ulang kata sandi oleh admin) belum tertulis di Bab III.

### Tidak termasuk (versi ini)

- Lupa/reset kata sandi lewat email (reset dilakukan admin lewat Data Anggota).
- Notifikasi email, WhatsApp, atau _push_; notifikasi hanya di dalam aplikasi.
- Aplikasi _mobile_ native.
- Forum diskusi untuk pengunjung/tamu. Pengunjung hanya membaca materi publik (lihat Keputusan Terbuka).
- Absensi, iuran/keuangan, sertifikat, serta agenda/kalender kegiatan per tanggal. _Draf 2.4:_ **program** kegiatan rutin (penjelasan, PIC, dokumentasi) **termasuk** ruang lingkup (KF-21), tetapi tanpa jadwal terstruktur, pendaftaran peserta, atau absensi.
- Moderasi komentar oleh admin (lihat Keputusan Terbuka).
- Desain visual akhir (tema akan ditentukan kemudian; sementara gunakan Tailwind yang rapi dan netral).

## 5. Aktor dan hak akses

| Aktor                               | Deskripsi                                                            | Hak akses                                                                                                                                                                                                                                                       |
| ----------------------------------- | -------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Pengunjung (tamu/calon anggota)** | Belum login atau belum punya akun aktif                              | Melihat beranda (cerita Al-Futuuhat), profil, dan struktur organisasi **periode saat ini**; **mencari dan membaca materi yang sudah terbit untuk umum**; **melihat program beserta dokumentasinya**; mendaftar                                                                                                          |
| **Anggota**                         | Akun berstatus `aktif`, peran `anggota` (termasuk anggota Tim Media) | Login/logout; melihat struktur **semua periode beserta riwayat perubahannya**; menulis, mengubah, dan menghapus materi miliknya; **mencari, membaca, dan mendiskusikan seluruh materi anggota (termasuk draf)**; mengajukan publikasi, membatalkan pengajuan, merevisi, dan mengajukan penarikan materi miliknya dari publik; melihat notifikasi; melihat program beserta dokumentasinya |
| **Admin (pengurus/ketua)**          | Akun berstatus `aktif`, peran `admin`. Admin juga anggota            | _Draf 2.6:_ hak anggota **kecuali menulis dan mengelola materi sendiri** (KP-08.6) — tetap membaca arsip materi, riwayat versi, dan forum, serta berkomentar di forum; + memverifikasi pendaftaran; mengelola data anggota; mengelola divisi, jabatan, dan susunan pengurus periode saat ini (periode dibuat otomatis); **memverifikasi pengajuan publikasi dan penarikan materi**; **mengatur konten beranda (cerita Al-Futuuhat & kontak organisasi)**; **mengelola program, PIC, dan dokumentasinya**             |

## 6. Daftar use case

| ID    | Use case                                                                 | Aktor               | KF           |
| ----- | ------------------------------------------------------------------------ | ------------------- | ------------ |
| UC-01 | Mendaftar sebagai Anggota                                                | Pengunjung          | KF-02, KF-03 |
| UC-02 | Login                                                                    | Anggota, Admin      | KF-03, KF-04 |
| UC-03 | Melihat Profil dan Struktur Organisasi                                   | Pengunjung, Anggota | KF-01        |
| UC-04 | Memverifikasi Pendaftaran Anggota                                        | Admin               | KF-05        |
| UC-05 | Mengelola Data Anggota                                                   | Admin               | KF-06        |
| UC-06 | Mengelola Struktur Organisasi                                            | Admin               | KF-07        |
| UC-07 | Mengelola Materi                                                         | Anggota             | KF-08, KF-09 |
| UC-08 | Mengajukan Publikasi Materi kepada Umum (termasuk membatalkan pengajuan) | Anggota (penulis)   | KF-10        |
| UC-09 | Memverifikasi Publikasi Materi                                           | Admin               | KF-11        |
| UC-10 | Merevisi Materi                                                          | Anggota (penulis)   | KF-12, KF-13 |
| UC-11 | Berdiskusi pada Forum Materi                                             | Anggota             | KF-14, KF-15 |
| UC-12 | Mencari dan Membaca Materi (internal)                                    | Anggota             | KF-13, KF-16 |
| UC-13 | Mencari dan Membaca Materi Publik                                        | Pengunjung          | KF-17        |
| UC-14 | Mengatur Konten Beranda (cerita & kontak)                                | Admin               | KF-18        |
| UC-15 | Mengelola Kategori Materi                                                | Admin               | KF-19        |
| UC-16 | Mengajukan Penarikan Materi dari Publik (termasuk membatalkannya)        | Anggota (penulis)   | KF-20        |
| UC-17 | Memverifikasi Penarikan Materi                                           | Admin               | KF-20        |
| UC-18 | Mengelola Program dan Dokumentasinya                                     | Admin               | KF-21        |
| UC-19 | Melihat Program dan Dokumentasinya                                       | Pengunjung, Anggota | KF-21        |

## 7. Kebutuhan fungsional dan kriteria penerimaan

Setiap KF memiliki kriteria penerimaan (KP). KP inilah yang diuji dengan _Black Box Testing_.

### KF-01 — Profil dan struktur kepengurusan per periode

_Sistem menampilkan profil organisasi dan struktur kepengurusan berdasarkan periode._

- KP-01.1: Beranda dapat dibuka tanpa login.
- KP-01.2: _Draf 2.5:_ beranda pengunjung hanya menampilkan struktur **periode saat ini** (`is_aktif = true`), tanpa pemilih periode, lewat `GET /publik/struktur`. Bila belum ada periode, beranda tetap tampil dengan pesan kosong.
- KP-01.3: _Draf 2.5:_ **anggota yang login** (halaman `/struktur`) dapat memilih periode lain dan struktur berubah sesuai periode tersebut. Daftar periode dan struktur per periode tidak dapat diakses tanpa login (401).
- KP-01.4: Pengurus dikelompokkan: pengurus inti (tanpa divisi, mis. Ketua) di atas, lalu per divisi. Setiap divisi dapat dibuka-tutup dan menampilkan **semua** pengurusnya, diurutkan berdasarkan `jabatan.urutan`.
- KP-01.5: Data publik hanya berisi nama, jabatan, dan divisi (KNF-02). Nomor HP, alamat, email, alasan bergabung, dan foto **tidak** dikirim oleh API publik.

### KF-02 — Formulir pendaftaran

_Pengunjung mendaftar melalui formulir berisi nama, email, kata sandi, nomor HP, alamat, alasan bergabung, dan foto._

- KP-02.1: Isian wajib: nama, email, kata sandi, konfirmasi kata sandi, nomor HP, alamat, alasan bergabung. Foto opsional (lihat Keputusan Terbuka).
- KP-02.2: Email harus valid dan belum terdaftar → jika sudah ada, tampil pesan "Email ini sudah terdaftar. Coba masuk saja, atau pakai email lain."
- KP-02.3: Kata sandi minimal 8 karakter dan harus sama dengan konfirmasinya.
- KP-02.4: Foto hanya JPG/PNG, maksimal 2 MB (KNF-03).
- KP-02.5: Pesan kesalahan tampil di bawah isian yang salah; data yang sudah diisi tidak hilang.

### KF-03 — Status menunggu verifikasi

_Sistem menyimpan pendaftar berstatus menunggu dan menolak login akun yang belum disetujui._

- KP-03.1: Setelah daftar, tersimpan 1 baris `users` (`role = anggota`, `status = menunggu`) dan 1 baris `anggota`, dalam satu transaksi.
- KP-03.2: Pengguna diarahkan ke halaman "Pendaftaran terkirim" yang menjelaskan bahwa akun menunggu verifikasi.
- KP-03.3: Login akun `menunggu` ditolak dengan pesan "Kamu belum bisa masuk dulu. Tunggu kabar dari admin ya, biasanya tidak lama."

### KF-04 — Login, logout, menu sesuai peran

- KP-04.1: Email/kata sandi salah → "Email atau kata sandinya belum cocok. Coba cek lagi ya." (tidak membedakan mana yang salah).
- KP-04.2: Akun `ditolak` → pesan penolakan **beserta alasan dari admin**.
- KP-04.3: Akun `nonaktif` → "Kamu belum bisa masuk dulu. Hubungi pengurus untuk mengaktifkannya lagi ya."
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

- KP-07.1: _Draf 2.5:_ periode **tidak dibuat manual**. Satu periode per **tahun kalender** (berganti 1 Januari). Periode tahun berjalan dibuat **otomatis** saat perubahan susunan pengurus pertama di tahun itu: namanya tahun tersebut (mis. "2027"), periode sebelumnya dinonaktifkan dan menjadi arsip, dan susunan pengurusnya disalin sebagai titik awal — semuanya dalam satu transaksi bersama perubahan yang memicunya. Hanya **satu** periode aktif. Bila tidak ada perubahan sepanjang tahun, tidak ada periode kosong yang dibuat.
- KP-07.2: CRUD divisi (data awal 6 divisi) dan jabatan (dengan `urutan`).
- KP-07.3: Menambah pengurus: pilih anggota aktif, divisi (boleh kosong untuk pengurus inti), dan jabatan. _Draf 2.5:_ tanpa memilih periode — selalu dicatat pada periode saat ini.
- KP-07.4: Satu anggota tidak boleh tercatat dua kali pada periode yang sama.
- KP-07.5: Divisi/jabatan yang masih dipakai di `kepengurusan` (periode mana pun) tidak dapat dihapus (pesan jelas, bukan error 500). _Draf 2.5:_ periode tidak dapat dihapus.
- KP-07.6: _Draf 2.5:_ periode lama **hanya-baca**: mengubah atau menghapus baris pengurus periode yang tidak aktif → 422 "Susunan pengurus periode lama tidak dapat diubah.". Satu anggota yang sudah tercatat pada periode saat ini → 422 "Anggota ini sudah memiliki jabatan pada periode ini.".
- KP-07.7: _Draf 2.5:_ setiap tambah, ubah, dan hapus pengurus serta pembuatan periode otomatis dicatat di `riwayat_struktur`: waktu, admin, aksi, kalimat keterangan (mis. "Menambahkan Ahmad sebagai Koordinator Divisi Media.", "Mengubah jabatan Siti dari Koordinator Divisi Media menjadi Ketua (pengurus inti)."), serta data sebelum/sesudah (nama, jabatan, divisi **saat kejadian**). Ubah tanpa perbedaan tidak dicatat.
- KP-07.8: _Draf 2.5:_ anggota dan admin melihat riwayat setiap periode: (1) **perbandingan** dengan periode sebelumnya — siapa masuk, keluar, dan pindah jabatan/divisi (dihitung dari data, dicocokkan per anggota); periode pertama tidak punya pembanding; (2) **log perubahan** terbaru di atas beserta nama admin. Tamu 401.

### KF-08 — Menulis materi

_Materi yang disimpan langsung menjadi milik bersama seluruh anggota untuk dibaca dan didiskusikan; tidak perlu persetujuan admin._

- KP-08.1: Anggota menulis judul, ringkasan (opsional), dan isi dengan editor Quill (teks berformat dan gambar).
- KP-08.2: Gambar di editor diunggah ke server (bukan base64) — JPG/PNG maks 2 MB.
- KP-08.3: Simpan → 1 baris `materi` (`status = draf`) + 1 baris `revisi_materi` (`nomor_versi = 1`, `status = draf`) dalam satu transaksi.
- KP-08.4: `konten` disimpan sebagai Delta Quill (JSONB) dan `konten_html` disimpan **setelah disanitasi**.
- KP-08.5: Begitu disimpan, materi **langsung tampil** di arsip anggota (KF-16) dan forum diskusinya **langsung terbuka** (KF-14), tanpa verifikasi admin. Materi berstatus `draf` **tidak** dapat diakses pengunjung.
- KP-08.6: _Draf 2.6:_ **admin hanya mengelola dan tidak dapat menulis materi.** Membuat materi, membuka "Materi saya", mengunggah sampul, serta mengubah, menghapus, mengajukan/membatalkan publikasi, membuat revisi, dan mengajukan/membatalkan penarikan ditolak **403** dengan pesan "Akun admin digunakan untuk mengelola sehingga tidak dapat menulis materi." (permintaan membuat materi ditolak sebelum validasi). Admin tetap dapat membaca arsip, materi, riwayat versi, dan forum, **berkomentar di forum**, serta mengunggah gambar editor Quill untuk konten beranda dan penjelasan program. Materi milik anggota yang kemudian diangkat menjadi admin tetap tampil tetapi **terkunci** selama ia admin, dan dapat dikelola lagi bila perannya dikembalikan ke anggota. Di antarmuka, admin tidak melihat tombol Tulis, menu Materi saya, maupun dasbor anggota; membuka halaman itu dialihkan ke `/admin`.

### KF-09 — Mengubah/menghapus materi

- KP-09.1: Penulis dapat mengubah **seluruh bagian** materi (judul, ringkasan, sampul, kategori, dan isi) selama revisi terakhirnya berstatus `draf`, termasuk ketika materi sedang terbit. Perubahan langsung terlihat oleh anggota lain (menimpa revisi `draf` tersebut, tidak membuat versi baru), tetapi **tidak** mengubah versi publik (KP-12.4).
- KP-09.2: Penulis dapat menghapus materi hanya jika materi **belum pernah terbit untuk umum** (`diterbitkan_pada` kosong) dan revisi terakhir tidak sedang `diajukan` (batalkan pengajuan terlebih dahulu). Revisi dan komentar materi tersebut ikut terhapus. Materi yang pernah terbit lalu ditarik dari publik (KF-20) tetap tidak dapat dihapus.
- KP-09.3: Bukan penulis → 403.

### KF-10 — Mengajukan publikasi kepada umum

- KP-10.1: Hanya penulis, hanya jika revisi terakhir berstatus `draf`, dan hanya jika materi **tidak sedang terbit**. Materi yang sedang terbit harus ditarik dari publik lebih dulu (KF-20); pengajuan saat materi terbit → 422 "Materi ini masih tayang. Turunkan dulu dari halaman umum, baru ajukan versi barunya ya."
- KP-10.2: Revisi terakhir → `diajukan`; `materi.status → diajukan`.
- KP-10.3: Semua admin aktif menerima notifikasi dalam aplikasi.
- KP-10.4: Revisi yang sedang `diajukan` tidak bisa diubah. Untuk mengubahnya, penulis membatalkan pengajuan terlebih dahulu (KP-10.6).
- KP-10.5: Selama diajukan, materi **tetap** tampil untuk anggota dan forum diskusinya **tetap** berjalan.
- KP-10.6: Penulis dapat **membatalkan pengajuan** selama admin belum memutuskan: revisi `diajukan → draf`; `materi.status → draf`. Revisi itu hilang dari antrean admin. Pembatalan oleh bukan penulis → 403; pembatalan revisi yang tidak `diajukan` (mis. sudah diputuskan admin) → 422.
- KP-10.7: Pengajuan baru dikirim setelah penulis mengonfirmasi. Bila materi **pernah terbit**, dialog konfirmasi menampilkan **perbandingan** versi publik terakhir (mis. v1) dengan revisi yang akan diajukan (mis. v2): dua kolom berdampingan (di ponsel menjadi dua tab) untuk judul, ringkasan, sampul, kategori, dan isi; bagian yang berbeda diberi label "Berubah", dan kata yang ditambah/dihapus pada judul, ringkasan, dan isi **disorot**. Bila materi belum pernah terbit, cukup konfirmasi biasa. Membatalkan dialog tidak mengubah apa pun.

### KF-11 — Verifikasi publikasi

- KP-11.1: Admin melihat antrean revisi berstatus `diajukan` (terlama di atas).
- KP-11.2: Admin melihat isi revisi; jika materi pernah terbit, admin dapat membandingkannya dengan versi publik terakhir memakai tampilan perbandingan yang sama dengan KP-10.7.
- KP-11.3: Setujui → revisi `disetujui`; `materi.status = terbit`; data publik materi (judul, slug, ringkasan, sampul, kategori) disalin dari revisi ini; `diterbitkan_pada` diisi jika masih kosong. Materi tampil di halaman publik (KF-17) dengan revisi ini.
- KP-11.4: Tolak → `catatan` wajib; revisi `ditolak`; `materi.status = ditolak` (tetap dapat dibaca dan didiskusikan anggota, tetapi tidak tampil untuk umum).
- KP-11.5: Setiap keputusan dicatat di `verifikasi_materi` dan penulis menerima notifikasi berisi keputusan dan catatannya.
- KP-11.6: Hanya revisi `diajukan` yang dapat diverifikasi (selain itu 422, termasuk revisi yang pengajuannya sudah dibatalkan penulis).

### KF-12 — Revisi oleh penulis

- KP-12.1: Hanya penulis yang dapat membuat revisi (selain itu 403).
- KP-12.2: Revisi baru hanya boleh dibuat jika revisi terakhir berstatus `disetujui` atau `ditolak` (tidak ada draf/pengajuan yang menggantung).
- KP-12.3: Revisi baru = baris baru di `revisi_materi`, `nomor_versi` = terakhir + 1, `status = draf`; judul, ringkasan, sampul, kategori, dan isi awalnya disalin dari revisi terakhir.
- KP-12.4: Revisi dapat mengubah seluruh bagian materi, termasuk judul, ringkasan, sampul, dan kategori. Anggota langsung melihat revisi baru tersebut, sedangkan **pengunjung tetap melihat versi publik** (judul, ringkasan, sampul, kategori, dan isi dari versi yang disetujui) sampai materi ditarik dari publik (KF-20).
- KP-12.5: Revisi yang `disetujui` tidak pernah diubah atau dihapus (KNF-06).
- KP-12.6: Selama materi terbit, penulis boleh membuat dan mengubah revisi `draf` kapan saja, tetapi baru dapat mengajukannya setelah penarikan disetujui (KP-10.1, KF-20).

### KF-13 — Riwayat revisi dan verifikasi

- KP-13.1: Halaman materi (anggota) menampilkan daftar versi: nomor versi, status, tanggal, catatan revisi.
- KP-13.2: Penulis dan admin melihat riwayat keputusan admin pada setiap versi (keputusan, catatan, nama admin, waktu).
- KP-13.3: Anggota lain melihat daftar semua versi beserta statusnya, tetapi **tidak** melihat catatan keputusan admin (API tidak mengirimkannya). Pengunjung **tidak** melihat riwayat versi.
- KP-13.4: Isi setiap versi (judul, ringkasan, sampul, kategori, dan isi versi itu) dapat dibaca kembali oleh anggota aktif. Daftar riwayat hanya memuat ringkasan (nomor, status, tanggal, catatan revisi); isi satu versi diambil menyusul lewat `GET /materi/{materi}/riwayat/{revisi}` saat versinya dibuka. Versi yang sudah diajukan/disetujui tidak pernah ditimpa, sehingga isi lama tetap utuh.

### KF-14 — Forum diskusi

- KP-14.1: Forum tersedia pada **setiap** materi, apa pun statusnya (`draf`, `diajukan`, `ditolak`, `terbit`), dan hanya untuk anggota aktif. Pengunjung tidak dapat melihat atau mengirim komentar.
- KP-14.2: Anggota mengirim komentar berupa teks, gambar, atau keduanya (minimal salah satu). Gambar JPG/PNG maks 2 MB.
- KP-14.3: Anggota dapat membalas komentar (satu tingkat balasan).
- KP-14.4: Anggota hanya dapat menghapus komentarnya sendiri (403 untuk komentar orang lain). Menghapus komentar induk ikut menghapus balasannya.
- KP-14.5: Balasan **tidak** ikut dikirim pada daftar komentar; daftar hanya memuat komentar induk beserta `jumlah_balasan`. Balasan satu induk diambil menyusul lewat `GET /komentar/{komentar}/balasan` ketika pembaca membukanya, agar peramban tidak mengunduh seluruh balasan materi sekaligus.

### KF-15 — Real-time

- KP-15.1: Komentar/balasan baru tampil di peramban anggota lain yang membuka materi yang sama tanpa memuat ulang.
- KP-15.2: Komentar yang dihapus hilang dari peramban lain tanpa memuat ulang.
- KP-15.3: Kanal bersifat _private_ (`materi.{id}`); hanya pengguna login berstatus `aktif` yang dapat berlangganan.

### KF-16 — Pencarian dan membaca materi (anggota)

- KP-16.1: Anggota mencari **seluruh** materi (status apa pun) dengan **satu** kata kunci yang mencakup judul dan isi **revisi terakhir** (_full-text search_ PostgreSQL), dan nama penulis, lalu dapat difilter menurut status publikasi. Pencocokan memakai **awalan kata** (`kata:*`), sehingga hasil muncul sejak sebagian kata diketik. Di halaman `/materi` dan `/materi-saya` pencarian berjalan **real-time**: daftar disaring saat pengguna mengetik (dijeda ±350 ms), tanpa perlu menekan Enter.
- KP-16.2: Hasil berpaginasi dan menampilkan judul, ringkasan, penulis, tanggal diperbarui, label status publikasi, dan jumlah komentar. Judul, ringkasan, sampul, dan kategori di area anggota selalu diambil dari **revisi terakhir**.
- KP-16.3: Tidak ada hasil → pesan "Materinya tidak ketemu."
- KP-16.4: Halaman baca menampilkan isi **revisi terakhir**, penulis, tanggal, nomor versi, label status publikasi (jika materi sedang terbit dan versi publik berbeda dari revisi terakhir, tampilkan keterangan "Versi publik: vN" yang dapat diklik untuk membaca versi publik itu secara lengkap di kolom baca — dengan baris "Anda membaca versi publik vN" dan tombol "Kembali ke draf vM"; jika ada pengajuan penarikan yang menunggu, tampilkan keterangan "Menunggu penarikan"), riwayat versi, dan forum.

### KF-17 — Membaca materi publik (pengunjung)

- KP-17.1: Halaman publikasi dapat dibuka tanpa login dan hanya menampilkan materi berstatus `terbit`; pengunjung dapat mencari berdasarkan kata kunci (judul + isi **versi publik**). Hasil berpaginasi dan menampilkan judul, ringkasan, nama penulis, tanggal terbit.
- KP-17.2: Halaman baca publik hanya menampilkan **versi publik** (revisi `disetujui` dengan `nomor_versi` terbesar, beserta judul, ringkasan, sampul, dan kategori versi itu), meskipun ada revisi lebih baru yang masih `draf`/`diajukan`/`ditolak`.
- KP-17.3: Materi yang belum terbit atau sudah ditarik dari publik (KF-20), jika diminta lewat URL/API publik, menghasilkan 404.
- KP-17.4: Halaman publik tidak menampilkan forum, riwayat versi, catatan admin, maupun data pribadi penulis (hanya nama).
- KP-17.5: Kata yang hanya ada pada revisi yang belum disetujui **tidak** ditemukan lewat pencarian publik.

### KF-18 — Konten beranda dan sampul artikel (pengunjung)

- KP-18.1: Beranda pengunjung memuat **cerita Al-Futuuhat** yang diatur admin lewat halaman `/admin/beranda`. Cerita terdiri atas judul (maks 150 karakter) dan isi berformat (editor Quill) yang **disanitasi** seperti isi materi (KP-08.4). _Draf 2.9: poster dihapus._
- KP-18.2: Selama admin belum mengatur cerita, beranda tetap tampil tanpa galat: bagian cerita memakai teks bawaan. Halaman beranda **tidak pernah** kosong bagi pengunjung.
- KP-18.3: Endpoint baca konten beranda bersifat publik (tanpa login) dan hanya mengirim judul dan isi cerita (serta kontak organisasi, KF-22). Mengubah konten beranda hanya boleh dilakukan admin (anggota biasa 403, tamu 401).
- KP-18.4: Setiap materi dapat memiliki **satu gambar sampul** JPG/PNG maks 2 MB yang diunggah penulis di editor materi. Sampul tampil sebagai gambar kartu artikel di `/publikasi` dan sebagai gambar kecil pada baris daftar materi di area anggota. Sampul disimpan per versi seperti judul dan ringkasan, sehingga dapat diubah kapan saja lewat revisi `draf` tanpa mengubah sampul versi publik (KP-12.4). Berkas sampul yang diganti atau dilepas dihapus dari server **hanya jika** tidak lagi dipakai oleh versi lain maupun data publik materi.
- KP-18.5: Daftar artikel di `/publikasi` ditampilkan sebagai **grid kartu** yang memuat sampul, nama penulis, judul, ringkasan, tanggal terbit, dan perkiraan waktu baca. Pencarian dan paginasi tetap seperti KP-17.1.

### KF-19 — Kategori materi (anggota & pengunjung)

- KP-19.1: Admin mengelola daftar kategori materi (tambah, ubah, hapus) di halaman `/admin/kategori`. Nama kategori unik (maks 255 karakter) dan _slug_-nya dibentuk otomatis dari nama. Kategori yang **masih dipakai** materi atau versi materi mana pun tidak dapat dihapus: API menolak dengan 422 berpesan "Kategori ini masih dipakai oleh materi sehingga belum dapat dihapus.". Anggota biasa 403, tamu 401.
- KP-19.2: Setiap materi wajib memiliki **minimal satu** kategori; lebih dari satu diperbolehkan. Materi tanpa kategori ditolak 422 saat disimpan. Daftar kategori dapat dibaca anggota maupun pengunjung lewat `GET /kategori`, sehingga penyaring dapat dipakai tanpa login.
- KP-19.3: Kategori disimpan per versi (`kategori_revisi`) dan dapat diubah kapan saja lewat revisi `draf`. Area anggota (chip dan penyaring `GET /materi`) memakai kategori revisi terakhir; area publik memakai kategori versi publik (`kategori_materi`), yang baru berganti saat revisi berikutnya disetujui (KP-11.3).
- KP-19.4: Anggota dan pengunjung dapat menyaring daftar materi menurut kategori (`?kategori=slug` pada `GET /materi` dan `GET /publik/materi`). Nama kategori tampil sebagai _chip_ pada baris daftar materi, kartu artikel `/publikasi`, dan halaman baca (halaman anggota maupun publik). Penyaring kategori digabung dengan pencarian kata kunci yang sudah ada (KP-16.1, KP-17.1), bukan menggantikannya.

### KF-20 — Penarikan materi dari publik

_Versi publik materi yang sudah terbit tidak dapat langsung diganti. Penulis mengajukan penarikan; setelah disetujui admin, materi hilang dari halaman publik dan dapat diajukan kembali._

- KP-20.1: Hanya penulis (selain itu 403), dan hanya jika `materi.status = terbit` serta tidak ada pengajuan penarikan yang masih menunggu (selain itu 422). Alasan wajib diisi, maksimal 1.000 karakter (kosong → 422 "Tulis dulu alasannya ya."). Pengajuan disimpan di `penarikan_materi` berstatus `diajukan`, dan semua admin aktif menerima notifikasi.
- KP-20.2: Selama penarikan menunggu, materi **tetap** terbit dan tampil untuk umum; penulis tetap dapat membuat dan mengubah revisi `draf` (KP-09.1), dan forum tetap berjalan.
- KP-20.3: Penulis dapat membatalkan pengajuan penarikan selama admin belum memutuskan → `dibatalkan`. Bukan penulis → 403; penarikan yang tidak `diajukan` → 422.
- KP-20.4: Admin melihat antrean penarikan berstatus `diajukan` (terlama di atas) beserta judul materi, nama penulis, alasan, dan tanggal pengajuan.
- KP-20.5: Setujui → penarikan `disetujui` (admin dan waktu keputusan dicatat); `materi.status → draf`; data materi (judul, slug, ringkasan, sampul, kategori) disamakan dengan revisi terakhir; materi tidak lagi tampil di halaman publik (404, KP-17.3). Versi yang dulu disetujui tetap utuh di riwayat (KNF-06) dan `diterbitkan_pada` tetap terisi. Penulis menerima notifikasi. Setelah itu penulis dapat mengajukan publikasi lagi (KF-10); bila revisi terakhir bukan `draf`, penulis membuat revisi baru lebih dulu (KF-12).
- KP-20.6: Tolak → `catatan` wajib; penarikan `ditolak`; materi tetap `terbit` dengan versi publik lama. Penulis menerima notifikasi berisi catatan.
- KP-20.7: Hanya penarikan berstatus `diajukan` yang dapat diputuskan (selain itu 422, termasuk yang sudah dibatalkan penulis). Alasan penarikan dan catatan admin hanya dikirim kepada penulis dan admin; anggota lain hanya melihat keterangan "Menunggu penarikan".

### KF-21 — Program kegiatan dan dokumentasinya

_Draf 2.4. Program adalah kegiatan rutin organisasi (mis. kajian pekanan). Setiap program memiliki penjelasan rinci, satu atau lebih PIC, dan kumpulan dokumentasi foto/video. Hanya admin yang mengelola; pengunjung dan anggota hanya melihat._

- KP-21.1: Admin menambah, mengubah, dan menghapus program di `/admin/program`. Isian: **nama** (wajib, unik, maks 150 karakter; nama sama → 422 "Nama program sudah ada."; _slug_ dibentuk otomatis dan hanya berubah bila nama berubah), **ringkasan** (opsional, maks 300), **jadwal pelaksanaan** (opsional, teks bebas maks 150, mis. "Setiap Jumat malam, ba'da Isya"), **gambar sampul** (opsional, JPG/PNG maks 2 MB; sampul baru menggantikan yang lama dan sampul dapat dilepas), **penjelasan** (wajib, editor Quill, **disanitasi** seperti isi materi; editor kosong → 422 "Penjelasan program wajib diisi."), dan **status** `aktif` (berjalan) atau `selesai`. Anggota biasa 403, tamu 401.
- KP-21.2: Setiap program wajib memiliki **minimal satu** PIC dan boleh lebih dari satu, dipilih dari anggota yang akunnya berstatus `aktif` (termasuk ketua/admin). PIC kosong → 422 "Pilih minimal satu PIC."; anggota tidak aktif atau tidak ada → 422 "PIC harus anggota aktif.".
- KP-21.3: Admin menambah dokumentasi pada program, **satu butir per permintaan**, berupa **foto** JPG/PNG maks **2 MB**, **video** MP4 maks **50 MB**, atau **tautan YouTube** (youtube.com/watch, youtu.be, youtube.com/shorts; tautan lain → 422 "Tautan YouTube tidak dikenali. Salin ulang tautannya dari YouTube."). Batas ukuran berlaku **per berkas**, bukan keseluruhan; antarmuka boleh memilih beberapa berkas sekaligus lalu mengirimnya satu per satu. Keterangan opsional, maks 255 karakter. Pesan galat: "Ukuran foto maksimal 2 MB.", "Ukuran video maksimal 50 MB.", "Foto harus berformat JPG atau PNG.", "Video harus berformat MP4.".
- KP-21.4: Admin dapat menghapus satu dokumentasi; berkasnya ikut dihapus dari server. Menghapus program menghapus PIC, seluruh dokumentasi, sampul, dan berkas-berkasnya.
- KP-21.5: Halaman `/program` dapat dibuka tanpa login dan menampilkan semua program sebagai grid kartu (sampul, status, nama, ringkasan, jadwal, nama PIC, jumlah foto & video); program `aktif` di atas, lalu menurut nama. Halaman `/program/[slug]` menampilkan penjelasan rinci (tab **Tentang program**) dan dokumentasi (tab **Dokumentasi**). Slug yang tidak ada → 404. API publik hanya mengirim **nama** PIC (KNF-02).
- KP-21.6: Tab Dokumentasi menampilkan foto dan video program itu saja, **terbaru di atas**, berpaginasi 12 per halaman, dengan penyaring Semua/Foto/Video (Video mencakup MP4 dan YouTube). Foto dibuka dalam penampil besar, video MP4 diputar dengan pemutar bawaan peramban, dan YouTube disematkan lewat `youtube-nocookie.com`.
- KP-21.7: Menu header publik berisi Beranda · Artikel · **Program** · Cara bergabung; menu **Struktur** dihapus dari header publik karena bagan struktur sudah tampil di beranda (KF-01 tetap berlaku). Halaman `/struktur` di area anggota dan navbar anggota tidak berubah.

### KF-22 — Kenyamanan pengunjung (Draf 2.8)

- KP-22.1: Beranda menampilkan 3 artikel terbaru dan 3 program (aktif lebih dulu) dengan tautan "Lihat semua". Bagian yang kosong tidak ditampilkan.
- KP-22.2: Halaman baca artikel publik dan halaman program punya tombol **Bagikan** (lembar bagikan perangkat; bila tidak tersedia: Salin tautan dan WhatsApp).
- KP-22.3: Admin mengatur kontak organisasi (alamat, WhatsApp, email, Instagram) di `/admin/beranda`; semuanya opsional. Nomor WhatsApp disimpan sebagai angka berawalan 62, akun Instagram tanpa "@". Kontak tampil di kaki halaman publik; kolom kosong disembunyikan.
- KP-22.4: Halaman baca materi (anggota) dan artikel (publik) punya tombol A− / A+ untuk ukuran tulisan isi (−4 s.d. +8 px dari bawaan); pilihan diingat di peramban.

### KF-23 — Notifikasi komentar (Draf 2.8)

- KP-23.1: Penulis materi menerima notifikasi saat materinya dikomentari; pemilik komentar menerima notifikasi saat komentarnya dibalas. Komentar sendiri tidak memicu notifikasi, dan satu orang hanya menerima satu notifikasi per komentar. Notifikasi memuat nama pengirim, judul materi, cuplikan komentar, dan tautan ke materi.

### KF-24 — Profil sendiri (Draf 2.8)

- KP-24.1: Pengguna yang masuk dapat mengubah nama, nomor HP, alamat, dan foto (JPG/PNG maks 2 MB) di `/profil`. Email dan peran tidak dapat diubah.
- KP-24.2: Mengganti kata sandi wajib memasukkan kata sandi lama (salah → 422 "Kata sandi lama belum cocok."); kata sandi baru minimal 8 karakter dan berbeda dari yang lama. Sesi di perangkat lain dicabut, sesi yang sedang dipakai tetap.

### KF-25 — Statistik admin (Draf 2.8)

- KP-25.1: Setiap kali halaman baca materi dibuka (anggota maupun publik), `materi.jumlah_dibaca` bertambah 1 tanpa mengubah `updated_at`.
- KP-25.2: Dasbor admin menampilkan 5 materi paling banyak dibaca dan 5 anggota paling aktif 30 hari terakhir (jumlah materi baru + komentar). Anggota biasa 403.

### KF-26 — Tentang kami (Draf 2.10)

- KP-26.1: Halaman publik `/tentang` (menu "Tentang kami") menampilkan cerita Al-Futuuhat lengkap, **visi**, dan **misi**. Visi teks biasa (maks 1.000 karakter); misi ditulis satu poin per baris (maks 3.000 karakter) dan tampil sebagai daftar bernomor. Keduanya diatur admin di `/admin/beranda`; bagian yang kosong tidak ditampilkan.
- KP-26.2: Di beranda, cerita yang panjang dipotong dengan efek pudar dan tombol "Baca selengkapnya" menuju `/tentang`; tombol hanya muncul bila cerita terpotong.

## 8. Aturan bisnis dan status

### 8.1 Status akun (`users.status`)

```
menunggu ──(admin setujui)──▶ aktif ◀──(admin aktifkan)──┐
    │                            │                       │
    └──(admin tolak + alasan)──▶ ditolak    aktif ──(admin nonaktifkan)──▶ nonaktif
```

Hanya `aktif` yang bisa login dan memakai API selain endpoint publik.

### 8.2 Dua tingkat akses materi

| Tingkat      | Siapa                                  | Versi yang dilihat                                                  | Butuh verifikasi admin?                |
| ------------ | -------------------------------------- | ------------------------------------------------------------------- | -------------------------------------- |
| **Internal** | Seluruh anggota aktif (termasuk admin) | **Revisi terakhir** (`nomor_versi` terbesar), apa pun statusnya     | Tidak — langsung sejak disimpan        |
| **Publik**   | Pengunjung/tamu (tanpa login)          | **Versi publik** = revisi `disetujui` dengan `nomor_versi` terbesar | Ya — diajukan penulis, disetujui admin |

`materi.status` menyatakan **status publikasi kepada umum**, bukan hak baca anggota:

- `draf` — belum diajukan, pengajuannya dibatalkan, atau sudah ditarik dari publik; hanya untuk anggota.
- `diajukan` — sedang menunggu verifikasi publikasi; hanya untuk anggota.
- `ditolak` — pengajuan publikasi ditolak; hanya untuk anggota.
- `terbit` — sudah terbit untuk umum (versi publik tersedia). Selama `terbit`, publikasi tidak dapat diajukan; penulis menarik materi lebih dulu (KF-20).

Judul, ringkasan, sampul, dan kategori disimpan **per versi** di `revisi_materi` (dan `kategori_revisi`). Kolom yang sama pada tabel `materi` (judul, slug, ringkasan, sampul, serta `kategori_materi`) adalah **data versi publik**: disamakan dengan revisi terakhir setiap kali draf disimpan selama materi tidak berstatus `terbit`, disalin dari revisi yang disetujui saat materi terbit (KP-11.3), dan tidak berubah selama materi berstatus `terbit`.

### 8.3 Perubahan status materi (`materi.status`) dan revisi (`revisi_materi.status`)

| Kejadian                    | revisi terakhir                                   | `materi.status`                                    | Halaman publik                     |
| --------------------------- | ------------------------------------------------- | -------------------------------------------------- | ---------------------------------- |
| Buat materi                 | `draf` (v1)                                       | `draf` — langsung terlihat anggota, forum terbuka  | —                                  |
| Ubah materi                 | tetap `draf` (judul, ringkasan, sampul, kategori, isi ditimpa) | tetap                                 | tidak berubah                      |
| Buat revisi baru            | baris baru `draf` (v+1), disalin dari revisi terakhir | `ditolak → draf`; `terbit` tetap `terbit`      | tidak berubah                      |
| Ajukan publikasi            | `draf → diajukan`                                 | `draf → diajukan` (422 bila `terbit`)              | —                                  |
| Penulis batalkan pengajuan  | `diajukan → draf`                                 | `→ draf`                                           | —                                  |
| Admin setujui publikasi     | `diajukan → disetujui`                            | `→ terbit`; data publik disalin dari revisi ini    | tampil dengan versi ini            |
| Admin tolak publikasi       | `diajukan → ditolak`                              | `→ ditolak`                                        | —                                  |
| Ajukan penarikan            | tetap                                             | tetap `terbit` (penarikan `diajukan`)              | tetap tampil                       |
| Penulis batalkan penarikan  | tetap                                             | tetap `terbit` (penarikan `dibatalkan`)            | tetap tampil                       |
| Admin setujui penarikan     | tetap                                             | `terbit → draf`; data materi disamakan dengan revisi terakhir | hilang (404)            |
| Admin tolak penarikan       | tetap                                             | tetap `terbit` (penarikan `ditolak`)               | tetap tampil (versi lama)          |

- Dalam satu materi hanya boleh ada **satu** revisi `draf` atau `diajukan` pada satu waktu, dan **satu** penarikan `diajukan` pada satu waktu.
- Revisi `disetujui` dan `ditolak` bersifat tetap (tidak diubah) sebagai riwayat, termasuk setelah materi ditarik dari publik.
- Publikasi hanya dapat diajukan selama materi **tidak** `terbit`; versi publik materi terbit diganti dengan alur tarik → ajukan → setujui.
- Forum diskusi melekat pada materi (bukan pada versi), sehingga diskusi tidak hilang saat revisi baru dibuat.

### 8.4 Notifikasi (dalam aplikasi, tabel `notifications` bawaan Laravel)

| Pemicu                          | Penerima          | Isi                                     |
| ------------------------------- | ----------------- | --------------------------------------- |
| Materi diajukan untuk publikasi | Semua admin aktif | judul materi, nama penulis, nomor versi |
| Revisi diverifikasi             | Penulis           | judul materi, keputusan, catatan        |
| Penarikan materi diajukan       | Semua admin aktif | judul materi, nama penulis, alasan      |
| Penarikan diputuskan            | Penulis           | judul materi, keputusan, catatan        |

## 9. Kebutuhan nonfungsional

| Kode   | Aspek                | Kebutuhan                                                                                                                                                                                                                                             |
| ------ | -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| KNF-01 | Keamanan             | Kata sandi di-_hash_ (bcrypt). API memakai token Laravel Sanctum. Setiap aksi diperiksa sesuai peran lewat _middleware_ dan _policy_.                                                                                                                 |
| KNF-02 | Privasi              | No HP, alamat, dan alasan bergabung hanya dapat dilihat admin. Halaman publik hanya menampilkan nama dan jabatan pengurus, serta nama penulis pada materi publik. Materi yang belum terbit, forum, dan riwayat versi tidak dapat diakses tanpa login. |
| KNF-03 | Validasi berkas      | Unggahan foto/gambar hanya JPG/PNG, maks 2 MB. Pengecualian _Draf 2.4_: video dokumentasi program MP4 maks 50 MB **per berkas** (KP-21.3).                                                                                                            |
| KNF-04 | Kemudahan penggunaan | Responsif di komputer dan ponsel (Tailwind CSS).                                                                                                                                                                                                      |
| KNF-05 | Real-time            | Komentar disiarkan melalui Laravel Reverb.                                                                                                                                                                                                            |
| KNF-06 | Integritas data      | _Foreign key_ + transaksi PostgreSQL; versi materi yang disetujui tidak dapat diubah.                                                                                                                                                                 |
| KNF-07 | Portabilitas         | Nuxt, Laravel, Reverb, dan PostgreSQL dapat dijalankan sebagai _container_ Docker: _backend_ dan _frontend_ masing-masing memiliki Dockerfile, dan seluruh layanan dijalankan bersama dengan satu perintah `docker compose up --build`.               |
| KNF-08 | Keterpeliharaan      | Git + GitHub; setiap _endpoint_ diuji dengan Postman sebelum diintegrasikan ke _frontend_.                                                                                                                                                            |
| KNF-09 | Performa query       | Tidak ada query N+1. Semua relasi yang ditampilkan di daftar dimuat dengan _eager loading_; _lazy loading_ dimatikan di lingkungan pengembangan.                                                                                                      |

## 10. Model data

Semua tabel memakai `id BIGSERIAL` dan `created_at`/`updated_at`. Nama tabel **berbahasa Indonesia dan tunggal** sesuai ERD (model Laravel wajib menulis `protected $table`). _Draf 2.2 tidak mengubah struktur tabel; yang berubah hanya arti `materi.status` (lihat 8.2). Draf 2.3 menambah kolom judul/ringkasan/sampul pada `revisi_materi`, tabel `kategori_revisi`, dan tabel `penarikan_materi`. Draf 2.4 menambah tabel `program`, `pic_program`, dan `dokumentasi_program` (KF-21). Draf 2.5 menambah tabel `riwayat_struktur`; `periode_kepengurusan` tidak berubah strukturnya, tetapi barisnya kini dibuat otomatis (KP-07.1)._

| Tabel                  | Kolom penting                                                                                                                                                     | Relasi                                              |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| `users`                | nama, email (unique), password, role (`admin`/`anggota`), status (`menunggu`/`aktif`/`ditolak`/`nonaktif`)                                                        | 1:1 anggota; 1:N materi (penulis_id); 1:N komentar  |
| `anggota`              | user_id (unique), no_hp, alamat, alasan_bergabung, foto?, tanggal_bergabung?, catatan_verifikasi?, diverifikasi_pada?                                             | N:1 users; 1:N kepengurusan                         |
| `periode_kepengurusan` | nama_periode, tahun_mulai, tahun_selesai, is_aktif — _Draf 2.5:_ dibuat otomatis per tahun (nama = tahun, mis. "2027"); hanya satu aktif | 1:N kepengurusan; 1:N riwayat_struktur |
| `divisi`               | nama_divisi (unique), deskripsi?                                                                                                                                  | 1:N kepengurusan                                    |
| `jabatan`              | nama_jabatan (unique), urutan                                                                                                                                     | 1:N kepengurusan                                    |
| `kepengurusan`         | anggota_id, periode_id, divisi_id? (null = pengurus inti), jabatan_id; unique(anggota_id, periode_id)                                                             | N:1 ke keempatnya                                   |
| `materi`               | penulis_id → users, judul, slug (unique), ringkasan?, sampul? (path gambar di disk `public`; KF-18) — keempatnya **data versi publik** (§8.2), status publikasi (`draf`/`diajukan`/`terbit`/`ditolak`), diterbitkan_pada? (pertama kali terbit untuk umum) | 1:N revisi_materi (cascade); 1:N komentar (cascade); N:M `kategori` lewat `kategori_materi` (minimal satu, KP-19.2) |
| `kategori`             | nama (unique), slug (unique; dibentuk otomatis dari nama)                                                                                                                                                                        | N:M `materi` lewat `kategori_materi`; N:M `revisi_materi` lewat `kategori_revisi` (KF-19) |
| `kategori_materi`      | materi_id → materi (cascade), kategori_id → kategori (cascade); unique(materi_id, kategori_id)                                                                                                                                   | tabel pivot                                         |
| `revisi_materi`        | materi_id, nomor_versi (unique bersama materi_id), judul, ringkasan?, sampul?, konten (JSONB Delta), konten_html, catatan_revisi?, status (`draf`/`diajukan`/`disetujui`/`ditolak`) | 1:N verifikasi_materi; N:M `kategori` lewat `kategori_revisi` (minimal satu) |
| `kategori_revisi`      | revisi_id → revisi_materi (cascade), kategori_id → kategori; unique(revisi_id, kategori_id) | tabel pivot (kategori per versi, KP-19.3) |
| `verifikasi_materi`    | revisi_id → revisi_materi, admin_id → users, keputusan (`disetujui`/`ditolak`), catatan?                                                                          | —                                                   |
| `penarikan_materi`     | materi_id → materi (cascade), alasan, status (`diajukan`/`disetujui`/`ditolak`/`dibatalkan`), admin_id? → users, catatan?, diputuskan_pada? | N:1 materi (KF-20) |
| `komentar`             | materi_id, user_id, parent_id? → komentar (cascade), isi?, gambar?                                                                                                | rekursif (balasan)                                  |
| `pengaturan_beranda`   | cerita_judul?, cerita_isi? (HTML tersanitasi), visi?, misi? (KF-26) — **satu baris saja** (KF-18)                                              | —                                                   |
| `program`              | nama (unique, maks 150), slug (unique), ringkasan? (maks 300), jadwal? (teks bebas, maks 150), sampul? (path gambar di disk `public`), deskripsi (HTML tersanitasi), status (`aktif`/`selesai`) | N:M `anggota` lewat `pic_program` (minimal satu); 1:N `dokumentasi_program` (cascade) — KF-21 |
| `pic_program`          | program_id → program (cascade), anggota_id → anggota (cascade); unique(program_id, anggota_id) | tabel pivot PIC (KP-21.2) |
| `dokumentasi_program`  | program_id → program (cascade), jenis (`foto`/`video`/`youtube`), path? (berkas foto/video di disk `public`), youtube_id? (11 karakter), keterangan? (maks 255) | N:1 program (KP-21.3) |
| `riwayat_struktur`     | periode_id → periode_kepengurusan (cascade), admin_id? → users, aksi (`tambah`/`ubah`/`hapus`/`periode_baru`), keterangan, data_sebelum? (JSONB `{nama, jabatan, divisi}`), data_sesudah? (JSONB) | N:1 periode (KP-07.7) |
| _Draf 2.8_            | `pengaturan_beranda` + kontak_alamat?, kontak_whatsapp?, kontak_email?, kontak_instagram?; `materi` + jumlah_dibaca (default 0) | KF-22, KF-25 |

Tabel bawaan: `personal_access_tokens` (Sanctum), `notifications`.

Tabel `pengaturan_beranda` sengaja hanya berisi satu baris; barisnya dibuat saat admin pertama kali menyimpan, dan endpoint baca tidak pernah membuat baris baru (KP-18.2).

Indeks tambahan:

- GIN _full-text_ `to_tsvector('simple', judul)` pada `materi` (pencarian publik) dan pada `revisi_materi` (pencarian internal atas revisi terakhir).
- GIN _full-text_ `to_tsvector('simple', regexp_replace(konten_html, '<[^>]+>', ' ', 'g'))` pada `revisi_materi` (dipakai untuk pencarian internal atas revisi terakhir dan pencarian publik atas versi publik).
- Indeks biasa pada setiap kolom _foreign key_ dan kolom `status`.

Data awal (_seeder_):

- 1 akun admin (ketua) dari variabel `.env` (`ADMIN_NAMA`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`) beserta baris `anggota`.
- 6 divisi: Advokasi, Bidang Sosial, Finance, Edukasi, Dewan Fatwa, Media.
- Jabatan awal: Ketua (urutan 1), Koordinator (urutan 2). Jabatan lain ditambah admin.
- 4 kategori materi awal: Fiqih, Akhlak, Politik Islam, Sejarah Islam. Kategori lain ditambah admin lewat `/admin/kategori` (KF-19).
- _Factory_ + _seeder_ data contoh hanya untuk lingkungan lokal.

## 11. Kontrak API

Basis URL: `/api`. Semua respons JSON. Data memakai API Resource Laravel (`{ "data": ... }`, paginasi dengan `meta` dan `links`). Error validasi memakai format bawaan Laravel (422: `{ message, errors }`).

Keterangan akses: **P** = publik, **A** = anggota aktif (termasuk admin), **Adm** = admin.

### Autentikasi

| Metode | Endpoint    | Akses | Keterangan                                                                   |
| ------ | ----------- | ----- | ---------------------------------------------------------------------------- |
| POST   | `/register` | P     | multipart; KF-02, KF-03                                                      |
| POST   | `/login`    | P     | → `{ token, user }`; 403 berisi `status_akun` dan `catatan` jika tidak aktif |
| POST   | `/logout`   | A     | hapus token saat ini                                                         |
| GET    | `/me`       | A     | pengguna yang sedang login                                                   |

### Publik — struktur organisasi

| Metode | Endpoint                      | Akses | Keterangan                                               |
| ------ | ----------------------------- | ----- | -------------------------------------------------------- |
| GET    | `/divisi`                     | P     | daftar divisi                                            |
| GET    | `/publik/struktur`            | P     | _Draf 2.5:_ struktur periode saat ini saja (`data` null bila belum ada periode); KP-01.2 |
| GET    | `/periode`                    | A     | _Draf 2.5 (sebelumnya P):_ semua periode, aktif di atas; tiap periode memuat `akan_berganti` |
| GET    | `/periode/{periode}/struktur` | A     | _Draf 2.5 (sebelumnya P):_ pengurus inti + per divisi (hanya nama, jabatan, divisi); KP-01.3 |
| GET    | `/periode/{periode}/riwayat`  | A     | _Draf 2.5:_ `{ periode, periode_sebelumnya, perbandingan: { masuk, keluar, pindah }, log }`; KP-07.8 |

### Publik — materi terbit

| Metode | Endpoint                  | Akses | Keterangan                                                      |
| ------ | ------------------------- | ----- | --------------------------------------------------------------- |
| GET    | `/publik/materi?q=&kategori=&page=` | P     | hanya `terbit`, isi versi publik; kartu memuat `sampul`; `kategori` memakai slug; KF-17, KF-18, KF-19 |
| GET    | `/publik/materi/{slug}`   | P     | versi publik saja; 404 jika belum terbit; tanpa forum & riwayat |

### Publik — kategori materi

| Metode | Endpoint    | Akses | Keterangan                                                     |
| ------ | ----------- | ----- | -------------------------------------------------------------- |
| GET    | `/kategori` | P     | daftar kategori materi (`id`, `nama`, `slug`) untuk penyaring; KF-19 |

### Publik — konten beranda

| Metode | Endpoint         | Akses | Keterangan                                                             |
| ------ | ---------------- | ----- | ---------------------------------------------------------------------- |
| GET    | `/publik/beranda` | P    | cerita Al-Futuuhat + kontak; `null` bila admin belum mengaturnya; KF-18 |

### Publik — program

| Metode | Endpoint                                          | Akses | Keterangan                                                                              |
| ------ | ------------------------------------------------- | ----- | --------------------------------------------------------------------------------------- |
| GET    | `/publik/program`                                 | P     | semua program (aktif di atas) + nama PIC + `jumlah_foto`, `jumlah_video`; tanpa paginasi; KP-21.5 |
| GET    | `/publik/program/{slug}`                          | P     | satu program + `deskripsi`; 404 bila tidak ada; KP-21.5                                 |
| GET    | `/publik/program/{slug}/dokumentasi?jenis=&page=` | P     | terbaru di atas, 12 per halaman; `jenis` = `foto`/`video` (video = MP4 + YouTube); tiap butir `url`, `embed`, `thumbnail`, `keterangan`; KP-21.6 |

### Profil & statistik (Draf 2.8)

| Metode | Endpoint            | Akses | Keterangan |
| ------ | ------------------- | ----- | ---------- |
| GET    | `/profil`           | A     | profil sendiri (beserta data keanggotaan); KF-24 |
| POST   | `/profil`           | A     | multipart `{ nama, no_hp?, alamat?, foto?, hapus_foto? }`; KP-24.1 |
| PUT    | `/profil/password`  | A     | `{ password_lama, password, password_confirmation }`; KP-24.2 |
| GET    | `/admin/statistik`  | Adm   | `{ materi_terpopuler, anggota_teraktif }`; KF-25 |

`/publik/beranda` dan `/admin/beranda` (POST) ditambah `kontak` / `kontak_*` (KF-22).

### Admin — pendaftaran & anggota

| Metode | Endpoint                             | Akses | Keterangan                                    |
| ------ | ------------------------------------ | ----- | --------------------------------------------- |
| GET    | `/admin/pendaftaran?status=menunggu` | Adm   | KF-05                                         |
| GET    | `/admin/pendaftaran/{user}`          | Adm   | detail pendaftar                              |
| PATCH  | `/admin/pendaftaran/{user}`          | Adm   | `{ keputusan: disetujui\|ditolak, catatan? }` |
| GET    | `/admin/anggota?q=&status=&page=`    | Adm   | KF-06                                         |
| GET    | `/admin/anggota/{user}`              | Adm   |                                               |
| PUT    | `/admin/anggota/{user}`              | Adm   | ubah data & peran                             |
| PATCH  | `/admin/anggota/{user}/status`       | Adm   | `{ status: aktif\|nonaktif }`                 |
| PATCH  | `/admin/anggota/{user}/password`     | Adm   | atur ulang kata sandi                         |

### Admin — struktur organisasi

| Metode              | Endpoint                                                    | Akses | Keterangan       |
| ------------------- | ----------------------------------------------------------- | ----- | ---------------- |
| POST/PUT/DELETE     | `/admin/divisi`, `/admin/divisi/{divisi}`                   | Adm   |                  |
| GET/POST/PUT/DELETE | `/admin/jabatan`, `/admin/jabatan/{jabatan}`                | Adm   |                  |
| GET                 | `/admin/kepengurusan`                                       | Adm   | pengurus periode aktif + `periode` (Draf 2.5) |
| POST/PUT/DELETE     | `/admin/kepengurusan`, `/admin/kepengurusan/{kepengurusan}` | Adm   | `{ anggota_id, jabatan_id, divisi_id? }` tanpa periode; dicatat pada periode saat ini + riwayat; 422 untuk periode lama (KP-07.1, KP-07.6, KP-07.7). _Draf 2.5: rute `/admin/periode` dihapus._ |

### Admin — konten beranda

| Metode | Endpoint         | Akses | Keterangan                                                                                          |
| ------ | ---------------- | ----- | --------------------------------------------------------------------------------------------------- |
| GET    | `/admin/beranda` | Adm   | isi awal formulir (sama dengan `/publik/beranda`)                                                    |
| POST   | `/admin/beranda` | Adm   | `{ cerita_judul?, cerita_isi?, visi?, misi?, kontak_*? }`; KF-18, KF-22, KF-26 |

### Admin — kategori materi

| Metode | Endpoint                     | Akses | Keterangan                                                                    |
| ------ | ---------------------------- | ----- | ----------------------------------------------------------------------------- |
| GET    | `/admin/kategori`            | Adm   | daftar kategori + `jumlah_materi`                                             |
| POST   | `/admin/kategori`            | Adm   | `{ nama }`; slug dibentuk otomatis; nama sama → 422 "Nama kategori sudah ada." |
| PUT    | `/admin/kategori/{kategori}` | Adm   | ubah nama (slug ikut diperbarui)                                              |
| DELETE | `/admin/kategori/{kategori}` | Adm   | 422 bila masih dipakai materi; KF-19, KP-19.1                                 |

### Admin — program & dokumentasi

| Metode | Endpoint                               | Akses | Keterangan                                                                                          |
| ------ | -------------------------------------- | ----- | --------------------------------------------------------------------------------------------------- |
| GET    | `/admin/program`                       | Adm   | semua program + PIC (`anggota_id`, `nama`) + jumlah dokumentasi                                      |
| POST   | `/admin/program`                       | Adm   | multipart `{ nama, ringkasan?, jadwal?, status, deskripsi, pic[], sampul? }`; KP-21.1, KP-21.2       |
| GET    | `/admin/program/{program}`             | Adm   | isi awal formulir ubah                                                                               |
| POST   | `/admin/program/{program}`             | Adm   | ubah; isian sama + `hapus_sampul?`. POST, bukan PUT, karena multipart                                |
| DELETE | `/admin/program/{program}`             | Adm   | hapus program + seluruh dokumentasi & berkasnya; KP-21.4                                             |
| POST   | `/admin/program/{program}/dokumentasi` | Adm   | multipart `{ jenis: foto\|video\|youtube, berkas?, tautan?, keterangan? }`, satu butir per permintaan; KP-21.3 |
| DELETE | `/admin/dokumentasi/{dokumentasi}`     | Adm   | hapus satu dokumentasi + berkasnya; KP-21.4                                                          |

### Materi (internal anggota)

| Metode | Endpoint                            | Akses       | Keterangan                                                       |
| ------ | ----------------------------------- | ----------- | ---------------------------------------------------------------- |
| GET    | `/materi?q=&penulis=&status=&kategori=&page=` | A           | **semua** materi; `q` mencakup judul & isi revisi terakhir dan nama penulis (awalan kata); `kategori` (slug) disaring atas kategori revisi terakhir; KF-16, KF-19 |
| GET    | `/materi/{slug}`                    | A           | detail revisi terakhir + `versi_publik_terakhir` (id & nomor revisi `disetujui` terbesar; tetap ada setelah ditarik) + penarikan yang menunggu (alasan hanya untuk penulis & admin); KP-16.4, KP-20.7 |
| GET    | `/materi/{materi}/riwayat`          | A           | versi + status; catatan admin hanya untuk penulis & admin; KF-13 |
| GET    | `/materi/{materi}/riwayat/{revisi}` | A           | isi satu versi (`judul`, `ringkasan`, `sampul`, `kategori`, `konten`, `konten_html`, `catatan_revisi`); 404 bila versi bukan milik materi itu; dipakai juga untuk perbandingan KP-10.7; KP-13.4 |
| GET    | `/materi-saya?q=&status=&page=`     | A (bukan admin) | semua materi milik pengguna; `q` mencakup judul + isi revisi terakhir (awalan kata); tiap baris memuat `versi_publik_terakhir` dan penarikan yang menunggu (KP-10.7, KF-20); admin → 403 (KP-08.6) |
| POST   | `/materi`                           | A (bukan admin) | buat materi + v1 draf; `sampul` = path dari `/unggah-sampul`; `kategori` = array id (wajib minimal satu); KF-08, KF-18, KF-19; admin → 403 (KP-08.6) |
| GET    | `/materi/{materi}/edit`             | A (penulis) | revisi terakhir (judul, ringkasan, sampul, isi) + id kategori revisi itu + `versi_publik_terakhir` (KP-10.7) |
| PUT    | `/materi/{materi}`                  | A (penulis) | ubah revisi `draf` (semua bagian, kapan saja); data publik tidak berubah selama `terbit`; KF-09, KP-12.4, KF-18, KF-19 |
| DELETE | `/materi/{materi}`                  | A (penulis) | hanya yang belum pernah terbit & tidak sedang diajukan           |
| POST   | `/materi/{materi}/ajukan`           | A (penulis) | ajukan publikasi kepada umum; 422 bila materi `terbit`; KF-10    |
| POST   | `/materi/{materi}/batal-ajukan`     | A (penulis) | batalkan pengajuan; KP-10.6                                      |
| POST   | `/materi/{materi}/revisi`           | A (penulis) | revisi baru; KF-12                                               |
| POST   | `/materi/{materi}/tarik`            | A (penulis) | `{ alasan }`; ajukan penarikan dari publik; KP-20.1              |
| POST   | `/materi/{materi}/batal-tarik`      | A (penulis) | batalkan pengajuan penarikan; KP-20.3                            |
| POST   | `/unggah-gambar`                    | A           | gambar untuk editor Quill → `{ url }`                            |
| POST   | `/unggah-sampul`                    | A (bukan admin) | multipart `sampul` (JPG/PNG maks 2 MB) → `{ path, url }`; KF-18; admin → 403 (KP-08.6) |

### Admin — verifikasi publikasi & penarikan materi

| Metode | Endpoint                            | Akses | Keterangan                           |
| ------ | ----------------------------------- | ----- | ------------------------------------ |
| GET    | `/admin/verifikasi-materi`          | Adm   | antrean revisi `diajukan`            |
| GET    | `/admin/revisi/{revisi}`            | Adm   | isi revisi + versi publik pembanding |
| POST   | `/admin/revisi/{revisi}/verifikasi` | Adm   | `{ keputusan, catatan? }`; KF-11     |
| GET    | `/admin/penarikan-materi`           | Adm   | antrean penarikan `diajukan` (terlama di atas); KP-20.4 |
| POST   | `/admin/penarikan/{penarikan}/verifikasi` | Adm | `{ keputusan, catatan? }` (catatan wajib bila ditolak); KP-20.5–20.7 |

### Forum

| Metode | Endpoint                          | Akses       | Keterangan                                                        |
| ------ | --------------------------------- | ----------- | ----------------------------------------------------------------- |
| GET    | `/materi/{materi}/komentar?page=` | A           | komentar induk + `jumlah_balasan` (tanpa balasan, KP-14.5); materi status apa pun |
| POST   | `/materi/{materi}/komentar`       | A           | multipart `{ isi?, gambar?, parent_id? }`                         |
| GET    | `/komentar/{komentar}/balasan`    | A           | seluruh balasan satu komentar induk (KP-14.5); 404 bila bukan induk |
| DELETE | `/komentar/{komentar}`            | A (pemilik) |                                                                   |
| POST   | `/broadcasting/auth`              | A           | otorisasi kanal Reverb                                            |

_Event_ di kanal privat `materi.{id}`: `komentar.dibuat` (data = KomentarResource), `komentar.dihapus` (`{ id, parent_id }`).

### Notifikasi & dasbor

| Metode | Endpoint                 | Akses | Keterangan                                                                              |
| ------ | ------------------------ | ----- | --------------------------------------------------------------------------------------- |
| GET    | `/notifikasi`            | A     | notifikasi pengguna (paginasi)                                                          |
| PATCH  | `/notifikasi/{id}/baca`  | A     | tandai dibaca                                                                           |
| PATCH  | `/notifikasi/baca-semua` | A     |                                                                                         |
| GET    | `/dasbor`                | A     | ringkasan pribadi (jumlah materi per status publikasi, notifikasi belum dibaca)         |
| GET    | `/admin/dasbor`          | Adm   | jumlah pendaftar menunggu, pengajuan publikasi, pengajuan penarikan, anggota aktif, materi terbit untuk umum |

## 12. Halaman frontend (Nuxt)

| Route                                                             | Layout  | Akses       | Isi                                                                                  |
| ----------------------------------------------------------------- | ------- | ----------- | ------------------------------------------------------------------------------------ |
| `/`                                                               | publik  | P           | beranda: sambutan, artikel & program terbaru, cerita Al-Futuuhat, struktur per periode, tautan ke Artikel, cara bergabung |
| `/tentang`                                                        | publik  | P           | Tentang kami: cerita lengkap, visi, misi (KF-26) |
| `/publikasi`                                                      | publik  | P           | Artikel: grid kartu artikel terbit (bersampul) + pencarian + penyaring kategori |
| `/publikasi/[slug]`                                               | publik  | P           | baca versi publik (tanpa forum & riwayat) + chip kategori                        |
| `/program`                                                        | publik  | P           | grid kartu program (aktif di atas); KP-21.5                                          |
| `/program/[slug]`                                                 | publik  | P           | tab Tentang program (penjelasan, jadwal, PIC) dan Dokumentasi (galeri + penyaring Semua/Foto/Video); KP-21.5, KP-21.6 |
| `/daftar`                                                         | publik  | tamu        | formulir pendaftaran                                                                 |
| `/daftar/berhasil`                                                | publik  | tamu        | status menunggu verifikasi                                                           |
| `/masuk`                                                          | publik  | tamu        | login + 4 jenis pesan (KP-04)                                                        |
| `/dasbor`                                                         | anggota | A (bukan admin) | ringkasan & pintasan                                                                 |
| `/materi`                                                         | anggota | A           | arsip **seluruh** materi anggota + pencarian + filter status publikasi + filter kategori |
| `/materi/[slug]`                                                  | anggota | A           | baca revisi terakhir + label status publikasi + chip kategori + riwayat versi + forum _real-time_ |
| `/materi-saya`                                                    | anggota | A (bukan admin) | daftar materi milik sendiri per status + pencarian (kotak cari di sidebar; di ponsel di atas daftar); tombol Ubah, Ajukan publikasi, atau Ajukan tarik materi (bila terbit) |
| `/materi-saya/baru`                                               | anggota | A (bukan admin) | editor Quill                                                                         |
| `/materi-saya/[id]/edit`                                          | anggota | A (penulis) | editor Quill (draf/revisi, semua bagian dapat diubah) + tombol Ajukan Publikasi / Batalkan Pengajuan, atau Ajukan Tarik Materi / Batalkan Penarikan bila materi terbit |
| `/struktur`                                                       | anggota | A           | bagan + pemilih periode (termasuk periode lama), lalu bagian Riwayat perubahan: tab "Dibanding periode sebelumnya" (Masuk/Keluar/Pindah jabatan) dan "Log perubahan" (KP-01.3, KP-07.8) |
| `/notifikasi`                                                     | anggota | A           | daftar notifikasi                                                                    |
| `/profil`                                                         | anggota | A           | data diri + ganti kata sandi (admin melihatnya di layout admin); KF-24 |
| `/admin`                                                          | admin   | Adm         | dasbor admin                                                                         |
| `/admin/pendaftaran`, `/admin/pendaftaran/[id]`                   | admin   | Adm         | verifikasi pendaftaran                                                               |
| `/admin/anggota`, `/admin/anggota/[id]`                           | admin   | Adm         | data anggota                                                                         |
| `/admin/struktur`                                                 | admin   | Adm         | tab Pengurus (periode saat ini), Divisi, Jabatan, Riwayat (pilih periode + riwayat); tab Periode dihapus (Draf 2.5) |
| `/admin/verifikasi-materi`, `/admin/verifikasi-materi/[revisiId]` | admin   | Adm         | antrean & keputusan: tab Publikasi dan tab Penarikan (KF-11, KF-20)                  |
| `/admin/beranda`                                                  | admin   | Adm         | konten beranda: cerita Al-Futuuhat + kontak organisasi                                          |
| `/admin/kategori`                                                 | admin   | Adm         | daftar kategori materi (tambah, ubah, hapus)                                         |
| `/admin/program`, `/admin/program/baru`, `/admin/program/[id]`    | admin   | Adm         | daftar program; formulir tambah; kelola program dengan tab Informasi dan Dokumentasi (unggah + hapus) — KF-21 |

Layout: `publik` (header + footer; menu Beranda, Artikel, Program, Cara bergabung, Masuk/Daftar — _Draf 2.4: Struktur diganti Program, KP-21.7_), `anggota` (navbar atas di tablet/desktop; header + toolbar bawah di ponsel), `admin` (sidebar kiri). Desain visual detail menyusul.

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

### 13.2 Lingkungan Docker (demo, sidang, _deploy_) — `docker compose up -d --build`

| Layanan    | Image                              | Port laptop      | Keterangan                                     |
| ---------- | ---------------------------------- | ---------------- | ---------------------------------------------- |
| `db`       | `postgres:16-alpine`               | — (tidak dibuka) | volume `pgdata`                                |
| `backend`  | dari `backend/Dockerfile` (Apache) | 8000 → 80        | volume `storage` untuk unggahan; `.env.docker` |
| `reverb`   | image yang sama dengan `backend`   | 8080             | `php artisan reverb:start`                     |
| `frontend` | dari `frontend/Dockerfile`         | 3000             | `NUXT_PUBLIC_*` dibaca saat kontainer berjalan |

- Antarkontainer memakai nama layanan (`DB_HOST=db`, `REVERB_HOST=reverb`); konfigurasi yang dibaca peramban memakai `localhost`.
- Image _backend_ dibangun dengan `composer install --no-dev`, sehingga _factory_/data contoh tidak tersedia di Docker; _seeder_ yang memanggil _factory_ harus dibatasi ke lingkungan `local`.
- Migrasi pertama: `docker compose exec -u www-data backend php artisan migrate --seed --force`.
- _Draf 2.4:_ `backend/Dockerfile` menaikkan batas unggahan PHP (`upload_max_filesize=55M`, `post_max_size=60M`) karena bawaan image hanya 2 MB, sedangkan video dokumentasi program boleh 50 MB (KP-21.3).
- `.dockerignore` wajib mengecualikan `vendor/`, `node_modules/`, `.env*`, dan `bootstrap/cache/*.php` milik laptop.
- Nama berkas harus sama persis dengan nama _class_ (huruf besar/kecil), karena Linux di dalam kontainer membedakannya.
- Tidak memakai _queue worker_: _event_ memakai `ShouldBroadcastNow` dan notifikasi dikirim langsung (sinkron) agar sederhana.

## 14. Urutan pembangunan (tugas untuk Claude Code)

Kerjakan **satu tugas per sesi Claude Code** (ketik `/clear` sebelum tugas berikutnya). Setelah setiap tugas: jalankan migrasi/tes, uji di Postman, lalu _commit_. Cara yang disarankan tetap per fitur sesuai `docs/ROADMAP.md`; tabel ini adalah pembagian per lapisan sebagai rujukan.

| #   | Tugas                                                                                                                                                                                                                             | Selesai jika                                                                              |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| T1  | Kerangka repo: Laravel di `backend/`, Nuxt di `frontend/`, berjalan lokal (§13.1); Dockerfile + `.dockerignore` _backend_ dan _frontend_, `docker-compose.yml`, `.env.docker.example`, README cara menjalankan (lokal dan Docker) | Ketiga proses lokal berjalan, dan `docker compose up --build` menjalankan keempat layanan |
| T2  | Migrasi, model, relasi, _factory_, _seeder_ sesuai bagian 10                                                                                                                                                                      | `php artisan migrate:fresh --seed` sukses                                                 |
| T3  | Backend autentikasi & pendaftaran (KF-02 s.d. KF-05)                                                                                                                                                                              | KP-02 s.d. KP-05 lulus di Postman                                                         |
| T4  | Backend data anggota & struktur organisasi (KF-01, KF-06, KF-07)                                                                                                                                                                  | KP-01, KP-06, KP-07 lulus                                                                 |
| T5  | Backend materi internal: tulis, kelola, unggah gambar, arsip & pencarian anggota, forum + Reverb (KF-08, KF-09, KF-14 s.d. KF-16)                                                                                                 | KP-08, KP-09, KP-14 s.d. KP-16 lulus                                                      |
| T6  | Backend publikasi: pengajuan, verifikasi, revisi, riwayat, notifikasi, API publik (KF-10 s.d. KF-13, KF-17)                                                                                                                       | KP-10 s.d. KP-13 dan KP-17 lulus                                                          |
| T7  | Frontend fondasi: `useApi`, `useAuth`, _middleware_, layout, plugin Echo, tipe data                                                                                                                                               | login/logout berjalan                                                                     |
| T8  | Frontend publik: beranda, daftar, masuk                                                                                                                                                                                           | KP-01 s.d. KP-04 lulus di peramban                                                        |
| T9  | Frontend admin: pendaftaran, anggota, struktur                                                                                                                                                                                    | KP-05 s.d. KP-07 lulus                                                                    |
| T10 | Frontend materi internal: editor Quill, materi saya, arsip, baca, forum _real-time_                                                                                                                                               | KP-08, KP-09, KP-14 s.d. KP-16 lulus                                                      |
| T11 | Frontend publikasi: ajukan, verifikasi admin, revisi & riwayat, halaman `/publikasi`, notifikasi                                                                                                                                  | KP-10 s.d. KP-13 dan KP-17 lulus                                                          |
| T12 | Penyempurnaan: responsif, pesan kosong/muat/error, koleksi Postman, daftar skenario Black Box                                                                                                                                     | semua KP lulus                                                                            |
| T13 | Konten beranda pengunjung: poster & cerita yang diatur admin, gambar sampul materi, grid kartu artikel (KF-18)                                                                                                                    | KP-18.1 s.d. KP-18.5 lulus                                                                |
| T14 | Kategori materi: pengelolaan oleh admin, pemilihan kategori pada materi, penyaring & chip kategori di halaman anggota dan publik (KF-19)                                                                                          | KP-19.1 s.d. KP-19.4 lulus                                                                |
| T15 | Penarikan materi & bagian materi per versi: kolom judul/ringkasan/sampul + `kategori_revisi` pada revisi, tabel `penarikan_materi`, pengajuan/pembatalan/verifikasi penarikan, tab Penarikan di admin (KF-20; perubahan KP-09.1, KP-10, KP-11, KP-12.4, KP-18.4, KP-19.3) | KP-20.1 s.d. KP-20.7 lulus dan KP yang diubah tetap lulus |
| T16 | Program kegiatan: tabel `program`, `pic_program`, `dokumentasi_program`; pengelolaan program & dokumentasi oleh admin; halaman `/program` dan `/program/[slug]`; menu publik Struktur → Program; batas unggahan PHP di Dockerfile (KF-21) | KP-21.1 s.d. KP-21.7 lulus |
| T17 | Periode otomatis & riwayat struktur: tabel `riwayat_struktur`, `PeriodeKepengurusan::saatIni()`, hapus CRUD periode, periode lama hanya-baca, endpoint publik/anggota/riwayat, halaman `/struktur` dan tab Riwayat admin (perubahan KF-01, KF-07) | KP-01.2, KP-01.3, KP-07.1 s.d. KP-07.8 lulus |
| T18 | Kenyamanan pengguna: beranda terbaru, bagikan, kontak, ukuran tulisan, notifikasi komentar, profil, statistik admin (KF-22–KF-25) | KP-22 s.d. KP-25 lulus |

Contoh prompt untuk Claude Code (ganti nomor fitur; cara yang disarankan adalah `/fitur` sesuai `docs/ROADMAP.md`):

```
Baca docs/PRD.md dan docs/ROADMAP.md. Kerjakan fitur F5, bagian BACKEND saja.
Gunakan skill backend-laravel. Sebelum menulis kode, tampilkan rencana singkat
(file yang akan dibuat/diubah). Setelah selesai, jalankan tes, jelaskan cara
mengujinya di Postman, dan sebutkan KP mana saja yang sudah terpenuhi.
```

## 15. Rencana pengujian (Black Box)

Setiap KP pada bagian 7 menjadi minimal satu skenario uji dengan format:

| No  | KF/KP | Skenario | Data uji | Hasil yang diharapkan | Hasil | Status |
| --- | ----- | -------- | -------- | --------------------- | ----- | ------ |

Skenario wajib mencakup jalur normal dan pengecualian (data tidak valid, akses bukan pemilik → 403, akses bukan admin → 403, akses tanpa login ke data internal → 401, materi belum terbit lewat jalur publik → 404, status tidak sesuai → 422, berkas > 2 MB atau bukan JPG/PNG).

## 16. Keputusan terbuka (asumsi sementara)

Asumsi berikut dipakai sampai Anda memutuskan lain. Ubah di sini jika berbeda. Nomor 6 dan 9–19 sudah **diputuskan** (10–11 November 2026).

| No  | Pertanyaan                                                                                                  | Asumsi sementara                                                         |
| --- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| 1   | Foto pendaftaran wajib atau opsional?                                                                       | Opsional                                                                 |
| 2   | Pendaftar yang ditolak boleh daftar ulang dengan email sama?                                                | Tidak; menghubungi pengurus (admin dapat mengaktifkan dari Data Anggota) |
| 3   | Tambahan isian pendaftaran (tgl lahir, divisi minat)?                                                       | Tidak; sesuai KF-02                                                      |
| 4   | Admin boleh menghapus komentar anggota lain (moderasi)?                                                     | Tidak; sesuai KF-14                                                      |
| 5   | Kedalaman balasan komentar                                                                                  | Satu tingkat                                                             |
| 6   | Kategori/tag materi (mis. per divisi)?                                                                      | **Diputuskan:** ada (KF-19). Banyak kategori per materi (pivot `kategori_materi`), wajib minimal satu, disimpan per versi sejak Draf 2.3 (`kategori_revisi`), dikelola admin di `/admin/kategori` |
| 7   | Nama jabatan selain Ketua dan Koordinator                                                                   | Ditambah admin melalui menu Jabatan                                      |
| 8   | Tema visual                                                                                                 | Ditentukan kemudian; sementara Tailwind netral                           |
| 9   | Apakah materi langsung terlihat semua anggota sejak pertama disimpan, atau perlu tahap "draf pribadi" dulu? | **Diputuskan:** langsung terlihat semua anggota (tidak ada draf pribadi) |
| 10  | Apakah pengunjung dapat melihat (tanpa mengirim) forum diskusi pada materi publik?                          | **Diputuskan:** tidak; forum hanya untuk anggota                         |
| 11  | Apakah catatan keputusan admin dapat dilihat semua anggota?                                                 | **Diputuskan:** tidak; hanya penulis (dan admin)                         |
| 12  | Apakah penulis dapat membatalkan pengajuan publikasi sebelum diputuskan admin?                              | **Diputuskan:** ya (KP-10.6)                                             |
| 13  | Bagaimana mengganti versi publik materi yang sudah terbit?                                                  | **Diputuskan (11 November 2026):** penulis mengajukan penarikan dengan alasan; setelah admin menyetujui, materi hilang dari publik (`draf`) dan dapat diajukan lagi (KF-20). Antrean penarikan berada di halaman verifikasi admin (tab tersendiri) |
| 15  | Bagaimana penulis memastikan perubahan sebelum mengajukan ulang materi yang pernah terbit?                   | **Diputuskan (11 November 2026):** dialog konfirmasi berisi perbandingan dua kolom dengan versi publik terakhir, bagian berbeda diberi label "Berubah", dan kata yang berubah disorot (KP-10.7). Versi publik juga dapat dibaca dari halaman materi lewat keterangan "Versi publik: vN" (KP-16.4) |
| 14  | Bagian materi apa yang boleh diubah setelah terbit?                                                         | **Diputuskan (11 November 2026):** semuanya (judul, ringkasan, sampul, kategori, isi), kapan saja, sebagai revisi `draf`; disimpan per versi sehingga versi publik tidak berubah (KP-12.4) |
| 16  | Bagaimana program kegiatan dan dokumentasinya ditampilkan?                                                  | **Diputuskan (11 November 2026):** fitur Program (KF-21) dikelola admin saja; PIC satu atau lebih anggota aktif; isian ringkasan, jadwal (teks bebas), sampul, dan status aktif/selesai; dokumentasi berupa foto JPG/PNG ≤ 2 MB, video MP4 ≤ 50 MB per berkas, atau tautan YouTube; menu publik Struktur diganti Program; navbar anggota tidak ditambah menu Program |
| 17  | Bagaimana periode kepengurusan dibuat dan siapa yang melihat periode lama?                                  | **Diputuskan (11 November 2026):** periode dibuat otomatis per tahun kalender pada perubahan pertama di tahun itu, menyalin susunan lama; periode lama hanya-baca; riwayat berisi log per aksi (dengan nama admin) dan perbandingan dengan periode sebelumnya; anggota & admin melihat semua periode dan riwayat, pengunjung hanya periode saat ini |
| 18  | Apakah admin boleh menulis materi?                                                                          | **Diputuskan (11 November 2026):** tidak; admin hanya mengelola (KP-08.6). Admin tetap membaca materi dan boleh berkomentar di forum; materi milik anggota yang diangkat menjadi admin tetap ada tetapi terkunci sampai perannya dikembalikan |
| 19  | Fitur kenyamanan apa yang ditambahkan?                                                                      | **Diputuskan (11 November 2026):** KF-22–KF-25. Kontak diatur admin; notifikasi komentar untuk penulis + yang dibalas; profil: nama, HP, alamat, foto, kata sandi; jumlah baca dihitung setiap halaman dibuka |
