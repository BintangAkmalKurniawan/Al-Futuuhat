---
name: frontend-nuxt
description: Konvensi menulis kode frontend Nuxt 4 (folder frontend/) untuk Sistem Al-Futuuhat — composable API, autentikasi token, middleware, layout, halaman, formulir, editor Quill, dan Laravel Echo. Gunakan setiap kali membuat atau mengubah kode di frontend/.
---

# Konvensi frontend Nuxt — Al-Futuuhat

Nuxt 4 dengan struktur folder `app/` (pages, layouts, components, composables, middleware, plugins), mode SPA (`ssr: false`), Tailwind, TypeScript ringan (tipe untuk data API, tanpa tipe rumit).

## Sebelum membuat halaman
- Tata letak dan alur halaman WAJIB disepakati dengan pengguna dulu. Jika belum ada kesepakatan, ajukan usulan singkat (bagian halaman, komponen, aksi, keadaan kosong/galat) dan tunggu persetujuan.
- Rute, layout, dan hak akses mengikuti PRD §12.

## Memanggil API — selalu lewat composable
- `useApi()` di `app/composables/useApi.ts`: membungkus `$fetch` dengan `baseURL = useRuntimeConfig().public.apiBase`, header `Accept: application/json` dan `Authorization: Bearer <token>`. Jika respons 401: hapus token, arahkan ke `/masuk`.
- Satu composable per modul yang memakai `useApi()`, mis. `useAuth`, `usePendaftaran`, `useAnggota`, `useStruktur`, `useMateri`, `useKomentar`, `useNotifikasi`.
- Halaman dan komponen TIDAK boleh memanggil `$fetch`/`useFetch` langsung ke backend.
- Tipe data respons API ditaruh di `app/types/` dan mengikuti bentuk JsonResource (`{ data, meta, links }`).

## Autentikasi
- Token disimpan dengan `useCookie('token')`; data pengguna dengan `useState('user')`.
- `useAuth()`: `login`, `logout`, `fetchMe`, `daftar`, plus computed `isAdmin` dan `isLoggedIn`.
- Middleware rute: `auth` (wajib login), `guest` (hanya tamu), `admin` (hanya admin; anggota dialihkan ke `/dasbor`).
- Setelah login: admin → `/admin`, anggota → `/dasbor` (KP-04.4). Tampilkan pesan login sesuai status akun (KP-03.3, KP-04.1–04.3).

## Layout
- `publik` (header + footer), `anggota` (menu atas), `admin` (sidebar kiri). Menu admin hanya tampil untuk admin.

## Formulir
- Galat 422 ditampilkan di bawah isian yang salah (`errors[field][0]`); nilai yang sudah diisi tidak boleh hilang (KP-02.5).
- Tombol kirim dinonaktifkan dan menampilkan status memuat selama permintaan berjalan.
- Unggah berkas dengan `FormData`; cek tipe (JPG/PNG) dan ukuran (≤ 2 MB) di sisi klien juga, tetapi validasi utama tetap di backend.

## Keadaan halaman
- Setiap halaman yang memuat data punya tiga keadaan: memuat, kosong (pesan jelas, mis. "Materi tidak ditemukan."), dan galat.
- Konfirmasi sebelum aksi yang merusak (hapus, tolak, nonaktifkan) memakai modal komponen, bukan `window.confirm`.

## Editor Quill
- Bungkus Quill dalam komponen `<ClientOnly>`; simpan `konten` (Delta JSON) dan `konten_html`.
- Handler gambar: unggah ke `POST /unggah-gambar`, lalu sisipkan URL yang dikembalikan (bukan base64; KP-08.2).
- Isi materi ditampilkan dengan `v-html` dari `konten_html` yang sudah disanitasi backend.

## Real-time (Laravel Echo + Reverb)
- Plugin `app/plugins/echo.client.ts` memakai `laravel-echo` + `pusher-js`, `broadcaster: 'reverb'`, nilai `key/host/port/scheme` dari `runtimeConfig.public.reverb*`, `authEndpoint` = `${apiBase}/broadcasting/auth` dengan header Bearer.
- Halaman materi berlangganan `private('materi.' + id)` saat dibuka dan `leave()` saat ditinggalkan (`onBeforeUnmount`).

## Gaya
- Tailwind netral dan rapi, responsif (KNF-04), teks antarmuka berbahasa Indonesia.
- Komponen kecil yang dipakai ulang (tombol, input, modal, kartu, tabel, paginasi) di `app/components/ui/`.
