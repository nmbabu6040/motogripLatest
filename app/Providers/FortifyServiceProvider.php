<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $r) => view('auth.reset-password', ['request' => $r]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // Only active accounts may sign in. The failure message is identical for every cause.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', Str::lower($request->input('email')))->first();

            if ($user && $user->status === 'active' && Hash::check($request->input('password'), $user->password)) {
                return $user;
            }
            return null;
        });

        Event::listen(Login::class, fn (Login $e) => $e->user->forceFill(['last_login_at' => now()])->saveQuietly());

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower($request->input('email')).'|'.$request->ip();
            return [Limit::perMinute(5)->by($key), Limit::perMinute(20)->by($request->ip())];
        });
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
