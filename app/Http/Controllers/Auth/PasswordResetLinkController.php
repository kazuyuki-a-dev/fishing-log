<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // メール送信サービスにつながらないときは、エラーの画面にせず、ログに残して利用者に伝える（#202）
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (TransportExceptionInterface $e) {
            Log::error('パスワード再設定のメールを送れませんでした', ['error' => $e->getMessage()]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => '今はメールを送れませんでした。時間をおいて、もう一度お試しください。']);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
