<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => ['required', 'confirmed', Password::min(14)->mixedCase()->numbers()->uncompromised()],
        ])->validate();

        $user->forceFill(['password' => Hash::make($input['password'])])->save();

        // Using the emailed reset link proves control of the mailbox.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // A reset invalidates every other signed-in session for this account.
        \DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
