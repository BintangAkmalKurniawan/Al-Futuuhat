<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya pengguna berstatus `aktif` yang boleh memakai API anggota (PRD §8.1).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAktif()) {
            abort(403, 'Akunmu sedang tidak aktif. Hubungi pengurus untuk mengaktifkannya lagi ya.');
        }

        return $next($request);
    }
}