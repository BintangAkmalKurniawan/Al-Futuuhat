# Dokumentasi As-Built — Sistem Informasi Al-Futuuhat

| | |
|---|---|
| **Dasar penyusunan** | Kode di repositori (branch `fitur/program`) dan skema basis data PostgreSQL hasil seluruh migrasi, per 11 November 2026 |
| **Bukan dasar penyusunan** | Rencana di `docs/PRD.md` (perbandingannya ada di bagian 7) |
| **Penanda** | **[BELUM ADA]** = tidak diimplementasikan di kode; **[TIDAK JELAS]** = perilaku tidak dapat dipastikan dari kode |

Sumber utama: `backend/routes/api.php` (`php artisan route:list --path=api`), `backend/app/Http/Controllers`, `backend/app/Policies`, `backend/app/Http/Middleware`, `backend/database/migrations`, `frontend/app/pages`, `frontend/app/middleware`, `frontend/app/layouts`.

---

## 1. Aktor dan hak akses

### 1.1 Peran dan status akun

Kolom `users.role`: `admin` atau `anggota` (bawaan `anggota`).
Kolom `users.status`: `menunggu`, `aktif`, `ditolak`, `nonaktif` (bawaan `menunggu`).

| Aktor | Kondisi di kode | Keterangan |
|---|---|---|
| **Pengunjung / calon anggota** | Tanpa token Sanctum | Hanya rute tanpa middleware (publik). Pendaftar yang belum disetujui juga termasuk di sini karena tidak mendapat token. |
| **Anggota** | `role = anggota`, `status = aktif`, punya token | Semua rute grup `auth:sanctum` + `aktif`. Menulis dan mengelola materi miliknya. |
| **Admin (ketua/pengurus)** | `role = admin`, `status = aktif`, punya token | Rute anggota + grup `admin`, **kecuali** semua aksi menulis/mengelola materi (ditolak `MateriPolicy`). |

Akun admin pertama dibuat oleh `AdminSeeder` (dipanggil `DatabaseSeeder`). [TIDAK JELAS] isi persis `AdminSeeder` tidak dibaca dalam penyusunan ini; komentar kode menyebut akun diambil dari variabel `.env` `ADMIN_*`.

### 1.2 Pembatas di backend

| Pembatas | Berkas | Aturan |
|---|---|---|
| `auth:sanctum` | bawaan Laravel | Wajib token Bearer. Tanpa token → 401 (pesan diganti "Sesi sudah berakhir. Silakan masuk lagi." di `bootstrap/app.php`). |
| `aktif` | `app/Http/Middleware/EnsureUserIsActive.php` | Pengguna harus `status = aktif`, selain itu 403. |
| `admin` | `app/Http/Middleware/EnsureUserIsAdmin.php` | Pengguna harus `role = admin`, selain itu 403 "Halaman ini khusus admin." |
| `MateriPolicy::buat` | `app/Policies/MateriPolicy.php` | Admin ditolak (403). Dipakai: `POST /materi` (lewat `SimpanMateriRequest`), `GET /materi-saya`, `POST /unggah-sampul`. |
| `MateriPolicy::ubah/hapus/ajukan/buatRevisi/tarik` | sama | Harus penulis materi **dan** bukan admin. Admin → 403 "Akun admin digunakan untuk mengelola sehingga tidak dapat menulis materi."; bukan penulis → 403. |
| Pemilik komentar | `KomentarController@destroy` | `abort_unless(user_id === pengguna)` → 403. |
| Catatan admin di riwayat | `MateriController::bolehLihatKeputusan` | Keputusan/catatan verifikasi hanya dimuat untuk penulis dan admin. |
| Kanal Reverb `materi.{id}` | `routes/channels.php` | Pengguna aktif dan materinya ada. |
| Kanal `App.Models.User.{id}` | `routes/channels.php` | Didefinisikan, tetapi tidak dipakai event apa pun. Notifikasi real-time per pengguna **[BELUM ADA]**. |

### 1.3 Pembatas di frontend (Nuxt)

| Middleware | Berkas | Aturan |
|---|---|---|
| `auth` | `middleware/auth.ts` | Tanpa token → `/masuk`. Memuat `/me` bila state kosong. Bila admin membuka halaman ber-layout `anggota`, layout diganti `admin` (`setPageLayout`). |
| `guest` | `middleware/guest.ts` | Pengguna yang sudah masuk dialihkan: admin → `/admin`, anggota → `/dasbor`. |
| `admin` | `middleware/admin.ts` | Bukan admin → `/dasbor`. |
| `bukan-admin` | `middleware/bukan-admin.ts` | Admin → `/admin` (dipakai halaman penulis: dasbor anggota dan Materi saya). |

Pembatasan sebenarnya tetap di backend; middleware frontend hanya mengatur navigasi.

---

## 2. Daftar fitur per aktor

Status: **selesai** = ada di backend dan frontend serta dicakup tes otomatis/fungsional; **sebagian** = ada tetapi dengan batasan yang dicatat.

### 2.1 Pengunjung / calon anggota

| Fitur | Aktor | Route API | Halaman Nuxt | Status |
|---|---|---|---|---|
| Beranda (sambutan, artikel & program terbaru, cerita ringkas, bagan struktur periode aktif, cara bergabung) | Pengunjung | `GET /publik/beranda`, `GET /publik/materi`, `GET /publik/program`, `GET /publik/struktur` | `/` | selesai |
| Tentang kami (cerita, visi, misi) | Pengunjung | `GET /publik/beranda` | `/tentang` | selesai |
| Daftar & baca artikel publik, cari, saring kategori | Pengunjung | `GET /publik/materi?q=&kategori=&page=`, `GET /publik/materi/{slug}`, `GET /kategori` | `/publikasi`, `/publikasi/[slug]` | selesai |
| Program & dokumentasi | Pengunjung | `GET /publik/program`, `GET /publik/program/{slug}`, `GET /publik/program/{slug}/dokumentasi` | `/program`, `/program/[slug]` | selesai |
| Pendaftaran anggota | Calon anggota | `POST /register` | `/daftar`, `/daftar/berhasil` | selesai |
| Masuk | Semua | `POST /login` | `/masuk` | selesai |
| Bagikan artikel/program (lembar bagikan perangkat, salin tautan, WhatsApp) | Pengunjung | — (frontend) | `/publikasi/[slug]`, `/program/[slug]` | selesai |
| Ukuran tulisan A− / A+ | Pengunjung | — (frontend, `localStorage`) | `/publikasi/[slug]` | selesai |
| Dengarkan artikel (Web Speech API) | Pengunjung | — (frontend) | `/publikasi/[slug]` | sebagian — bergantung peramban; suara pria Indonesia hanya ada bila peramban menyediakannya (mis. Edge "Ardi", Windows "Andika") |
| Kontak organisasi di kaki halaman | Pengunjung | `GET /publik/beranda` | layout `publik` | selesai |
| Lupa / reset kata sandi mandiri | — | — | — | **[BELUM ADA]** (reset hanya oleh admin) |

### 2.2 Anggota

| Fitur | Aktor | Route API | Halaman Nuxt | Status |
|---|---|---|---|---|
| Keluar | Anggota, admin | `POST /logout` | menu akun | selesai |
| Dasbor pribadi (jumlah materi per status, notifikasi) | Anggota | `GET /dasbor`, `GET /materi-saya`, `GET /notifikasi` | `/dasbor` | selesai |
| Arsip seluruh materi (semua status), cari, saring status & kategori | Anggota, admin | `GET /materi?q=&status=&kategori=&penulis=&page=` | `/materi` | selesai |
| Baca materi + riwayat versi + isi versi lama | Anggota, admin | `GET /materi/{slug}`, `GET /materi/{id}/riwayat`, `GET /materi/{id}/riwayat/{revisi}` | `/materi/[slug]` | selesai |
| Tulis materi (Quill, sampul, kategori) | Anggota | `POST /materi`, `POST /unggah-gambar`, `POST /unggah-sampul`, `GET /kategori` | `/materi-saya/baru` | selesai |
| Ubah materi / buat versi baru | Anggota (penulis) | `GET /materi/{id}/edit`, `PUT /materi/{id}`, `POST /materi/{id}/revisi` | `/materi-saya/[id]/edit` | selesai |
| Hapus materi (belum pernah tayang) | Anggota (penulis) | `DELETE /materi/{id}` | `/materi-saya`, editor | selesai |
| Ajukan tayang / batalkan pengajuan | Anggota (penulis) | `POST /materi/{id}/ajukan`, `POST /materi/{id}/batal-ajukan` | `/materi-saya`, editor | selesai |
| Minta turunkan materi dari publik / batalkan | Anggota (penulis) | `POST /materi/{id}/tarik`, `POST /materi/{id}/batal-tarik` | `/materi-saya`, editor | selesai |
| Materi saya (per status) | Anggota | `GET /materi-saya?q=&status=&page=` | `/materi-saya` | selesai |
| Forum diskusi real-time (komentar, balasan 1 tingkat, gambar, hapus milik sendiri) | Anggota, admin | `GET/POST /materi/{id}/komentar`, `GET /komentar/{id}/balasan`, `DELETE /komentar/{id}`, `POST /broadcasting/auth` | `/materi/[slug]` (laci Diskusi) | selesai |
| Notifikasi (daftar, tandai dibaca) | Anggota, admin | `GET /notifikasi`, `PATCH /notifikasi/{id}/baca`, `PATCH /notifikasi/baca-semua` | `/notifikasi` | selesai (tidak real-time, dimuat saat halaman dibuka) |
| Struktur semua periode + riwayat perubahan | Anggota, admin | `GET /periode`, `GET /periode/{id}/struktur`, `GET /periode/{id}/riwayat` | `/struktur` | selesai |
| Profil sendiri & ganti kata sandi | Anggota, admin | `GET /profil`, `POST /profil`, `PUT /profil/password` | `/profil` | selesai |
| Ukuran tulisan & Dengarkan materi | Anggota, admin | — (frontend) | `/materi/[slug]` | sebagian (lihat 2.1) |
| Program & artikel publik | Anggota | rute publik | `/program`, `/publikasi` (layout publik) | selesai |

### 2.3 Admin (ketua/pengurus)

| Fitur | Aktor | Route API | Halaman Nuxt | Status |
|---|---|---|---|---|
| Dasbor admin (angka ringkasan, pendaftar terbaru, antrean, statistik) | Admin | `GET /admin/dasbor`, `GET /admin/statistik`, `GET /admin/pendaftaran`, `GET /admin/verifikasi-materi` | `/admin` | selesai |
| Verifikasi pendaftaran | Admin | `GET /admin/pendaftaran?status=`, `GET/PATCH /admin/pendaftaran/{user}` | `/admin/pendaftaran`, `/admin/pendaftaran/[id]` | selesai |
| Data anggota (cari, ubah data & peran, aktif/nonaktif, atur ulang sandi) | Admin | `GET /admin/anggota`, `GET/PUT /admin/anggota/{user}`, `PATCH .../status`, `PATCH .../password` | `/admin/anggota`, `/admin/anggota/[id]` | selesai |
| Struktur: pengurus periode saat ini, divisi, jabatan, riwayat | Admin | `GET/POST/PUT/DELETE /admin/kepengurusan`, `POST/PUT/DELETE /admin/divisi`, `GET/POST/PUT/DELETE /admin/jabatan`, `GET /divisi`, `GET /periode*` | `/admin/struktur` (tab Pengurus, Divisi, Jabatan, Riwayat) | selesai |
| Pemeriksaan pengajuan tayang (bandingkan versi, setujui/tolak) | Admin | `GET /admin/verifikasi-materi`, `GET /admin/revisi/{id}`, `POST /admin/revisi/{id}/verifikasi` | `/admin/verifikasi-materi` (tab Minta tayang), `/admin/verifikasi-materi/[revisiId]` | selesai |
| Pemeriksaan permintaan penurunan | Admin | `GET /admin/penarikan-materi`, `POST /admin/penarikan/{id}/verifikasi` | `/admin/verifikasi-materi?tab=penarikan` | selesai |
| Kategori materi | Admin | `GET/POST/PUT/DELETE /admin/kategori` | `/admin/kategori` | selesai |
| Konten beranda (cerita, visi, misi, kontak) | Admin | `GET/POST /admin/beranda` | `/admin/beranda` | selesai |
| Program & dokumentasi (foto, video MP4, YouTube) | Admin | `GET/POST /admin/program`, `GET/POST/DELETE /admin/program/{id}`, `POST /admin/program/{id}/dokumentasi`, `DELETE /admin/dokumentasi/{id}` | `/admin/program`, `/admin/program/baru`, `/admin/program/[id]` | selesai |
| Membaca materi & berkomentar | Admin | rute anggota | `/materi`, `/materi/[slug]` (tampil di layout admin) | selesai |
| Menulis materi | Admin | — | — | Sengaja ditolak (403) — keputusan Draf 2.6 |
| Moderasi (hapus komentar orang lain) | — | — | — | **[BELUM ADA]** |

---

## 3. Alur fitur utama

### 3.1 Pendaftaran dan verifikasi anggota

1. Pengunjung membuka `/daftar` (middleware `guest`) dan mengisi nama, email, kata sandi + konfirmasi, nomor HP, alamat, alasan bergabung, foto (opsional).
2. Frontend memeriksa foto lebih dulu: harus JPG/PNG dan ≤ 2 MB; bila tidak, pesan galat tampil tanpa mengirim.
3. `POST /register` (`RegisterRequest`): `nama` wajib maks 255; `email` wajib, format email, unik di `users`; `password` wajib min 8 + `password_confirmation`; `no_hp` wajib maks 20; `alamat` dan `alasan_bergabung` wajib; `foto` opsional image jpg/jpeg/png maks 2048 KB. Gagal → 422 per isian (mis. "Email ini sudah terdaftar. Coba masuk saja, atau pakai email lain.").
4. Foto disimpan ke disk `public/foto-anggota` (di luar transaksi). Lalu dalam satu transaksi dibuat baris `users` (`role = anggota`, `status = menunggu`, kata sandi di-hash oleh cast `hashed`) dan baris `anggota`.
5. Respons 201; frontend ke `/daftar/berhasil`. **Tidak ada login otomatis.**
6. Admin membuka `/admin/pendaftaran` → `GET /admin/pendaftaran?status=menunggu|ditolak` (status lain dipaksa `menunggu`), urut terbaru, 10 per halaman.
7. Admin membuka detail `/admin/pendaftaran/[id]` (data pribadi + foto).
8. Admin memutuskan `PATCH /admin/pendaftaran/{user}` `{ keputusan: disetujui|ditolak, catatan? }`:
   - catatan wajib bila `ditolak` (422 "Mohon isi alasan penolakan.");
   - bila status pendaftar bukan `menunggu` → 422 "Pendaftar ini sudah diputuskan sebelumnya.";
   - **disetujui** → `users.status = aktif`; `anggota.tanggal_bergabung = hari ini`, `diverifikasi_pada = sekarang`, `catatan_verifikasi = catatan`;
   - **ditolak** → `users.status = ditolak`; `anggota.catatan_verifikasi = catatan`.
9. Tidak ada notifikasi ke pendaftar; hasilnya terlihat saat ia mencoba masuk (lihat 3.2). Pendaftar yang ditolak tidak bisa mendaftar ulang dengan email sama (email unik); admin dapat mengaktifkannya lewat Data anggota (`PATCH /admin/anggota/{user}/status`).

### 3.2 Login dan logout

1. `/masuk` (middleware `guest`) → `POST /login { email, password }` (`LoginRequest`: keduanya wajib, email berformat).
2. Email tidak ada atau kata sandi salah → 422 pada `email`: "Email atau kata sandinya belum cocok. Coba cek lagi ya." (pesan sama untuk keduanya).
3. Akun ada tetapi `status ≠ aktif` → 403 `{ message, status_akun, catatan }`:
   - `menunggu` → "Kamu belum bisa masuk dulu. Tunggu kabar dari admin ya, biasanya tidak lama.";
   - `ditolak` → "Admin belum menyetujui pendaftaranmu…" dan `catatan` berisi alasan admin (ditampilkan halaman sebagai "Catatan dari admin");
   - `nonaktif` → "Kamu belum bisa masuk dulu. Hubungi pengurus untuk mengaktifkannya lagi ya."
4. Akun aktif → token Sanctum `createToken('api')`, respons `{ token, user }`. Frontend menyimpan token di cookie `token`, data pengguna di state `user`.
5. Pengalihan: admin → `/admin`, anggota → `/dasbor`.
6. Setiap permintaan API membawa `Authorization: Bearer`. Respons 401 → frontend menghapus sesi dan ke `/masuk`. Akun yang dinonaktifkan admin kehilangan semua token (langsung 401).
7. Logout: `POST /logout` menghapus token yang sedang dipakai saja.

### 3.3 Membuat dan mengedit materi

1. Anggota (bukan admin) membuka `/materi-saya/baru` dan mengisi judul, ringkasan (opsional), sampul (opsional), kategori (minimal satu), isi Quill, catatan perubahan (opsional).
2. Gambar di editor diunggah langsung: `POST /unggah-gambar` (`gambar` JPG/PNG ≤ 2 MB → disk `public/gambar-materi`), URL disisipkan ke isi (bukan base64).
3. Sampul: `POST /unggah-sampul` (JPG/PNG ≤ 2 MB → `public/sampul-materi`) mengembalikan `path`; admin ditolak 403.
4. `POST /materi` (`SimpanMateriRequest`; admin ditolak sebelum validasi): judul wajib maks 255, kategori array minimal 1 id yang ada, isi tidak boleh kosong, sampul harus path dari unggahan. HTML isi disanitasi (`Purifier`, profil `materi`).
5. Dalam satu transaksi: `materi` (`status = draf`, slug unik dari judul) + `revisi_materi` v1 (`status = draf`) + pivot `kategori_revisi` dan `kategori_materi`. Materi **langsung terlihat semua anggota** dan forumnya terbuka.
6. Mengubah: `GET /materi/{id}/edit` → `PUT /materi/{id}`. Syarat: penulis, bukan admin, revisi terakhir berstatus `draf` (selain itu 422 "Materi ini belum bisa diubah karena lagi dicek admin."). Judul, ringkasan, sampul, kategori, isi revisi `draf` ditimpa.
   - Bila materi **tidak** `terbit`, kolom data publik di `materi` (judul, slug, ringkasan, sampul, kategori) disamakan dengan revisi (`samakanDenganRevisi`).
   - Bila materi `terbit`, data publik tidak berubah (hanya `updated_at`).
   - Sampul lama dihapus dari disk hanya bila tidak dipakai versi lain atau data publik.
7. Versi baru: `POST /materi/{id}/revisi` (tombol "Ubah" saat revisi terakhir bukan `draf`). Ditolak 422 bila masih ada revisi `draf`/`diajukan`. Membuat revisi `nomor_versi + 1` berstatus `draf`, menyalin judul, ringkasan, sampul, isi, kategori dari revisi terakhir. Bila `materi.status = ditolak` → menjadi `draf`.
8. Hapus: `DELETE /materi/{id}` hanya bila `materi.status = draf` dan `diterbitkan_pada` kosong (belum pernah tayang); selain itu 422. Revisi, komentar, pivot ikut terhapus (cascade).
9. Membaca: `GET /materi/{slug}` menampilkan revisi terakhir; setiap pembukaan menambah `materi.jumlah_dibaca` (tanpa mengubah `updated_at`). Riwayat versi `GET /materi/{id}/riwayat`; isi versi lama `GET /materi/{id}/riwayat/{revisi}` (404 bila revisi bukan milik materi).

### 3.4 Diskusi materi

1. Di `/materi/[slug]`, tombol "n komentar" membuka laci Diskusi. Forum berlaku untuk materi **status apa pun**.
2. `GET /materi/{id}/komentar` mengembalikan komentar induk saja (urut terlama, 10 per halaman) + `jumlah_balasan`. Balasan diambil saat tombol "Lihat n balasan" ditekan: `GET /komentar/{id}/balasan` (404 bila komentar bukan induk).
3. Kirim: `POST /materi/{id}/komentar` multipart `{ isi?, gambar?, parent_id? }`:
   - `isi` wajib bila tidak ada `gambar`; HTML disanitasi; isi yang hanya `<p><br></p>` tanpa gambar → 422;
   - `gambar` JPG/PNG ≤ 2 MB → disk `public/gambar-komentar`;
   - `parent_id` harus komentar pada materi yang sama (selain itu 422); balasan atas balasan diratakan ke induk yang sama (**satu tingkat**).
4. Setelah tersimpan: event `KomentarDibuat` disiarkan lewat Reverb di kanal privat `materi.{id}` (kecuali ke pengirim); notifikasi `KomentarBaru` dikirim (lihat 3.9).
5. Hapus: `DELETE /komentar/{id}` hanya pemilik (403 bila bukan); balasannya ikut terhapus (FK cascade); event `KomentarDihapus` disiarkan.
6. Admin boleh membaca dan berkomentar; tidak dapat menghapus komentar orang lain.

### 3.5 Pengajuan tayang, pembatalan, dan keputusan admin

1. Penulis menekan "Ajukan supaya tayang" → `POST /materi/{id}/ajukan`:
   - `materi.status = terbit` → 422 "Materi ini masih tayang. Turunkan dulu dari halaman umum…";
   - revisi terakhir bukan `draf` → 422;
   - sukses (transaksi): revisi `draf → diajukan`, materi `→ diajukan`; notifikasi `MateriDiajukan` ke **semua admin aktif**.
2. Selama `diajukan`, `PUT /materi/{id}` ditolak (editor hanya-baca). Materi tetap dibaca/didiskusikan anggota.
3. Batal: `POST /materi/{id}/batal-ajukan` (penulis) → revisi `diajukan → draf`; bila materi `diajukan` → `draf`. Tidak ada pengajuan → 422.
4. Admin melihat antrean `GET /admin/verifikasi-materi` (revisi `diajukan`, terlama di atas) dan tinjauan `GET /admin/revisi/{id}` (revisi + versi tayang terakhir untuk perbandingan dua kolom).
5. Keputusan `POST /admin/revisi/{id}/verifikasi { keputusan, catatan? }` (catatan wajib bila `ditolak`); revisi bukan `diajukan` → 422. Dalam transaksi:
   - baris `verifikasi_materi` dibuat (admin, keputusan, catatan);
   - **disetujui** → revisi `disetujui`; materi `terbit`, `diterbitkan_pada` diisi bila masih kosong; data publik materi disalin dari revisi;
   - **ditolak** → revisi `ditolak`; materi `ditolak` (bila sebelumnya tidak `terbit`).
6. Penulis menerima notifikasi `RevisiDiverifikasi` (keputusan + catatan). Setelah ditolak, penulis menekan "Ubah" (versi baru) untuk memperbaiki dan mengajukan lagi.
7. Halaman publik `/publikasi/[slug]` → `GET /publik/materi/{slug}`: hanya materi `terbit` (selain itu 404), isi = **versi tayang** (revisi `disetujui` dengan nomor terbesar), meskipun ada revisi lebih baru.

### 3.6 Menurunkan materi dari halaman umum

1. Penulis materi `terbit` menekan "Turunkan dari halaman umum" → `POST /materi/{id}/tarik { alasan }` (alasan wajib, maks 1.000):
   - materi bukan `terbit` → 422; sudah ada permintaan `diajukan` → 422;
   - sukses → baris `penarikan_materi` `status = diajukan`; notifikasi `PenarikanDiajukan` ke semua admin aktif. Materi **tetap tayang**.
2. Batal: `POST /materi/{id}/batal-tarik` → `status = dibatalkan`; tidak ada yang menunggu → 422.
3. Admin: `GET /admin/penarikan-materi` (terlama di atas) → `POST /admin/penarikan/{id}/verifikasi { keputusan, catatan? }` (catatan wajib bila `ditolak`; bukan `diajukan` → 422):
   - **disetujui** → penarikan `disetujui` + admin + `diputuskan_pada`; materi `terbit → draf`; data publik disamakan dengan revisi terakhir. `diterbitkan_pada` dan revisi `disetujui` lama tetap ada. Halaman publik materi → 404;
   - **ditolak** → penarikan `ditolak`; materi tetap `terbit`.
4. Penulis menerima notifikasi `PenarikanDiputuskan`. Setelah turun, penulis dapat mengajukan lagi (3.5).

### 3.7 Struktur organisasi

1. **Pengunjung** (`/`): `GET /publik/struktur` → struktur periode aktif saja (`data = null` bila belum ada periode). Pengurus inti (tanpa divisi) di atas, lalu per divisi, urut `jabatan.urutan`. Hanya nama, jabatan, divisi.
2. **Anggota/admin** (`/struktur`): `GET /periode` (aktif di atas) → pilih periode → `GET /periode/{id}/struktur`; tanpa token → 401.
3. **Admin, tab Pengurus**: `GET /admin/kepengurusan` (pengurus periode aktif + data periode). Tidak ada pemilih periode.
4. Tambah: `POST /admin/kepengurusan { anggota_id, jabatan_id, divisi_id? }` (anggota harus akun aktif; jabatan wajib). Dalam transaksi:
   - `PeriodeKepengurusan::saatIni()` (dengan `lockForUpdate`): bila tidak ada periode aktif atau periode aktif dari tahun sebelumnya → dibuat periode tahun berjalan (nama = tahun, mis. "2027"), periode lama dinonaktifkan, seluruh baris kepengurusan lama **disalin**, dan dicatat `riwayat_struktur` aksi `periode_baru`;
   - anggota sudah punya jabatan di periode itu → 422 "Anggota ini sudah memiliki jabatan pada periode ini." (seluruh transaksi batal, termasuk periode baru);
   - baris kepengurusan dibuat; dicatat `riwayat_struktur` aksi `tambah` dengan kalimat dan potret `{nama, jabatan, divisi}`.
5. Ubah/hapus: `PUT|DELETE /admin/kepengurusan/{id}`. Baris milik periode tidak aktif → 422 "Susunan pengurus periode lama tidak dapat diubah.". Bila tahun sudah berganti, perubahan diterapkan pada **salinan** di periode baru. Ubah tanpa perbedaan tidak dicatat; ubah/hapus lainnya dicatat aksi `ubah`/`hapus`.
6. Divisi & jabatan: CRUD; tidak dapat dihapus bila masih dipakai `kepengurusan` periode mana pun (422). Periode **tidak** dapat dibuat/dihapus manual (rute tidak ada).
7. **Riwayat** (`/struktur` bagian bawah dan tab Riwayat admin): `GET /periode/{id}/riwayat` → perbandingan dengan periode sebelumnya (masuk, keluar, pindah jabatan/divisi; dicocokkan per `anggota_id`; `null` untuk periode pertama) + log terbaru di atas dengan nama admin.

### 3.8 Program kegiatan

1. Admin menambah program `POST /admin/program` (multipart): nama wajib unik maks 150 (slug otomatis), ringkasan maks 300, jadwal maks 150, status `aktif|selesai`, deskripsi Quill wajib (disanitasi; kosong → 422), sampul JPG/PNG ≤ 2 MB opsional, `pic[]` minimal 1 anggota berakun aktif (selain itu 422). Program + pivot `pic_program` dalam satu transaksi.
2. Ubah `POST /admin/program/{id}` (dengan `hapus_sampul`); slug hanya berubah bila nama berubah; sampul lama dihapus dari disk.
3. Dokumentasi: `POST /admin/program/{id}/dokumentasi`, **satu butir per permintaan**: `foto` (JPG/PNG ≤ 2 MB), `video` (MP4 ≤ 50 MB), atau `youtube` (tautan watch/youtu.be/shorts/embed/live; ID 11 karakter disimpan). Frontend admin boleh memilih banyak berkas lalu mengirimnya berurutan.
4. Hapus dokumentasi `DELETE /admin/dokumentasi/{id}` → berkas ikut dihapus. Hapus program → PIC, dokumentasi, sampul, dan berkasnya terhapus.
5. Publik: `GET /publik/program` (aktif di atas lalu nama, hanya nama PIC + jumlah foto/video), `GET /publik/program/{slug}` (+ deskripsi; tidak ada → 404), `GET /publik/program/{slug}/dokumentasi?jenis=foto|video&page=` (terbaru di atas, 12 per halaman; `video` = MP4 + YouTube).

### 3.9 Notifikasi

| Notifikasi | Pemicu | Penerima |
|---|---|---|
| `MateriDiajukan` | Penulis mengajukan tayang | Semua admin aktif |
| `RevisiDiverifikasi` | Admin menyetujui/menolak pengajuan | Penulis |
| `PenarikanDiajukan` | Penulis minta materi diturunkan | Semua admin aktif |
| `PenarikanDiputuskan` | Admin memutuskan permintaan turun | Penulis |
| `KomentarBaru` (`jenis = balasan`) | Komentar seseorang dibalas | Pemilik komentar yang dibalas (bukan pengirim) |
| `KomentarBaru` (`jenis = komentar`) | Materi dikomentari | Penulis materi (bukan pengirim, dan tidak bila sudah menerima jenis `balasan` untuk komentar yang sama) |

Disimpan di tabel `notifications` (kanal `database`, dikirim sinkron). Dibaca di `/notifikasi` (`GET /notifikasi`, tandai satu/semua dibaca). Jumlah belum dibaca dari `GET /dasbor`. Tidak dikirim real-time **[BELUM ADA]**.

### 3.10 Fitur lain di kode

1. **Data anggota (admin)**: `GET /admin/anggota?q=&status=` (cari nama/email, `ilike`), ubah nama/HP/alamat/peran (`PUT`; admin tidak dapat menurunkan perannya sendiri → 422), aktif/nonaktif (`PATCH .../status`; tidak untuk diri sendiri; nonaktif menghapus semua token), atur ulang kata sandi (`PATCH .../password`; semua token dihapus).
2. **Profil sendiri**: `POST /profil` (nama wajib; HP & alamat wajib bila punya data keanggotaan; foto JPG/PNG ≤ 2 MB / `hapus_foto`); email & peran tidak dapat diubah. `PUT /profil/password` wajib kata sandi lama yang benar; baru min 8, dikonfirmasi, berbeda dari lama; token perangkat lain dicabut, token saat ini tetap.
3. **Kategori**: `GET /kategori` publik; admin CRUD (nama unik, slug otomatis); tidak dapat dihapus bila dipakai `kategori_materi` atau `kategori_revisi` (422).
4. **Konten beranda (admin)**: `POST /admin/beranda` → cerita (judul ≤ 150, isi Quill disanitasi), visi (≤ 1.000), misi (≤ 3.000, satu poin per baris), kontak (alamat, WhatsApp dinormalkan ke awalan 62, email, Instagram tanpa "@"). Tabel berisi satu baris; endpoint baca tidak pernah membuat baris.
5. **Statistik admin**: `GET /admin/statistik` → 5 materi `jumlah_dibaca` terbesar (> 0) dan 5 anggota aktif dengan jumlah materi baru + komentar 30 hari terakhir terbesar (> 0).
6. **Dasbor admin**: `GET /admin/dasbor` → pendaftar menunggu, revisi diajukan, penarikan diajukan, anggota aktif, materi terbit.
7. **Pesan galat**: pesan bawaan Laravel berbahasa Inggris (401/403/404/413/429/500) diganti kalimat Indonesia di `bootstrap/app.php`; validasi bawaan memakai `lang/id/validation.php` (`APP_LOCALE=id`).

---

## 4. Status dan transisinya

### 4.1 Status akun (`users.status`)

```
(register) ─▶ menunggu ──setujui──▶ aktif ◀──aktifkan── nonaktif
                 │                    │                    ▲
                 └──tolak──▶ ditolak  └────nonaktifkan─────┘
admin dapat juga mengaktifkan akun "ditolak" lewat PATCH /admin/anggota/{user}/status
```
Hanya `aktif` yang dapat masuk dan memakai rute ber-middleware `aktif`.

### 4.2 Status materi (`materi.status`) — status **publikasi**, bukan hak baca anggota

| Dari | Kejadian | Ke |
|---|---|---|
| — | buat materi | `draf` |
| `draf` | ajukan | `diajukan` |
| `diajukan` | penulis batal ajukan | `draf` |
| `diajukan` | admin setujui | `terbit` |
| `diajukan` | admin tolak | `ditolak` |
| `ditolak` | penulis buat versi baru ("Ubah") | `draf` |
| `terbit` | admin setujui permintaan turun | `draf` (`diterbitkan_pada` tetap terisi) |
| `terbit` | buat versi baru / ubah draf | tetap `terbit` |

### 4.3 Status versi (`revisi_materi.status`)

`draf → diajukan → disetujui | ditolak`; `diajukan → draf` (batal ajukan). Versi `disetujui` dan `ditolak` tidak diubah lagi. Satu materi hanya boleh punya satu versi `draf`/`diajukan` pada satu waktu (ditegakkan di `buatRevisi`). Versi tayang = versi `disetujui` dengan `nomor_versi` terbesar (`Materi::versiTerbit`); versi terakhir = `nomor_versi` terbesar (`Materi::revisiTerakhir`).

### 4.4 Status permintaan turun (`penarikan_materi.status`)

`diajukan → disetujui | ditolak | dibatalkan` (dibatalkan oleh penulis). Hanya satu `diajukan` per materi.

### 4.5 Lainnya

| Kolom | Nilai | Transisi |
|---|---|---|
| `verifikasi_materi.keputusan` | `disetujui`, `ditolak` | sekali dibuat per keputusan |
| `periode_kepengurusan.is_aktif` | boolean | hanya satu `true`; berganti otomatis lewat `saatIni()` |
| `riwayat_struktur.aksi` | `tambah`, `ubah`, `hapus`, `periode_baru` | log, tidak berubah |
| `program.status` | `aktif`, `selesai` | diubah admin bebas |
| `dokumentasi_program.jenis` | `foto`, `video`, `youtube` | tetap |
| `notifications.read_at` | null / waktu | null → waktu saat ditandai dibaca |

---

## 5. Struktur basis data

Diambil dari PostgreSQL setelah semua migrasi. Semua tabel aplikasi memakai `id bigint` (BIGSERIAL, PK) dan `created_at`/`updated_at` (timestamp, nullable). Kolom bertanda `?` boleh null.

### 5.1 Tabel aplikasi

| Tabel | Kolom penting (tipe) | Kunci & unik |
|---|---|---|
| `users` | nama varchar(255), email varchar(255), password varchar(255), role varchar (def `anggota`), status varchar (def `menunggu`), remember_token? | UNIQUE email |
| `anggota` | user_id bigint, no_hp varchar(20), alamat text, alasan_bergabung text, foto? varchar, tanggal_bergabung? date, catatan_verifikasi? text, diverifikasi_pada? timestamp | FK user_id → users (CASCADE); UNIQUE user_id |
| `periode_kepengurusan` | nama_periode varchar, tahun_mulai smallint, tahun_selesai smallint, is_aktif boolean (def false) | — |
| `divisi` | nama_divisi varchar, deskripsi? text | UNIQUE nama_divisi |
| `jabatan` | nama_jabatan varchar, urutan smallint (def 0) | UNIQUE nama_jabatan |
| `kepengurusan` | anggota_id, periode_id, divisi_id?, jabatan_id (bigint) | FK ke anggota, periode_kepengurusan, divisi, jabatan (semua RESTRICT); UNIQUE (anggota_id, periode_id) |
| `riwayat_struktur` | periode_id bigint, admin_id? bigint, aksi varchar, keterangan text, data_sebelum? jsonb, data_sesudah? jsonb | FK periode_id → periode_kepengurusan (CASCADE); admin_id → users (SET NULL) |
| `materi` | penulis_id bigint, judul varchar, slug varchar, ringkasan? text, sampul? varchar, status varchar (def `draf`), diterbitkan_pada? timestamp, jumlah_dibaca integer (def 0) | FK penulis_id → users (RESTRICT); UNIQUE slug |
| `revisi_materi` | materi_id bigint, nomor_versi integer, judul varchar, ringkasan? text, sampul? varchar, konten jsonb (Delta Quill), konten_html text, catatan_revisi? text, status varchar (def `draf`) | FK materi_id → materi (CASCADE); UNIQUE (materi_id, nomor_versi) |
| `verifikasi_materi` | revisi_id bigint, admin_id bigint, keputusan varchar, catatan? text | FK revisi_id → revisi_materi (CASCADE); admin_id → users (RESTRICT) |
| `penarikan_materi` | materi_id bigint, alasan text, status varchar (def `diajukan`), admin_id? bigint, catatan? text, diputuskan_pada? timestamp | FK materi_id → materi (CASCADE); admin_id → users (RESTRICT) |
| `kategori` | nama varchar, slug varchar | UNIQUE nama; UNIQUE slug |
| `kategori_materi` | materi_id, kategori_id | FK keduanya CASCADE; UNIQUE (materi_id, kategori_id) |
| `kategori_revisi` | revisi_id, kategori_id | FK keduanya CASCADE; UNIQUE (revisi_id, kategori_id) |
| `komentar` | materi_id bigint, user_id bigint, parent_id? bigint, isi? text (HTML), gambar? varchar | FK materi_id → materi (CASCADE); user_id → users (RESTRICT); parent_id → komentar (CASCADE) |
| `pengaturan_beranda` | cerita_judul? varchar, cerita_isi? text, visi? text, misi? text, kontak_alamat? varchar(255), kontak_whatsapp? varchar(20), kontak_email? varchar(255), kontak_instagram? varchar(50) | — (dipakai satu baris) |
| `program` | nama varchar(150), slug varchar, ringkasan? varchar(300), jadwal? varchar(150), sampul? varchar, deskripsi text, status varchar (def `aktif`) | UNIQUE nama; UNIQUE slug |
| `pic_program` | program_id, anggota_id | FK program (CASCADE), anggota (CASCADE); UNIQUE (program_id, anggota_id) |
| `dokumentasi_program` | program_id bigint, jenis varchar, path? varchar, youtube_id? varchar(20), keterangan? varchar(255) | FK program_id → program (CASCADE) |
| `notifications` | id uuid (PK), type, notifiable_type, notifiable_id, data text (JSON), read_at? | bawaan Laravel (polimorfik ke users) |
| `personal_access_tokens` | tokenable_type, tokenable_id, name, token varchar(64), abilities?, last_used_at?, expires_at? | bawaan Sanctum; UNIQUE token |

Indeks GIN full-text untuk pencarian materi dibuat lewat `DB::statement` di migrasi materi/revisi. [TIDAK JELAS] daftar indeks GIN tidak diambil dalam penyusunan ini; hanya indeks UNIQUE yang dicantumkan di atas.

Tabel bawaan lain yang ada tetapi tidak dipakai fitur aplikasi: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`.

### 5.2 Relasi

| Relasi | Jenis |
|---|---|
| users — anggota | 1–1 |
| users (penulis) — materi | 1–N |
| users — komentar | 1–N |
| users (admin) — verifikasi_materi / penarikan_materi / riwayat_struktur | 1–N |
| anggota — kepengurusan | 1–N |
| periode_kepengurusan — kepengurusan | 1–N |
| periode_kepengurusan — riwayat_struktur | 1–N |
| divisi — kepengurusan; jabatan — kepengurusan | 1–N |
| materi — revisi_materi | 1–N |
| revisi_materi — verifikasi_materi | 1–N |
| materi — penarikan_materi | 1–N |
| materi — komentar | 1–N |
| komentar — komentar (parent_id) | 1–N (satu tingkat) |
| materi — kategori (lewat `kategori_materi`, data versi tayang) | N–N |
| revisi_materi — kategori (lewat `kategori_revisi`, per versi) | N–N |
| program — anggota (lewat `pic_program`) | N–N |
| program — dokumentasi_program | 1–N |
| users — notifications | 1–N (polimorfik) |

---

## 6. Halaman frontend

| Route | Layout | Middleware | Fungsi |
|---|---|---|---|
| `/` | publik | — | Beranda: sambutan, 3 artikel terbaru, 3 program, cerita (diringkas + "Baca selengkapnya"), bagan struktur periode aktif, cara bergabung |
| `/tentang` | publik | — | Cerita lengkap, visi, misi |
| `/publikasi` | publik | — | Grid artikel tayang (sampul atau sampul bawaan), cari, saring kategori, paginasi |
| `/publikasi/[slug]` | publik | — | Baca versi tayang; Bagikan, Dengarkan, A− / A+ |
| `/program`, `/program/[slug]` | publik | — | Daftar program; detail (tab Tentang program & Dokumentasi), Bagikan |
| `/daftar`, `/daftar/berhasil` | publik | guest | Formulir pendaftaran; konfirmasi terkirim |
| `/masuk` | publik | guest | Login + pesan per status akun |
| `/dasbor` | anggota | auth, bukan-admin | Ringkasan materi per status, materi terbaru, notifikasi |
| `/materi` | anggota* | auth | Arsip seluruh materi, cari, tab status, saring kategori |
| `/materi/[slug]` | anggota* | auth | Baca versi terakhir, riwayat versi, versi tayang, forum (laci), Dengarkan, A− / A+ |
| `/materi-saya` | anggota | auth, bukan-admin | Materi milik sendiri per status + aksi (Ubah, Ajukan, Batal, Turunkan, Hapus) |
| `/materi-saya/baru`, `/materi-saya/[id]/edit` | anggota | auth, bukan-admin | Editor Quill; editor hanya-baca saat dicek/sudah tayang; dialog perbandingan versi saat mengajukan ulang |
| `/struktur` | anggota* | auth | Bagan semua periode + riwayat perubahan |
| `/notifikasi` | anggota* | auth | Daftar notifikasi, tandai dibaca |
| `/profil` | anggota* | auth | Ubah data diri & kata sandi |
| `/admin` | admin | auth, admin | Dasbor admin + statistik |
| `/admin/pendaftaran`, `/admin/pendaftaran/[id]` | admin | auth, admin | Antrean & keputusan pendaftaran |
| `/admin/anggota`, `/admin/anggota/[id]` | admin | auth, admin | Data anggota |
| `/admin/struktur` | admin | auth, admin | Tab Pengurus, Divisi, Jabatan, Riwayat |
| `/admin/verifikasi-materi`, `/admin/verifikasi-materi/[revisiId]` | admin | auth, admin | Tab Minta tayang / Minta diturunkan; tinjau & putuskan |
| `/admin/kategori` | admin | auth, admin | Kategori materi |
| `/admin/beranda` | admin | auth, admin | Cerita, visi, misi, kontak |
| `/admin/program`, `/admin/program/baru`, `/admin/program/[id]` | admin | auth, admin | Program, PIC, dokumentasi |

\* Halaman ber-layout `anggota` yang dibuka admin ditampilkan dalam layout `admin`.

Layout: `publik` (header: Beranda · Tentang kami · Artikel · Program · Cara bergabung · Masuk/Daftar; kaki halaman berisi kontak), `anggota` (navbar Arsip · Materi saya · Struktur + Tulis, lonceng, menu akun; toolbar bawah di ponsel), `admin` (sidebar kiri; grup Ringkasan, Keanggotaan, Materi, Kegiatan, Tampilan, Akun). Logo `assets/logo.png` di header ketiga layout.

---

## 7. Perbedaan dengan PRD

Pembanding: (a) PRD **awal** di commit pertama repositori (Draf 2.1, 9 November 2026, KF-01–KF-16) dan (b) PRD **terkini** di `docs/PRD.md` (Draf 2.10).

### 7.1 Terhadap PRD terkini (Draf 2.10)

Pemeriksaan otomatis: seluruh endpoint di PRD §11 ada di `routes/api.php` dan sebaliknya; seluruh route di PRD §12 ada sebagai halaman Nuxt dan sebaliknya.

Yang ada di kode tetapi **tidak** tercantum di PRD:

| Hal | Lokasi |
|---|---|
| Tombol "Dengarkan" (text-to-speech Web Speech API) | `components/materi/PemutarSuara.vue`, `composables/useBacaArtikel.ts` |
| Sampul bawaan untuk artikel tanpa gambar | `components/publik/SampulBawaan.vue` |
| Logo gambar menggantikan wordmark teks | `assets/logo.png`, ketiga layout |
| Pesan bawaan Laravel diterjemahkan, `APP_LOCALE=id`, `lang/id` | `bootstrap/app.php`, `backend/lang/` (dicatat di CLAUDE.md, bukan PRD) |

Yang tercantum di PRD tetapi tidak/sebagian di kode:

| Hal | Keterangan |
|---|---|
| Notifikasi real-time per pengguna | PRD hanya menyebut notifikasi dalam aplikasi; kanal `App.Models.User.{id}` ada tetapi tidak dipakai **[BELUM ADA]** |
| Koleksi Postman & daftar skenario Black Box (T12) | **[BELUM ADA]** — tidak ada berkas Postman maupun dokumen skenario Black Box di repositori |

### 7.2 Terhadap PRD awal (Draf 2.1)

**Ditambah**

| Fitur | KF di PRD terkini |
|---|---|
| Materi publik untuk pengunjung (semula "tidak termasuk") | KF-17 |
| Konten beranda yang diatur admin, sampul artikel | KF-18 |
| Kategori materi | KF-19 |
| Penarikan (penurunan) materi dari publik; semua bagian materi disimpan per versi; perbandingan versi | KF-20 |
| Program kegiatan + PIC + dokumentasi foto/video/YouTube (semula "kegiatan/agenda" tidak termasuk) | KF-21 |
| Artikel & program terbaru di beranda, Bagikan, kontak, A− / A+ | KF-22 |
| Notifikasi komentar | KF-23 |
| Profil & ganti kata sandi sendiri | KF-24 |
| Statistik admin (jumlah dibaca, anggota teraktif) | KF-25 |
| Halaman Tentang kami (visi, misi), cerita diringkas di beranda | KF-26 |
| Riwayat perubahan struktur (log + perbandingan antarperiode) | KP-07.7–07.8 |
| Isi versi lama dapat dibaca; balasan komentar dimuat saat dibuka | KP-13.4, KP-14.5 |

**Diubah**

| Rencana awal | Kode sekarang |
|---|---|
| Materi draf hanya milik penulis; forum hanya pada materi `terbit` (KP-14.1 awal) | Materi langsung dibaca & didiskusikan semua anggota sejak draf; `materi.status` hanya berarti status publikasi |
| Periode dibuat/diaktifkan manual (CRUD periode, KP-07.1 awal) | Periode otomatis per tahun kalender saat perubahan pertama; periode lama hanya-baca; rute `/admin/periode` tidak ada |
| Pengguna (termasuk tamu) dapat memilih periode lain (KP-01.3 awal) | Tamu hanya melihat periode aktif; daftar periode & periode lama butuh login |
| Admin = anggota + hak admin | Admin tidak dapat menulis/mengelola materi (KP-08.6); halaman anggota tampil di layout admin |
| Menu publik berisi Struktur | Diganti Program dan Tentang kami; bagan tetap di beranda |
| Teks antarmuka formal ("Anda", istilah draf/terbit/revisi) | Santai untuk anggota & pengunjung, formal untuk admin, tanpa istilah teknis (Panduan Bahasa UI) |
| Ukuran dasar UI 16 px | 15 px (isi artikel tetap 20 px) |

**Dihilangkan**

| Hal | Keterangan |
|---|---|
| CRUD dan aktivasi periode manual | Diganti periode otomatis |
| Poster beranda (sempat ditambahkan di KF-18) | Kolom `pengaturan_beranda.poster` dihapus (Draf 2.9) |
| Pemilih periode di beranda publik | Dihapus (KP-01.2 Draf 2.5) |
