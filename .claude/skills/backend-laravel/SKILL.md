---
name: backend-laravel
description: Konvensi menulis kode backend Laravel (folder backend/) untuk Sistem Al-Futuuhat — migrasi, model, validasi, policy, controller, resource, route, event Reverb, dan tes. Gunakan setiap kali membuat atau mengubah kode di backend/.
---

# Konvensi backend Laravel — Al-Futuuhat

Gaya: Laravel standar yang mudah dipahami pemula. Hindari pola rumit (repository pattern, service container kustom, DDD). Jika logika panjang dipakai di lebih dari satu tempat, boleh dipindah ke satu class Action/Service sederhana di `app/Services/`.

## Urutan pengerjaan satu fitur
1. Migrasi/model hanya jika belum ada (umumnya sudah dibuat di F0).
2. `FormRequest` untuk validasi → `Policy` untuk hak akses → `Controller` → `JsonResource` → rute di `routes/api.php`.
3. Tes fitur (Feature test) untuk alur utama dan pengecualian (403, 422).
4. Jalankan `php artisan test` dan `php artisan route:list --path=api`.

## Model dan basis data
- Nama tabel Indonesia dan tunggal (PRD §10); tulis `protected $table = 'anggota';` dan seterusnya di setiap model.
- Gunakan `$fillable` (bukan `$guarded = []`) dan `casts()` untuk tanggal, boolean, dan `konten` (JSONB → `array`).
- Nilai status ditulis sebagai konstanta di model, mis. `User::STATUS_MENUNGGU = 'menunggu'`; validasi memakai `Rule::in([...])`.
- Operasi yang menulis ke lebih dari satu tabel WAJIB memakai `DB::transaction()` (KP-03.1, KP-07.1, KP-08.3, dll.).
- Foreign key dengan `constrained()`; `cascadeOnDelete()` hanya jika PRD menyebutnya (balasan komentar).
- Indeks GIN full-text memakai `DB::statement(...)` di migrasi (PRD §10).
- `Model::preventLazyLoading(! app()->isProduction());` di `AppServiceProvider::boot()`.

## Validasi (FormRequest)
- Satu FormRequest per aksi tulis, mis. `RegisterRequest`, `VerifikasiPendaftaranRequest`.
- Pesan galat berbahasa Indonesia; jika PRD menulis pesan persis (mis. "Email sudah terdaftar."), gunakan teks itu lewat `messages()`.
- Unggahan: `['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048']`.
- Simpan berkas dengan `->store('folder', 'public')`; kirim URL lewat `Storage::url()` di Resource.

## Otorisasi
- Peran dan status dicek dengan middleware (mis. `admin`, `aktif`) yang didaftarkan di `bootstrap/app.php`.
- Kepemilikan (penulis materi, pemilik komentar) dicek dengan Policy + `$this->authorize()` atau `Gate::authorize()`.
- Bukan pemilik/bukan admin → 403. Status tidak sesuai alur (mis. verifikasi revisi yang bukan `diajukan`) → 422 dengan pesan jelas, contohnya `abort(422, 'Revisi ini sudah diverifikasi.')` atau `ValidationException::withMessages([...])`.

## Controller dan respons
- Controller tipis: validasi di FormRequest, hak akses di Policy, respons lewat `JsonResource`/`ResourceCollection`.
- Daftar memakai `paginate()` dan eager loading: `User::with('anggota')->paginate(15)`. Untuk jumlah relasi pakai `withCount()`.
- Jangan pernah mengirim data pribadi (no HP, alamat, email, alasan, foto) di endpoint publik (KP-01.5): buat Resource terpisah untuk publik.
- Format galat bawaan Laravel (422 `{ message, errors }`) jangan diubah.

## Autentikasi
- Sanctum token: login mengembalikan `{ token, user }` dari `$user->createToken('web')->plainTextToken`.
- Logout menghapus token saat ini; menonaktifkan akun menghapus semua token (`$user->tokens()->delete()`).
- Login akun tidak aktif → 403 berisi `status_akun` dan `catatan` (PRD §11).

## Real-time (Reverb)
- Event memakai `ShouldBroadcastNow` (tanpa queue worker), kanal privat `materi.{id}`, nama event lewat `broadcastAs()` (`komentar.dibuat`, `komentar.dihapus`).
- Otorisasi kanal di `routes/channels.php`; rute broadcasting memakai prefix `api` dan middleware `auth:sanctum`.

## Keamanan konten
- `konten_html` dari Quill WAJIB disanitasi sebelum disimpan (paket `mews/purifier`), hanya mengizinkan tag yang dihasilkan Quill.

## Tes
- PHPUnit Feature test dengan `RefreshDatabase` di database PostgreSQL `alfutuuhat_test` (atur `DB_CONNECTION=pgsql` dan `DB_DATABASE=alfutuuhat_test` di `phpunit.xml`; SQLite tidak mendukung JSONB/GIN).
- Minimal satu tes untuk jalur normal dan satu untuk setiap pengecualian penting (403/422).
- Tes otomatis hanya alat bantu; pengujian resmi skripsi tetap Black Box manual.

## Seeder
- Admin dari `.env` (`ADMIN_NAMA`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`), 6 divisi, jabatan Ketua & Koordinator.
- Data contoh yang memakai factory hanya dipanggil jika `app()->environment('local')`, karena image Docker tidak memuat paket dev.
