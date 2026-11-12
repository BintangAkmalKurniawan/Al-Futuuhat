<?php

namespace App\Http\Controllers;

use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Daftar kategori materi untuk penyaring
class KategoriController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return KategoriResource::collection(Kategori::orderBy('nama')->get());
    }
}
