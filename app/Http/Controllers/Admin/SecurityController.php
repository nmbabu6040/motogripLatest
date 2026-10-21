<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Two-factor setup uses Fortify's endpoints; this controller renders the state and manages sessions.
class SecurityController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $sessions = DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get()
            ->map(fn ($s) => [
                'current' => $s->id === $request->session()->getId(),
                'ip' => $s->ip_address,
                'agent' => \Illuminate\Support\Str::limit((string) $s->user_agent, 80),
                'last_active' => \Carbon\Carbon::createFromTimestamp($s->last_activity),
            ]);

        return view('admin.security', [
            'user' => $user,
            'twoFactorPending' => $user->two_factor_secret && ! $user->two_factor_confirmed_at,
            'twoFactorEnabled' => (bool) $user->two_factor_confirmed_at,
            'sessions' => $sessions,
        ]);
    }

    public function destroyOtherSessions(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        activity('admin')->causedBy($request->user())->log('sessions.revoked_others');

        return back()->with('status', 'Other sessions signed out.');
    }
}
