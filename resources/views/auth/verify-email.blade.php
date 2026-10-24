<x-guest-layout>
    {{-- メールアドレスの確認（#213）。確かめるまでは、ログインが必要なページはこの画面になる --}}
    <div class="space-y-4 text-sm">
        <h2 class="crayon-subheading">メールアドレスの確認</h2>

        <p>
            <b>{{ auth()->user()->email }}</b> に確認のメールを送りました。
            メールの「メールアドレスの確認」ボタンを押すと、登録が終わり、釣行の記録などを使えるようになります。
        </p>

        @if (session('status') == 'verification-link-sent')
        <p class="crayon-note px-3 py-2">確認のメールを、もう一度送りました。</p>
        @elseif (session('status') == 'verification-send-failed')
        <p class="crayon-note px-3 py-2">今は確認のメールを送れませんでした。時間をおいて、下の「確認のメールをもう一度送る」を押してください。</p>
        @endif

        <div class="crayon-note px-3 py-2 space-y-1">
            <p class="font-bold">メールが届かないとき</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>迷惑メールのフォルダを見てください（送り主は FishingLog &lt;{{ config('mail.from.address') }}&gt;）</li>
                <li>メールのリンクの有効期限は{{ config('auth.verification.expire', 60) }}分です。切れたら、もう一度送ってください</li>
                <li>アドレスがまちがっているときは、<a href="{{ route('profile.edit') }}" class="underline text-sea">プロフィール</a>で直せます</li>
            </ul>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-sand hover:text-ink">ログアウト</button>
            </form>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="crayon-button font-hand w-full sm:w-auto">確認のメールをもう一度送る</button>
            </form>
        </div>
    </div>
</x-guest-layout>
