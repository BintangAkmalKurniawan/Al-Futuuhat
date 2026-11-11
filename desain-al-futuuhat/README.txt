PAKET DESAIN AL-FUTUUHAT — tema gelap editorial (gaya Medium)
==============================================================

Isi paket (struktur folder sama dengan repo):
  docs/DESIGN.md                       Sistem desain: prinsip, token, komponen, pola halaman
  docs/design/contoh-tampilan.html     Contoh 31 layar (semua halaman PRD §12), buka di peramban; ada daftar isi
  docs/design/01..31-*.png             Tangkapan layar setiap layar (dipakai di DESIGN.md §6)
  .claude/skills/desain-ui/SKILL.md    Skill Claude Code untuk pekerjaan tampilan
  frontend/assets/css/main.css         Token Tailwind v4 + kelas dasar + gaya gelap Quill

Cara memasang
-------------
1. Salin folder docs/, .claude/, dan frontend/ ke akar repo (gabungkan dengan yang sudah ada).
   Jika frontend/assets/css/main.css sudah ada, ganti isinya dengan berkas ini,
   lalu pindahkan kembali aturan lain yang memang Anda tulis sendiri.

2. Pasang paket huruf (di folder frontend):
     npm i @fontsource-variable/source-serif-4 @fontsource-variable/inter

3. Pastikan nuxt.config.ts memuat CSS tersebut:
     css: ['~/assets/css/main.css']

4. Bagan struktur (PrimeVue 4.5.5, hanya OrganizationChart):
     npm i primevue@4.5.5 @primeuix/themes@2.0.3
     npm i -D @primevue/nuxt-module@4.5.5
   lalu ikuti konfigurasi nuxt.config.ts di docs/DESIGN.md §9.2.
   Jangan memakai PrimeVue 5.x (butuh license key; API berbeda).

5. Di komponen editor, muat CSS Quill tema snow:
     import 'quill/dist/quill.snow.css'
   lalu bungkus editor dengan <div class="prosa-editor">.

6. Tambahkan satu baris ke CLAUDE.md:
     - Untuk pekerjaan tampilan (layout, halaman, komponen, Tailwind, Quill), gunakan skill desain-ui dan docs/DESIGN.md.

Catatan
-------
- main.css ditulis untuk Tailwind CSS v4. Jika proyek memakai v3, skill desain-ui
  menjelaskan cara memindahkan token ke tailwind.config.
- Contoh tampilan memakai data fiktif.
