---
name: fitur
description: Mengerjakan satu fitur dari docs/ROADMAP.md (F0–F11) bagian backend atau frontend dengan alur RAD proyek Al-Futuuhat.
argument-hint: "[F0-F11] [backend|frontend]"
disable-model-invocation: true
---

Kerjakan fitur **$0**, bagian **$1**.

## 1. Pahami dulu
- Baca bagian $0 di `docs/ROADMAP.md` (tujuan, endpoint/halaman, skenario alur, skenario uji, "Selesai jika").
- Baca KF/KP terkait di `docs/PRD.md` §7, serta §10/§11 (backend) atau §12 (frontend).
- Periksa kondisi kode saat ini: apa yang sudah ada dari fitur sebelumnya dan apa yang belum.
- Periksa branch git. Jika belum berada di `fitur/<nama-fitur>`, usulkan nama branch dan minta persetujuan sebelum membuatnya.
- Jika bagian = frontend: pastikan backend fitur ini sudah selesai. Jika tata letak halaman belum disepakati, usulkan tata letak dulu dan TUNGGU persetujuan saya.
- Jika ada yang ambigu atau tidak ada di PRD, tanyakan (maksimal 5 pertanyaan) sebelum lanjut.

## 2. Rencana
Tampilkan rencana: daftar berkas yang dibuat/diubah, alasan singkat tiap berkas, dan KP yang akan dipenuhi. Tunggu persetujuan saya.

## 3. Kerjakan
- Ikuti skill `backend-laravel` atau `frontend-nuxt` sesuai bagian.
- Kerjakan hanya lingkup $0 bagian $1. Jangan menyentuh fitur lain.

## 4. Periksa
- Backend: `php artisan test`, `php artisan route:list --path=api`, dan pastikan tidak ada lazy loading di daftar.
- Frontend: pastikan `npm run build` berhasil tanpa galat.
- Perbaiki galat yang muncul sebelum melapor.

## 5. Laporan akhir (Bahasa Indonesia)
1. Ringkasan berkas yang dibuat/diubah.
2. Penjelasan singkat bagian kode terpenting (bekal untuk sidang).
3. Cara menguji: langkah Postman per endpoint (metode, URL, header, body) untuk backend, atau langkah di peramban untuk frontend.
4. Tabel status KP: | KP | Status (lulus tes otomatis / perlu dicek manual / belum) | Catatan |
5. Usulan baris log untuk `docs/ROADMAP.md` §6. Tambahkan baris tersebut hanya setelah saya setujui.
6. Usulan pesan commit. Jangan commit sebelum saya minta.
