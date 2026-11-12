<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        // Batas percobaan login
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // Tautan atur ulang kata sandi
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url').'/atur-ulang-kata-sandi?'.http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);
        });

        // Email atur ulang kata sandi
        ResetPassword::toMailUsing(function (User $user, string $token) {
            $menit = config('auth.passwords.users.expire');

            return (new MailMessage)
                ->subject('Buat kata sandi baru')
                ->greeting('Halo, '.$user->nama.'!')
                ->line('Kami menerima permintaan untuk membuat kata sandi baru untuk akunmu.')
                ->action('Buat kata sandi baru', call_user_func(ResetPassword::$createUrlCallback, $user, $token))
                ->line("Tautan ini berlaku {$menit} menit.")
                ->line('Kalau kamu tidak memintanya, abaikan saja email ini. Kata sandimu tidak berubah.')
                ->salutation('Salam, Pengurus Al-Futuuhat');
        });

        // Tautan verifikasi email
        VerifyEmail::createUrlUsing(function (User $user) {
            $path = URL::temporarySignedRoute(
                'verifikasi.email',
                now()->addMinutes(60),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
                absolute: false,
            );

            return rtrim(config('app.url'), '/').$path;
        });

        // Email verifikasi
        VerifyEmail::toMailUsing(function (User $user, string $url) {
            return (new MailMessage)
                ->subject('Konfirmasi emailmu')
                ->greeting('Halo, '.$user->nama.'!')
                ->line('Terima kasih sudah mendaftar di Al-Futuuhat. Klik tombol di bawah untuk mengonfirmasi emailmu.')
                ->action('Konfirmasi email', $url)
                ->line('Setelah itu, pendaftaranmu lanjut dicek admin. Tautan ini berlaku 60 menit.')
                ->line('Kalau kamu tidak merasa mendaftar, abaikan saja email ini.')
                ->salutation('Salam, Pengurus Al-Futuuhat');
        });
    }
}
