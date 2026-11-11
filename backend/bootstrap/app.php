<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Kanal real-time memakai prefix /api dan token Sanctum (PRD §11, KF-15)
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum', 'aktif']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'aktif' => EnsureUserIsActive::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Rute API tidak punya halaman login web; tamu selalu mendapat 401 JSON
        // (KNF-02), termasuk saat diuji lewat curl/Postman tanpa header Accept JSON.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Panduan Bahasa UI: pesan bawaan Laravel berbahasa Inggris (mis. "This action is
        // unauthorized.", "No query results for model ...", "Server Error") tidak boleh tampil
        // ke pengguna. Pesan khusus yang ditulis di controller/Policy tidak disentuh.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
                return $response;
            }

            $data = $response->getData(true);
            $pesan = (string) ($data['message'] ?? '');
            $status = $response->getStatusCode();

            $pengganti = match (true) {
                $status === 401 && $pesan === 'Unauthenticated.' => 'Sesi sudah berakhir. Silakan masuk lagi.',
                $status === 403 && ($pesan === '' || $pesan === 'This action is unauthorized.') => 'Akses untuk aksi ini tidak diizinkan.',
                $status === 404 && ($pesan === '' || str_starts_with($pesan, 'No query results') || str_starts_with($pesan, 'The route')) => 'Data yang dicari tidak ditemukan. Mungkin sudah dihapus.',
                $status === 413 => 'Ukuran berkas terlalu besar.',
                $status === 429 => 'Terlalu banyak percobaan. Tunggu sebentar, lalu coba lagi.',
                $status >= 500 && $pesan === 'Server Error' => 'Terjadi kendala di server. Silakan coba lagi sebentar lagi.',
                default => null,
            };

            if ($pengganti === null) {
                return $response;
            }

            $data['message'] = $pengganti;

            return $response->setData($data);
        });
    })->create();