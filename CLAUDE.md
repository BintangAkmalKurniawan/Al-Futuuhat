# Sistem Informasi Al-Futuuhat

Aplikasi web skripsi S1: manajemen keanggotaan dan pengelolaan materi untuk organisasi Al-Futuuhat
(pendaftaran + verifikasi anggota, struktur kepengurusan per periode, materi dengan editor Quill
+ verifikasi + riwayat versi, forum diskusi real-time).

## Dokumen acuan (baca bagian yang relevan sebelum mengerjakan tugas)
- `docs/PRD.md`: sumber kebenaran tentang APA yang dibangun (KF/KP, model data §10, kontrak API §11, halaman §12, lingkungan §13).
- `docs/ROADMAP.md`: urutan fitur F0–F11, skenario uji per fitur, dan log implementasi (§6).
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
- Bahasa: jawab dalam Bahasa Indonesia. Teks antarmuka dan pesan galat berbahasa Indonesia, persis seperti di PRD jika PRD menyebutkannya.
- Penamaan: tabel/kolom berbahasa Indonesia sesuai PRD §10; nama class/method mengikuti konvensi Laravel/Vue (PascalCase/camelCase).
- Nama berkas harus sama persis dengan nama class (huruf besar/kecil). Windows tidak membedakan, tetapi Linux di Docker membedakan.
- Tidak ada query N+1: selalu eager loading untuk relasi yang ditampilkan (KNF-09).
- Unggahan gambar: JPG/PNG maks 2 MB (KNF-03).
- Tema visual: sementara Tailwind netral dan rapi (PRD §16 no. 8) sampai saya menentukan desain.
