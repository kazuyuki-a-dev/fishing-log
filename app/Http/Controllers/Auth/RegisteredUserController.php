<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'home_prefecture' => ['required', 'string', Rule::in(config('prefectures'))],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'home_prefecture' => $request->home_prefecture,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 確認のメールは Registered の知らせで送られる。メール送信サービスにつながらないときも、登録は済ませて案内を出す（#213）
        try {
            event(new Registered($user));
        } catch (TransportExceptionInterface $e) {
            Log::error('確認のメールを送れませんでした', ['error' => $e->getMessage()]);
            Auth::login($user);

            return redirect()->route('verification.notice')->with('status', 'verification-send-failed');
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
