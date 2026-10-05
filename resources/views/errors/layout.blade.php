{{--
    エラーページの共通の形（#120）
    番号（404 など）は出さず、何が起きたかとどうすればよいかを、ふだんの言葉で書く
    データベースが止まっているとき（500）でも出せるように、ログインの確認やデータベースを使う部品（ナビなど）は使わない
    使い方：@include('errors.layout', ['title' => '…', 'message' => '…', 'hint' => '…'])
--}}
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}｜{{ config('app.name', 'FishingLog') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BIZ+UDPGothic:wght@400;700&family=Yomogi&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="font-sans text-ink antialiased">
    <x-crayon-filters />
    <div class="min-h-screen crayon-paper flex flex-col items-center px-4 py-10">
        <a href="{{ url('/') }}"><x-application-logo /></a>

        <main class="crayon-card crayon-empty mt-8 w-full max-w-lg p-6 text-center space-y-4">
            <h1 class="crayon-heading">{{ $title }}</h1>
            <p>{{ $message }}</p>
            @if (! empty($hint))
            <p class="text-sm text-sand">{{ $hint }}</p>
            @endif
            <div class="flex flex-wrap justify-center gap-3 pt-2">
                {{-- ログイン中ならトップからダッシュボードへ移るので、どちらの人も最初の画面に戻れる --}}
                <a href="{{ url('/') }}" class="crayon-button">最初の画面へ戻る</a>
                <button type="button" onclick="history.back()" class="crayon-button-secondary">前の画面に戻る</button>
            </div>
        </main>
    </div>
</body>

</html>
