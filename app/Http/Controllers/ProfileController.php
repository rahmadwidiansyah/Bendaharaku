<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        // Fill data teks biasa (name, email, dll)
        $user->fill($request->safe()->except(['avatar_file']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle Upload File Avatar Manual
        if ($request->hasFile('avatar_file')) {
            // Hapus avatar lama jika ada dan bukan URL dari Google
            if ($user->avatar && ! Str::startsWith($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Simpan file baru ke storage/app/public/avatars
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return Redirect::route('settings.account.profile')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
