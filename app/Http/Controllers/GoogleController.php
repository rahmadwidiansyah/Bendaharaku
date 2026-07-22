<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->user();

        $finduser = User::where('google_id', $user->id)->orWhere('email', $user->email)->first();

        if ($finduser) {
            // Kalau user sudah ada, update google_id-nya jika belum ada
            $finduser->update(['google_id' => $user->id, 'avatar' => $user->getAvatar()]);
            Auth::login($finduser);
        } else {
            // Kalau belum ada, buat user baru
            $newUser = User::create([
                'name' => $user->name,
                'email' => $user->email,
                'google_id' => $user->id,
                'avatar' => $user->getAvatar(),
                'password' => encrypt('password_dummy_aja'),
            ]);
            Auth::login($newUser);
        }

        return redirect()->route('dashboard');
    }
}
