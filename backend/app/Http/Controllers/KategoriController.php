<?php

namespace App\Http\Controllers;

use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Daftar kategori materi untuk penyaring (KF-19, KP-19.2).
 * Bersifat publik: pengunjung pun perlu daftar ini untuk menyaring artikel.
 */
class KategoriController extends Controller
{
    /**
     * GET /api/kategori.
     */
    public function index(): AnonymousResourceCollection
    {
        return KategoriResource::collection(Kategori::orderBy('nama')->get());
    }
}
