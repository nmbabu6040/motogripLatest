<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'current_password' => [
                'required_with:new_password',
                'current_password',
            ],

            'new_password' => [
                'nullable',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        $emailChanged = $user->email !== $validated['email'];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if (!empty($validated['new_password'])) {
            $user->password = $validated['new_password'];
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('status', 'profile-email-verification-sent');
        }

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
