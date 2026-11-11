# Halaman Sistem Informasi Al-Futuuhat

| | |
|---|---|
| **Dasar penyusunan** | Kode frontend di `frontend/app/pages`, `layouts`, `middleware`, dan `components`, per 11 November 2026 |
| **Pendamping** | `docs/as-built-alur-sistem.md` (alur, status, basis data) |
| **Penanda** | **[BELUM ADA]** = tidak diimplementasikan; **[TIDAK JELAS]** = tidak dapat dipastikan dari kode |

Setiap halaman dijelaskan dengan pola yang sama:
- **Akses:** siapa yang boleh membuka halaman, berdasarkan layout dan middleware.
- **Fungsi:** untuk apa halaman itu.
- **Isi:** bagian-bagian yang tampil.
- **Aksi:** apa yang bisa dilakukan pengguna.
- **Data:** endpoint API yang dipanggil.
- **Keadaan:** tampilan saat memuat, kosong, atau galat.

---

## 1. Kerangka halaman (layout)

### 1.1 Layout `publik` — pengunjung
- **Header:** logo Al-Futuuhat; menu **Beranda · Tentang kami · Artikel · Program · Cara bergabung**; di kanan **Masuk** dan tombol **Daftar** (bila belum masuk) atau **Dasbor** (bila sudah masuk; admin diarahkan ke `/admin`). Di ponsel, menu dilipat menjadi laci.
- **Kaki halaman:** kontak organisasi (alamat, WhatsApp, email, Instagram) yang diatur admin; kolom kosong tidak ditampilkan. Di bawahnya hak cipta.

### 1.2 Layout `anggota` — anggota yang sudah masuk
- **Tablet/desktop:** header berisi logo, menu **Arsip · Materi saya · Struktur**, tombol **Tulis**, lonceng notifikasi dengan angka belum dibaca, dan avatar.
- **Menu avatar:** nama & peran, Dasbor, **Profil**, Notifikasi, Panel admin (khusus admin), Keluar.
- **Ponsel:** header hanya logo + avatar; navigasi pindah ke **toolbar bawah**: Dasbor · Arsip · **Tulis** (tombol bulat di tengah) · Materi saya · Struktur.

### 1.3 Layout `admin` — admin
- **Sidebar kiri:**
  - **Ringkasan:** Dasbor
  - **Keanggotaan:** Pendaftaran (angka menunggu), Data anggota, Struktur
  - **Materi:** Pemeriksaan materi (angka antrean), Arsip materi, Kategori
  - **Kegiatan:** Program
  - **Tampilan:** Konten beranda
  - **Akun:** Profil, Notifikasi (angka belum dibaca), Keluar
- Di bawah lebar `lg` sidebar menjadi laci yang dibuka lewat tombol menu.
- Halaman ber-layout `anggota` yang dibuka admin (Arsip, baca materi, Struktur, Notifikasi, Profil) otomatis ditampilkan dalam layout ini.

### 1.4 Middleware halaman

| Middleware | Perilaku |
|---|---|
| `auth` | Wajib masuk; tanpa token → `/masuk`. Mengganti layout `anggota` menjadi `admin` bila penggunanya admin. |
| `guest` | Hanya untuk yang belum masuk; yang sudah masuk diarahkan ke `/admin` (admin) atau `/dasbor` (anggota). |
| `admin` | Hanya admin; anggota diarahkan ke `/dasbor`. |
| `bukan-admin` | Halaman khusus penulis; admin diarahkan ke `/admin`. |

---

## 2. Halaman publik (pengunjung)

### 2.1 Beranda — `/`
- **Akses:** semua orang, tanpa login.
- **Fungsi:** pintu masuk; mengenalkan organisasi dan isi terbarunya.
- **Isi (berurutan):**
  1. **Hero:** "Tumbuh bersama, belajar bersama.", dek singkat, tombol **Baca artikel**, tautan **Daftar sebagai anggota** (atau **atau buka dasbor** bila sudah masuk).
  2. **Artikel terbaru:** 3 kartu artikel + "Lihat semua". Disembunyikan bila belum ada artikel tayang.
  3. **Program kami:** 3 kartu program (aktif lebih dulu) + "Lihat semua". Disembunyikan bila kosong.
  4. **Cerita Al-Futuuhat:** judul dan isi cerita dari admin; bila panjang, dipotong dengan efek pudar dan tombol **Baca selengkapnya** menuju `/tentang`. Bila admin belum mengisi, tampil teks bawaan.
  5. **Struktur kepengurusan:** bagan periode yang sedang aktif (pengurus inti di puncak, divisi dapat dibuka-tutup); tanpa pemilih periode.
  6. **Cara bergabung:** tiga langkah (isi formulir → tunggu dicek pengurus → mulai belajar) dan tombol daftar.
- **Data:** `GET /publik/beranda`, `GET /publik/materi`, `GET /publik/program`, `GET /publik/struktur`.
- **Keadaan:** kerangka memuat untuk cerita; bagian artikel/program yang gagal dimuat cukup disembunyikan. Beranda tidak pernah kosong.

### 2.2 Tentang kami — `/tentang`
- **Akses:** semua orang.
- **Fungsi:** profil organisasi lengkap.
- **Isi:** cerita Al-Futuuhat lengkap (tanpa dipotong), **Visi** (teks besar), **Misi** (daftar bernomor, satu poin per baris dari admin). Visi/misi yang belum diisi tidak ditampilkan.
- **Data:** `GET /publik/beranda`.
- **Keadaan:** kerangka memuat; bila gagal, tetap tampil dengan cerita bawaan.

### 2.3 Artikel — `/publikasi`
- **Akses:** semua orang.
- **Fungsi:** daftar materi yang sudah tayang untuk umum.
- **Isi:** judul "Artikel" + dek; **grid kartu artikel** (sampul atau sampul bawaan berisi huruf awal judul, kategori, logo; nama penulis; judul; ringkasan; tanggal tayang; perkiraan menit baca); paginasi. Sidebar: kotak cari, penyaring kategori, "Tentang artikel", ajakan "Ingin ikut menulis?". Di ponsel, cari dan kategori pindah ke atas daftar.
- **Aksi:** cari (real-time), saring kategori, **Hapus filter**, buka artikel, pil kategori pada kartu.
- **Data:** `GET /publik/materi?q=&kategori=&page=`, `GET /kategori`. Penyaring disimpan di URL (`?q=`, `?kategori=`).
- **Keadaan:** kerangka kartu; kosong → "Tulisannya tidak ketemu."; galat → "Tulisan belum bisa dimuat".

### 2.4 Baca artikel — `/publikasi/[slug]`
- **Akses:** semua orang; hanya materi berstatus tayang (selain itu 404).
- **Fungsi:** membaca versi tayang sebuah artikel.
- **Isi:** tautan "Semua artikel"; judul, ringkasan, pil kategori, nama penulis, tanggal & menit baca; bilah alat: **Bagikan** (kiri), **Dengarkan** dan **A− / A+** (kanan); isi artikel; penutup "Ditulis oleh … · Ditinjau dan disetujui pengurus Al-Futuuhat"; ajakan bergabung (pengganti forum).
- **Aksi:**
  - **Bagikan:** lembar bagikan perangkat; bila tidak tersedia, menu **Salin tautan** / **WhatsApp**.
  - **Dengarkan:** membacakan judul lalu isi dengan suara peramban (putar, jeda/lanjutkan, berhenti, kecepatan 0,75×–1,5×); berhenti otomatis saat pindah halaman; disembunyikan bila peramban tidak mendukung.
  - **A− / A+:** ukuran tulisan isi (−4 s.d. +8 px), diingat peramban.
- **Data:** `GET /publik/materi/{slug}` (menambah jumlah dibaca).
- **Keadaan:** kerangka judul & paragraf; galat → "Tulisan belum bisa dibuka".
- Forum, riwayat versi, dan catatan admin **tidak** ditampilkan di halaman ini.

### 2.5 Program — `/program`
- **Akses:** semua orang.
- **Fungsi:** daftar kegiatan rutin organisasi.
- **Isi:** "Program kami" + dek; grid kartu program (sampul atau ikon kalender, lencana **Berjalan/Selesai**, nama, ringkasan, jadwal, "PIC: nama…", "n foto · n video").
- **Data:** `GET /publik/program`.
- **Keadaan:** kerangka kartu; kosong → "Belum ada program."; galat → "Program belum bisa dimuat".

### 2.6 Detail program — `/program/[slug]`
- **Akses:** semua orang.
- **Isi:** tautan "Semua program"; lencana status, nama, ringkasan, jadwal, daftar PIC (avatar inisial + nama), tombol **Bagikan**; gambar sampul; tab **Tentang program** (penjelasan rinci) dan **Dokumentasi (n)**. Tab tersimpan di `?tab=dokumentasi`.
- **Tab Dokumentasi:** penyaring **Semua · Foto · Video**; grid gambar kecil (foto, cuplikan bingkai video MP4, gambar kecil YouTube dengan tanda putar); keterangan; paginasi 12 per halaman. Klik membuka penampil: foto besar, pemutar video, atau YouTube tersemat + "Buka di YouTube".
- **Data:** `GET /publik/program/{slug}`, `GET /publik/program/{slug}/dokumentasi?jenis=&page=`.
- **Keadaan:** slug tidak ada → "Programnya tidak ketemu." + tombol "Lihat semua program".

### 2.7 Daftar anggota — `/daftar`
- **Akses:** hanya yang belum masuk (`guest`).
- **Isi:** formulir nama lengkap, email, kata sandi (min. 8), ulangi kata sandi, nomor HP, foto (opsional, JPG/PNG ≤ 2 MB, dengan pratinjau), alamat, alasan bergabung; tautan "Sudah punya akun? Masuk"; tombol **Daftar**.
- **Aksi:** kirim pendaftaran. Foto diperiksa dulu di peramban. Galat per isian tampil di bawah isian tanpa menghapus isian lain; galat umum → "Pendaftaran belum terkirim".
- **Data:** `POST /register`. Berhasil → `/daftar/berhasil`.

### 2.8 Pendaftaran terkirim — `/daftar/berhasil`
- **Akses:** `guest`.
- **Isi:** tanda centang, "Pendaftaran terkirim", penjelasan bahwa pendaftaran sedang dicek admin; tombol **Ke halaman masuk** dan **Kembali ke beranda**. Tidak ada login otomatis.

### 2.9 Masuk — `/masuk`
- **Akses:** `guest`.
- **Isi:** formulir email & kata sandi, tombol **Masuk**, tautan **Daftar**.
- **Keadaan pesan** (kotak di atas formulir sesuai jawaban server):
  - salah email/sandi → "Belum bisa masuk";
  - pendaftaran menunggu → "Pendaftaranmu lagi dicek admin";
  - ditolak → "Pendaftaranmu belum disetujui" + **Catatan dari admin**;
  - nonaktif → "Akunmu sedang tidak aktif".
- **Data:** `POST /login`. Berhasil → admin ke `/admin`, anggota ke `/dasbor`.

---

## 3. Halaman anggota

### 3.1 Dasbor — `/dasbor`
- **Akses:** anggota (`auth`, `bukan-admin`; admin diarahkan ke `/admin`).
- **Isi:** "Selamat datang, nama"; 4 kartu angka materi milik sendiri (**Belum tayang · Lagi dicek admin · Belum disetujui · Tayang untuk umum**); panel **Materi saya terbaru** (status, waktu, jumlah komentar) dengan tombol **Tulis materi baru**; panel **Notifikasi** (3 terbaru).
- **Data:** `GET /dasbor`, `GET /materi-saya`, `GET /notifikasi`.
- **Keadaan:** kerangka kartu & panel; kosong → "Kamu belum menulis materi." / "Belum ada notifikasi."

### 3.2 Arsip materi — `/materi`
- **Akses:** anggota dan admin (`auth`).
- **Fungsi:** semua materi anggota, **termasuk yang belum tayang**.
- **Isi:** tab status **Semua · Belum tayang · Lagi dicek admin · Belum disetujui · Tayang untuk umum**; daftar baris materi (avatar & nama penulis, chip status, judul, ringkasan 3 baris, chip kategori, tanggal · menit baca · jumlah komentar, sampul kecil bila ada); paginasi. Sidebar: kotak cari "Cari materi atau penulis", penyaring kategori, legenda **Arti status**.
- **Aksi:** cari judul/isi/nama penulis secara real-time, ganti tab, saring kategori, **Hapus filter**, buka materi. Penyaring tersimpan di URL.
- **Data:** `GET /materi?q=&status=&kategori=&page=`, `GET /kategori`.
- **Keadaan:** kosong → "Materinya tidak ketemu."; galat → "Materi belum bisa dimuat".

### 3.3 Baca materi + forum — `/materi/[slug]`
- **Akses:** anggota dan admin (`auth`); semua status materi.
- **Isi:**
  - tautan "Arsip materi"; kepala artikel (judul, ringkasan, chip kategori, penulis, "Diperbarui/Tayang sejak … · n menit baca", chip status);
  - **banner akses** bila belum tayang, dibedakan per keadaan: belum tayang, lagi dicek admin, belum disetujui, sudah diturunkan dari halaman umum;
  - **bilah aksi:** "n komentar" (membuka laci Diskusi), **Riwayat perubahan** (membuka laci riwayat), **Lihat versi yang sedang tayang** (bila ada perubahan yang belum tayang), catatan kanan (mis. "Lagi dicek admin untuk diturunkan");
  - **Dengarkan** dan **A− / A+**; isi materi.
- **Laci Riwayat perubahan:** daftar versi dengan nomor, status, tanggal, catatan perubahan; catatan admin hanya untuk penulis & admin; aksi **Lihat isi versi ini** mengganti isi kolom baca dengan versi lama + baris "Kamu sedang membaca versi lama (vN)" dan tombol **Kembali ke versi terbaru**.
- **Laci Diskusi (real-time):**
  - kotak tulis (teks berformat + lampiran gambar JPG/PNG ≤ 2 MB);
  - komentar induk; **Lihat n balasan** (dimuat saat dibuka);
  - **Balas**, **Hapus** (milik sendiri; dengan konfirmasi);
  - label "Penulis" pada komentar penulis materi;
  - komentar baru dari orang lain muncul tanpa memuat ulang (Reverb), dengan sorotan sesaat.
- **Data:** `GET /materi/{slug}` (menambah jumlah dibaca), `GET /materi/{id}/riwayat/{revisi}`, `GET|POST /materi/{id}/komentar`, `GET /komentar/{id}/balasan`, `DELETE /komentar/{id}`, kanal `materi.{id}`.
- **Keadaan:** galat → "Materi belum bisa dibuka".

### 3.4 Materi saya — `/materi-saya`
- **Akses:** anggota (`bukan-admin`).
- **Fungsi:** mengelola materi milik sendiri.
- **Isi:** judul + tombol **Tulis materi**; tab per status dengan jumlah; daftar baris materi dengan chip status (mis. "Perubahan belum tayang") dan catatan (mis. "Versi lama masih tayang · Lagi dicek admin untuk diturunkan"); paginasi. Sidebar: cari "Cari materi saya", **Arti status**, **Cara materi tayang untuk umum**.
- **Aksi per baris** (bergantung status):
  - **Ubah:** membuka editor; bila perlu, membuat versi baru lebih dulu.
  - **Lihat:** membuka halaman baca materi.
  - **Ajukan supaya tayang:** membuka dialog konfirmasi; materi yang pernah tayang menampilkan perbandingan dua kolom.
  - **Batalkan pengajuan**.
  - **Turunkan dari halaman umum:** membuka dialog alasan, wajib diisi, maks 1.000 karakter.
  - **Batal turunkan**.
  - Menu titik tiga → **Hapus**, hanya untuk materi yang belum pernah tayang; dengan konfirmasi.
- **Data:** `GET /materi-saya?q=&status=&page=`, `POST /materi/{id}/ajukan|batal-ajukan|revisi|tarik|batal-tarik`, `DELETE /materi/{id}`, `GET /materi/{id}/riwayat/{revisi}` (perbandingan).
- **Keadaan:** kosong per tab dengan pesan masing-masing (mis. "Kamu belum punya materi." + "Yuk, tulis materi pertamamu."); toast setelah aksi.

### 3.5 Tulis materi — `/materi-saya/baru`
- **Akses:** anggota (`bukan-admin`).
- **Isi (editor):** judul besar, ringkasan, **Sampul artikel** (pilih/ganti/hapus, diunggah langsung), **Kategori** (pil yang dapat dipilih, kotak cari bila banyak, wajib minimal satu), keterangan "Begitu disimpan, semua anggota bisa langsung membacanya", editor Quill (judul bagian, tebal, miring, tautan, daftar, kutipan, gambar). Bilah bawah: "Materi baru · Belum disimpan", tombol **Batal** dan **Simpan**.
- **Data:** `POST /unggah-gambar`, `POST /unggah-sampul`, `GET /kategori`, `POST /materi`. Berhasil → ke editor materi tersebut.
- **Keadaan:** galat per isian di bawah isian ("Judulnya belum diisi.", "Pilih minimal satu kategori ya.", "Isi materinya masih kosong.").

### 3.6 Ubah materi — `/materi-saya/[id]/edit`
- **Akses:** penulis (`bukan-admin`; backend menolak bukan penulis dengan 403).
- **Mode dapat diubah** (versi terakhir belum tayang): editor yang sama dengan 3.5 + chip status. Bila materinya **sedang tayang**, muncul banner "Materi ini sedang tayang untuk umum" yang isinya berbeda bila permintaan turun sedang dicek. Tombol: **Simpan**, **Ajukan supaya tayang** (bila tidak tayang), **Turunkan dari halaman umum** / **Batal turunkan** (bila tayang), **Hapus materi** (bila belum pernah tayang).
- **Mode hanya-baca** (versi terakhir lagi dicek, sudah tayang, atau belum disetujui): banner dengan judul & penjelasan per keadaan, judul, ringkasan, isi, catatan perubahan, tautan **Lihat halaman materi**. Bilah bawah: **Batalkan pengajuan** (bila lagi dicek), **Ubah** (membuat versi baru), **Turunkan dari halaman umum** / **Batal turunkan**, **Hapus materi**.
- **Data:** `GET /materi/{id}/edit`, `PUT /materi/{id}`, `POST /materi/{id}/ajukan|batal-ajukan|revisi|tarik|batal-tarik`, `DELETE /materi/{id}`.

### 3.7 Struktur organisasi — `/struktur`
- **Akses:** anggota dan admin (`auth`).
- **Isi:**
  - "Struktur kepengurusan" + pemilih periode (periode aktif berlabel "(saat ini)");
  - bagan (pengurus inti di puncak, satu kotak per divisi berisi jumlah orang & koordinator, anggota divisi dapat dibuka-tutup);
  - tombol **Buka semua / Tutup semua**; di ponsel bagan digeser mendatar;
  - bagian **Riwayat perubahan** untuk periode terpilih:
    - tab **Dibanding periode sebelumnya:** kartu Masuk, Keluar, Pindah jabatan;
    - tab **Log perubahan:** kalimat perubahan, nama admin, waktu.
- **Data:** `GET /periode`, `GET /periode/{id}/struktur`, `GET /periode/{id}/riwayat`. Mendukung `?periode=id` (dipakai tombol Pratinjau bagan admin).
- **Keadaan:** belum ada periode → "Struktur organisasi belum ada."; periode pertama → pesan tidak ada pembanding.

### 3.8 Notifikasi — `/notifikasi`
- **Akses:** anggota dan admin (`auth`).
- **Isi:** daftar notifikasi (titik penanda belum dibaca, ikon per jenis, kalimat dengan judul materi tebal, kotak cuplikan komentar / alasan / catatan admin, waktu relatif, tautan **Buka**, tombol **Tandai dibaca**); tombol **Tandai semua dibaca**; paginasi.
- **Jenis & tujuan tautan:**
  - pengajuan tayang dan permintaan turun → halaman pemeriksaan admin;
  - hasil keputusan → editor materi penulis;
  - komentar baru / balasan → halaman materi.
- **Data:** `GET /notifikasi`, `PATCH /notifikasi/{id}/baca`, `PATCH /notifikasi/baca-semua`.
- **Keadaan:** kosong → "Belum ada notifikasi."; galat → "Notifikasi belum bisa dimuat". Notifikasi tidak masuk secara real-time **[BELUM ADA]**; daftar diperbarui saat halaman dibuka.

### 3.9 Profil — `/profil`
- **Akses:** anggota dan admin (`auth`).
- **Isi:**
  - **Data diri:** foto profil (pilih/ganti/hapus, JPG/PNG ≤ 2 MB), nama lengkap, email (tidak dapat diubah), nomor HP, alamat; tombol **Simpan perubahan**.
  - **Ganti kata sandi:** kata sandi lama, kata sandi baru (min. 8), ulangi; tombol **Ganti kata sandi**.
  - Nomor HP, alamat, dan foto hanya muncul bila akun punya data keanggotaan.
- **Data:** `GET /profil`, `POST /profil`, `PUT /profil/password`.
- **Keadaan:** kata sandi lama salah → "Kata sandi lama belum cocok."; setelah ganti sandi, perangkat lain perlu masuk lagi.

---

## 4. Halaman admin

### 4.1 Dasbor admin — `/admin`
- **Akses:** admin.
- **Isi:**
  - 4 kartu angka: **Pendaftar menunggu**, **Menunggu pemeriksaan** (n minta tayang · n minta diturunkan), **Anggota aktif**, **Tayang untuk umum**;
  - panel **Pendaftar terbaru** (3, tombol **Periksa**) dan **Materi menunggu pemeriksaan** (2, tombol **Tinjau**);
  - panel statistik **Materi paling banyak dibaca** (5, "n kali dibaca") dan **Anggota paling aktif (30 hari)** (5, "n materi · n komentar").
- **Data:** `GET /admin/dasbor`, `GET /admin/pendaftaran`, `GET /admin/verifikasi-materi`, `GET /admin/statistik`.

### 4.2 Pemeriksaan pendaftaran — `/admin/pendaftaran`
- **Isi:** penyaring **Menunggu / Ditolak**; tabel nama (avatar), email, tanggal mendaftar, status, tombol **Periksa**; paginasi (terbaru di atas).
- **Data:** `GET /admin/pendaftaran?status=&page=`.
- **Keadaan:** kosong → "Tidak ada pendaftar menunggu." / "Tidak ada pendaftar ditolak."

### 4.3 Detail pendaftar — `/admin/pendaftaran/[id]`
- **Isi:** nama + lencana status, tanggal mendaftar; data email, nomor HP, alamat, alasan bergabung, catatan admin (bila ada); foto (atau "Tidak ada foto") + "Buka foto ukuran penuh"; bilah keputusan **Tolak** dan **Setujui pendaftaran**.
- **Aksi:** setujui (dialog konfirmasi) atau tolak (dialog dengan **catatan penolakan wajib**). Bila sudah diputuskan, tombol tidak tersedia.
- **Data:** `GET /admin/pendaftaran/{id}`, `PATCH /admin/pendaftaran/{id}`.

### 4.4 Data anggota — `/admin/anggota`
- **Isi:** penyaring status; kotak cari nama/email; tabel nama, email, peran (lencana Admin), status, tanggal bergabung, tombol **Kelola**; paginasi.
- **Data:** `GET /admin/anggota?q=&status=&page=`.
- **Keadaan:** kosong → "Anggota tidak ditemukan."

### 4.5 Kelola anggota — `/admin/anggota/[id]`
- **Isi:** nama, lencana status, tanggal bergabung.
- **Formulir ubah:** nama lengkap, email (terkunci), nomor HP, peran, alamat; tombol **Simpan perubahan**.
- **Kartu aksi:** **Atur ulang kata sandi** (dialog kata sandi baru + ulangi) dan **Nonaktifkan / Aktifkan akun** (dialog konfirmasi).
- Pada akun sendiri, ubah peran dan nonaktifkan tidak tersedia.
- **Data:** `GET|PUT /admin/anggota/{id}`, `PATCH /admin/anggota/{id}/status`, `PATCH /admin/anggota/{id}/password`.

### 4.6 Struktur organisasi — `/admin/struktur`
- **Isi:** tab tersimpan di `?tab=`.
  - **Pengurus:**
    - kepala "Periode saat ini: …" + keterangan bahwa perubahan tercatat di riwayat;
    - tombol **Pratinjau bagan** (membuka `/struktur` di tab baru) dan **Tambah pengurus**;
    - peringatan **Tahun sudah berganti** bila perubahan berikutnya akan memulai periode baru;
    - tabel nama, divisi ("— Pengurus inti" bila kosong), jabatan, urutan, **Ubah** / hapus;
    - dialog tambah/ubah: cari anggota aktif (anggota yang sudah menjabat ditandai "Sudah pengurus"), jabatan, divisi.
  - **Divisi:** tabel + tambah/ubah (nama, deskripsi) / hapus (ditolak bila masih dipakai).
  - **Jabatan:** tabel urutan & nama + tambah/ubah/hapus (ditolak bila masih dipakai).
  - **Riwayat:** pemilih periode + tampilan riwayat yang sama dengan halaman anggota.
- Tidak ada tab Periode; periode dibuat otomatis.
- **Data:** `GET|POST|PUT|DELETE /admin/kepengurusan`, `/admin/divisi`, `/admin/jabatan`, `GET /divisi`, `GET /periode`, `GET /periode/{id}/riwayat`, `GET /admin/anggota?status=aktif`.

### 4.7 Pemeriksaan materi — `/admin/verifikasi-materi`
- **Isi:** tab **Minta tayang** dan **Minta diturunkan** (dengan jumlah; tersimpan di `?tab=penarikan`), keterangan per tab.
  - **Minta tayang:** tabel materi (subteks "Pertama kali diajukan" / "Pernah tayang, kemudian diturunkan"), penulis, versi, tanggal diajukan, tombol **Tinjau**; terlama di atas.
  - **Minta diturunkan:** tabel materi ("Masih tayang untuk umum"), penulis, alasan, tanggal, tombol **Tolak** (dialog catatan wajib) dan **Setujui** (dialog konfirmasi).
- **Data:** `GET /admin/verifikasi-materi`, `GET /admin/penarikan-materi`, `POST /admin/penarikan/{id}/verifikasi`.

### 4.8 Tinjau pengajuan — `/admin/verifikasi-materi/[revisiId]`
- **Isi:** judul + chip status; penulis, waktu diajukan, catatan perubahan.
  - Materi yang **pernah tayang** → perbandingan dua kolom "Versi yang terakhir tayang · vN" vs "Versi yang diajukan · vM": bagian berbeda diberi label "Berubah", kata baru disorot, kata terhapus dicoret; di ponsel menjadi dua tab.
  - Materi **belum pernah tayang** → satu kolom isi lengkap.
- **Bilah keputusan:** keterangan akibat persetujuan, **Tolak** (dialog catatan wajib) dan **Setujui & tayangkan**.
- **Data:** `GET /admin/revisi/{id}`, `POST /admin/revisi/{id}/verifikasi`.

### 4.9 Kategori materi — `/admin/kategori`
- **Isi:** tombol **Tambah kategori**; tabel nama, alamat tautan (slug), jumlah materi, **Edit** / **Hapus**.
- **Aksi:** tambah/ubah lewat dialog (nama; nama sama → galat); hapus lewat dialog (ditolak bila masih dipakai materi).
- **Data:** `GET|POST|PUT|DELETE /admin/kategori`.

### 4.10 Konten beranda — `/admin/beranda`
- **Isi:**
  1. **Cerita Al-Futuuhat:** judul cerita + editor Quill.
  2. **Visi dan misi:** visi (teks), misi (satu poin per baris).
  3. **Kontak organisasi:** alamat sekretariat, nomor WhatsApp, email, akun Instagram.
  - Tombol **Simpan** + "Terakhir diperbarui …".
- **Data:** `GET /admin/beranda`, `POST /admin/beranda`.
- Poster beranda sudah dihapus dari sistem.

### 4.11 Program — `/admin/program`
- **Isi:** tombol **Tambah program**; tabel program (nama + jadwal), PIC, status, "n foto · n video", **Kelola** / **Hapus** (dialog yang menyebut jumlah foto & video yang ikut terhapus).
- **Data:** `GET /admin/program`, `DELETE /admin/program/{id}`.

### 4.12 Tambah program — `/admin/program/baru`
- **Isi:** formulir nama, ringkasan, jadwal pelaksanaan, status (Berjalan/Selesai), gambar sampul, **PIC** (cari anggota aktif, boleh lebih dari satu, ditampilkan sebagai pil yang dapat dilepas), penjelasan program (Quill); tombol **Simpan program** / **Batal**.
- **Data:** `POST /admin/program`. Berhasil → `/admin/program/[id]?tab=dokumentasi`.

### 4.13 Kelola program — `/admin/program/[id]`
- **Isi:** nama, lencana status, alamat `/program/slug`, tautan **Lihat halaman publik**; tab **Informasi** (formulir yang sama dengan 4.12) dan **Dokumentasi (n)**.
- **Tab Dokumentasi:**
  - kotak **Tambah dokumentasi** dengan pilihan **Foto · Video · Tautan YouTube**;
  - foto/video boleh dipilih beberapa sekaligus dan diunggah satu per satu; antrean menampilkan status tiap berkas: siap, mengunggah, berhasil, atau gagal beserta alasannya;
  - keterangan opsional;
  - di bawahnya galeri yang sama dengan halaman publik, ditambah tombol hapus per butir.
- **Data:** `GET|POST /admin/program/{id}`, `POST /admin/program/{id}/dokumentasi`, `DELETE /admin/dokumentasi/{id}`, `GET /publik/program/{slug}/dokumentasi`.

### 4.14 Halaman anggota yang dibuka admin
Admin dapat membuka **Arsip materi**, **baca materi + forum**, **Struktur**, **Notifikasi**, dan **Profil** dengan isi yang sama seperti bagian 3, tetapi di dalam layout admin. Admin **tidak** dapat membuka **Dasbor anggota** dan **Materi saya** (diarahkan ke `/admin`), dan backend menolak semua aksi menulis materi oleh admin.

---

## 5. Halaman galat

| Kondisi | Tampilan |
|---|---|
| Route tidak ada | `error.vue` (halaman tidak ditemukan). [TIDAK JELAS] isi persis `error.vue` tidak dibaca dalam penyusunan ini. |
| Data tidak ditemukan di halaman detail | Kotak pesan di dalam halaman, mis. "Programnya tidak ketemu." atau "Tulisan belum bisa dibuka". |
| Sesi berakhir (401) | Sesi dihapus dan pengguna diarahkan ke `/masuk`. |
