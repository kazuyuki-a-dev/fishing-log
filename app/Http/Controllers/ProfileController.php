<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\FishCatch;
use App\Services\PhotoStorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // お試し用アカウントは変更できない（だれでもログインできるため。#136）
        if ($request->user()->isDemo()) {
            return Redirect::route('profile.edit')->with('status', 'demo-locked');
        }

        $request->user()->fill($request->validated());

        $emailChanged = $request->user()->isDirty('email');
        if ($emailChanged) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // メールアドレスを変えたら、新しいアドレスを確かめ直す。確かめるまでは確認の画面になる（#213）
        if ($emailChanged) {
            return Redirect::route('verification.notice')
                ->with('status', $request->user()->sendEmailVerificationSafely() ? 'verification-link-sent' : 'verification-send-failed');
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, PhotoStorer $photos): RedirectResponse
    {
        // お試し用アカウントは退会できない（ほかの人が試せなくなるため。#136）
        if ($request->user()->isDemo()) {
            return Redirect::route('profile.edit')->with('status', 'demo-locked');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // 釣果の写真のファイル（データベースの行は一緒に消えるが、ファイルは自動では消えない。#118）
        $photoPaths = FishCatch::whereHas('trip', fn($trip) => $trip->where('user_id', $user->id))
            ->whereNotNull('image_path')
            ->pluck('image_path');

        // 自分宛てのお知らせも消す（notifications には外部キーがないので、自動では消えない）
        $user->notifications()->delete();
        $user->delete();

        // データベースから消せたあとで、写真のファイルを消す
        foreach ($photoPaths as $path) {
            $photos->delete($path);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
