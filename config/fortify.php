<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'middleware' => ['web', \App\Http\Middleware\SecurityHeaders::class],
    'auth_middleware' => 'auth',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'views' => true,
    'home' => '/admin',
    'prefix' => '',
    'domain' => null,
    'lowercase_usernames' => true,
    'limiters' => ['login' => 'login', 'two-factor' => 'two-factor'],
    'paths' => [
        'login' => null, 'logout' => null, 'password' => [
            'request' => null, 'reset' => null, 'email' => null, 'update' => null, 'confirm' => null, 'confirmation' => null,
        ],
        'register' => null,
        'verification' => ['notice' => null, 'verify' => null, 'send' => null],
        'user-profile-information' => null, 'user-password' => null,
        'two-factor' => ['login' => null, 'enable' => null, 'confirm' => null, 'disable' => null, 'qr-code' => null, 'secret-key' => null, 'recovery-codes' => null],
    ],
    'redirects' => ['login' => null, 'logout' => null, 'password-confirmation' => null, 'register' => null, 'email-verification' => null, 'password-reset' => null],
    // Public registration stays off until the customer/dealer approval workflow exists (Phase 4).
    'features' => [
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
    ],
];
