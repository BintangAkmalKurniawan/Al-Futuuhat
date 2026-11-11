# Sistem Informasi Al-Futuuhat

Aplikasi web skripsi S1: manajemen keanggotaan dan pengelolaan materi untuk organisasi Al-Futuuhat
(pendaftaran + verifikasi anggota, struktur kepengurusan per periode, materi dengan editor Quill
+ verifikasi + riwayat versi, forum diskusi real-time).

## Dokumen acuan (baca bagian yang relevan sebelum mengerjakan tugas)
- `docs/PRD.md`: sumber kebenaran tentang APA yang dibangun (KF/KP, model data §10, kontrak API §11, halaman §12, lingkungan §13).
- `docs/ROADMAP.md`: urutan fitur F0–F11, skenario uji per fitur, dan log implementasi (§6).
- `docs/DESIGN.md`: sumber kebenaran tentang BAGAIMANA tampilannya (token, layout, komponen, pola halaman, bagan struktur). Untuk pekerjaan tampilan, gunakan skill `desain-ui`.
- Jangan menambah fitur, kolom, atau endpoint di luar PRD. Jika PRD kurang jelas atau bertentangan, berhenti dan tanyakan kepada saya.

## Struktur repositori
- `backend/`: Laravel 13 (REST API, Sanctum, Reverb). Konvensi: skill `backend-laravel`.
- `frontend/`: Nuxt 4, `ssr: false`, Tailwind. Konvensi: skill `frontend-nuxt`.
- `docker-compose.yml`, `backend/Dockerfile`, `frontend/Dockerfile`: hanya untuk menjalankan sistem utuh (demo/sidang), bukan untuk pengembangan harian.

## Menjalankan secara lokal (Windows + Laravel Herd, terminal Git Bash)
- Basis data: PostgreSQL di kontainer Docker `postgres-umum` (127.0.0.1:5432), database `alfutuuhat`; database tes `alfutuuhat_test`.
- Backend: `cd backend && php artisan serve` (port 8000) dan `php artisan reverb:start --debug` (port 8080).
- Frontend: `cd frontend && npm run dev` (port 3000).
- Tes backend: `cd backend && php artisan test`.
- Daftar rute: `cd backend && php artisan route:list --path=api`.
- Server pengembangan biasanya sudah saya jalankan sendiri di terminal lain. Jangan menjalankannya di latar belakang tanpa bertanya.

## Aturan kerja
- Satu sesi mengerjakan satu bagian fitur (backend ATAU frontend). Gunakan alur di skill `fitur`.
- Selalu tampilkan rencana (berkas yang dibuat/diubah + alasan) sebelum menulis kode.
- Untuk halaman frontend: tata letak dan alur halaman WAJIB dibahas dan disetujui saya dulu sebelum dibangun.
- Setelah selesai: jalankan tes, jelaskan cara menguji (Postman untuk backend, peramban untuk frontend), dan tuliskan status setiap KP fitur tersebut (lulus / belum / perlu dicek manual).
- Jangan `git commit`, `git push`, atau menghapus berkas di luar lingkup tugas kecuali saya minta.
- Jangan mengubah `.env`, `.env.docker`, `docker-compose.yml`, atau Dockerfile tanpa menyebutkannya di rencana.
- Saya harus memahami kode untuk sidang: kode sederhana dan standar Laravel/Nuxt, beri komentar singkat berbahasa Indonesia pada logika yang tidak jelas, dan jelaskan bagian penting setelah selesai.

## Konvensi umum
- Bahasa: jawab dalam Bahasa Indonesia. Teks antarmuka dan pesan galat berbahasa Indonesia, persis seperti di PRD jika PRD menyebutkannya, dan mengikuti "Panduan Bahasa UI" di bawah.
- Penamaan: tabel/kolom berbahasa Indonesia sesuai PRD §10; nama class/method mengikuti konvensi Laravel/Vue (PascalCase/camelCase).
- Nama berkas harus sama persis dengan nama class (huruf besar/kecil). Windows tidak membedakan, tetapi Linux di Docker membedakan.
- Tidak ada query N+1: selalu eager loading untuk relasi yang ditampilkan (KNF-09).
- Unggahan gambar: JPG/PNG maks 2 MB (KNF-03).
- Tema visual: sementara Tailwind netral dan rapi (PRD §16 no. 8) sampai saya menentukan desain.

## Panduan Bahasa UI
Berlaku untuk semua teks yang dilihat pengguna: banner/alert, toast, dialog konfirmasi, keadaan kosong, label tombol, placeholder, pesan validasi (`messages()` di FormRequest, `backend/lang/id/`), pesan dari API, dan isi notifikasi.

**Siapa pembacanya**
- Anggota dan pengunjung: orang awam, bukan orang IT. Gaya **santai dan sopan**, sapa dengan "kamu".
- Admin: gaya **formal**. Pakai "Anda", tanpa "ya"/"yuk"/"nih", tanpa emoji. Istilah teknis tetap diganti dengan bahasa biasa.
- Teks yang dipakai keduanya (label status, pesan bawaan Laravel, `lang/id/validation.php`): **netral**, tanpa kata ganti.

**Gaya (anggota & pengunjung)**
- Kalimat pendek, satu kalimat satu ide, maksimal 2–3 kalimat per pesan. Kata aktif dan langsung ("Perubahanmu sudah disimpan").
- Tanpa singkatan chat (gpp, yg, dll.).
- Jangan menyalahkan pengguna: "Fotonya kebesaran, maksimal 2 MB ya", bukan "File melebihi batas ukuran".
- Emoji boleh sesekali di momen positif (materi tayang, pendaftaran terkirim), tidak pernah di pesan galat.

**Pola banner, notifikasi, dan pesan status** — jawab berurutan:
1. Apa yang sedang terjadi (dari sudut pandang pengguna).
2. Apa artinya baginya ("pengunjung masih melihat versi lama").
3. Langkah berikutnya, **hanya** yang relevan dengan kondisi saat ini; jangan jelaskan seluruh alur. Bila tidak ada yang perlu dilakukan, katakan ("Kamu tinggal tunggu kabar dari admin, nanti kami kasih notifikasi").
Satu banner untuk beberapa keadaan (belum diajukan / lagi dicek / belum disetujui) dipecah menjadi pesan per keadaan.

**Istilah yang tidak boleh tampil → gantinya**
| Jangan | Pakai |
|---|---|
| draf (status) | Belum tayang ("draf" boleh bila konteksnya sudah jelas) |
| diajukan | Lagi dicek admin (admin: "menunggu pemeriksaan") |
| ditolak (materi) | Belum disetujui + tampilkan catatan admin |
| terbit / diterbitkan | Tayang untuk umum |
| revisi | Perubahan / versi baru |
| penarikan / tarik materi | Turunkan dari halaman umum (admin: "permintaan penurunan") |
| publikasi / verifikasi | Tayang / dicek (admin: "pemeriksaan") |
| versi publik v1, v2, nomor versi | Versi yang sedang tayang / versi sebelumnya (nomor versi hanya di Riwayat perubahan dan halaman tinjau admin) |
| kode galat (401/403/404/422/500), nama kolom basis data, status mentah, "server", "API", "token", "slug" | Kalimat biasa |

**Teknis**
- Pesan bawaan Laravel berbahasa Inggris diganti di `backend/bootstrap/app.php` (`$exceptions->respond`) dan `backend/lang/` (`APP_LOCALE=id`). Pesan khusus tetap ditulis di FormRequest/controller.
- Nama isian di pesan validasi bawaan diatur di `lang/id/validation.php` → `attributes`.
- Bila PRD mengutip sebuah pesan, PRD dan tes ikut diperbarui saat pesannya diubah.
