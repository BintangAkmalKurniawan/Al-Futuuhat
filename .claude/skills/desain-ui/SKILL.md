---
name: desain-ui
description: Gunakan setiap kali membuat atau mengubah tampilan frontend Nuxt Al-Futuuhat — layout, halaman, komponen, warna, tipografi, kelas Tailwind, atau editor Quill — agar sesuai docs/DESIGN.md (tema gelap editorial bergaya Medium). Dipakai bersama skill frontend-nuxt.
---

# Skill: desain-ui (tampilan Al-Futuuhat)

Skill ini mengatur **tampilan**. Struktur kode Nuxt (composable, pemanggilan API, middleware) tetap mengikuti skill `frontend-nuxt`. Isi fitur tetap mengikuti `docs/PRD.md`.

## Sumber kebenaran
1. `docs/DESIGN.md` — prinsip, token, komponen, pola halaman, aturan.
2. `frontend/assets/css/main.css` — token Tailwind v4 (`@theme`) dan kelas dasar (`.btn*`, `.chip*`, `.input`, `.prosa`, `.prosa-editor`).
3. `docs/design/contoh-tampilan.html` dan `docs/design/*.png` — contoh visual tiap halaman.

## Langkah wajib sebelum menulis kode tampilan
1. Baca bagian `docs/DESIGN.md` yang relevan: §3 (token), §4 (layout), §5 (komponen), §6 (pola halaman yang sedang dikerjakan), §7 jika halaman menampilkan status materi, dan §9 jika halaman menampilkan struktur organisasi.
2. Buka gambar contoh halaman terkait di `docs/design/` dengan alat baca berkas.
3. Periksa versi Tailwind di `frontend/package.json`.
   - **v4:** pakai `main.css` apa adanya.
   - **v3:** jangan pasang v4. Salin token `@theme` ke `theme.extend` di `tailwind.config` dengan nama yang sama (`colors.kanvas`, `fontSize.judul`, `maxWidth.baca`, dst.), lalu pindahkan blok `@layer` ke CSS utama.
4. Pastikan paket huruf terpasang: `@fontsource-variable/source-serif-4` dan `@fontsource-variable/inter`.
5. **Sebelum membuat halaman baru, tunjukkan rencana tata letaknya kepada pengguna** (bagian-bagian halaman, komponen yang dipakai, perbedaan dari contoh) **dan tunggu persetujuan.** Pengguna ingin membahas tata letak setiap modul sebelum dibangun.

## Aturan saat menulis
- **Hanya pakai token.** Gunakan `bg-kanvas`, `bg-permukaan`, `border-garis`, `text-teks`, `text-teks-kuat`, `text-teks-lembut`, `text-teks-redup`, `text-aksen`, dan `.chip-*`. Jangan menulis hex di komponen, dan jangan memakai palet bawaan Tailwind (`gray-*`, `green-*`, dst.).
- **Huruf:** `font-serif` untuk judul, dek, dan isi materi; `font-sans` (bawaan `body`) untuk antarmuka. Isi materi selalu dibungkus `.prosa`. Editor Quill dibungkus `.prosa-editor`.
- **Lebar:** halaman baca/tulis memakai `max-w-baca mx-auto px-6`; halaman daftar memakai `max-w-halaman mx-auto px-6` dengan sidebar 320 px mulai `lg`.
- **Tombol:** bentuk pil (`.btn` + `.btn-primer|sekunder|hantu|bahaya`, ukuran `.btn-sm|.btn-lg`). Maksimal satu `btn-primer` per kelompok aksi.
- **Pemisah:** garis 1 px `border-garis`. Bayangan hanya untuk menu melayang, *toast*, dan modal.
- **Status materi:** selalu tampilkan `StatusChip` di area anggota, dengan label dan tombol sesuai tabel §7 DESIGN.md. Di halaman publik `/publikasi` *chip* tidak ditampilkan.
- **Privasi:** avatar memakai inisial (`AvatarInisial`), bukan foto. Halaman publik hanya menampilkan nama penulis dan pengurus.
- **Teks antarmuka:** bahasa Indonesia, kapital hanya di awal kalimat. Ikon tanpa teks wajib memakai `aria-label`.
- **Jangan menambah fitur** hanya karena Medium memilikinya (*clap*, *bookmark*, *follow*, tag). Jangan memakai nama, logo, ikon *clap*, atau warna hijau Medium.
- Ikon: `lucide-vue-next`, *stroke* 1,5, ukuran 16/20 px.
- **PrimeVue hanya untuk bagan struktur.** Pakai PrimeVue **4.5.5** (MIT, versi dikunci tanpa `^`) dan hanya komponen `OrganizationChart`, lewat komponen `BaganStruktur` dan composable `useBaganStruktur` (§9 DESIGN.md). Komponen PrimeVue lain tidak dipakai. Warna bagan diatur oleh blok `.p-organizationchart` di `main.css`, bukan oleh *preset*. Jangan memasang PrimeVue 5.x: versi itu butuh *license key*, dan API-nya berbeda.

## Komponen yang dipakai ulang
Buat atau pakai komponen di `frontend/components/` sesuai §5 DESIGN.md: `BaganStruktur`, `BaseButton`, `StatusChip`, `AvatarInisial`, `SearchPill`, `Tabs`, `StoryRow`, `ArticleHeader`, `ActionBar`, `RiwayatVersi`, `ForumDiskusi`, `KomentarComposer`, `KomentarItem`, `StatCard`, `DataTable`, `Pagination`, `EmptyState`, `Toast`, `ConfirmDialog`. Periksa dulu apakah komponen sudah ada sebelum membuat yang baru.

## Keadaan yang wajib ada di setiap halaman
- **Memuat:** kerangka (*skeleton*) `bg-permukaan animate-pulse` berbentuk sama dengan kontennya.
- **Kosong:** `EmptyState` dengan satu kalimat (mis. "Materi tidak ditemukan.").
- **Galat:** `.pesan-galat` di bawah isian untuk 422, *toast* untuk galat lain, dan halaman 404 untuk materi yang tidak ada.

## Pemeriksaan sebelum selesai
1. Jalankan `npm run dev`, lalu buka halaman di lebar **1280 px** dan **390 px**. Bandingkan dengan gambar contoh di `docs/design/`.
2. Cari hex atau palet bawaan Tailwind yang lolos:
   `grep -rnE "#[0-9a-fA-F]{3,6}|(bg|text|border)-(gray|zinc|neutral|slate|green|red|yellow)-" frontend/components frontend/pages frontend/layouts`
   Hasil yang diharapkan: kosong.
   Pastikan juga hanya `OrganizationChart` yang dipakai dari PrimeVue: `grep -rn "primevue" frontend --include=*.vue --include=*.ts -l` hanya menunjuk `nuxt.config.ts` dan berkas bagan.
3. Pastikan fokus *keyboard* terlihat dan semua tombol ikon punya `aria-label`.
4. Laporkan kepada pengguna: halaman/komponen yang dibuat, bagian yang berbeda dari contoh beserta alasannya, dan KP yang terkait.
