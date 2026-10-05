{{-- プライバシーポリシー（NF-05・PG24）。定義書の「書くこと」と、あとから増えた機能（写真・報告・外部サービス）を書く（#118） --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">プライバシーポリシー</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-sm">
            <p>FishingLog（このサービス）が、どんな情報を受け取り、どう使い、どこまで公開するかを説明します。</p>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">1. 受け取る情報</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>会員登録のとき：メールアドレス・ニックネーム・メインフィールド（よく行く都道府県）・パスワード（元に戻せない形に変えて保存します）</li>
                    <li>記録するとき：釣行の日時・釣り場（位置）・潮・天候・釣果・写真・メモ、釣り場の現地の情報</li>
                    <li>報告するとき：報告の理由と詳しい内容</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">2. 使いみち</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>ログイン、釣行の記録と集計、プランナーやカルテの表示、都道府県での絞り込み、同じ県の新着のお知らせなど、このサービスを動かすためだけに使います。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">3. 公開する範囲</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>メールアドレスと、メインフィールドの都道府県は公開しません。ほかの人に見えるのはニックネームだけです。</li>
                    <li>釣行は、選んだ公開範囲（全体公開・釣り場だけ隠す・非公開）にしたがって表示します。「釣り場だけ隠す」の釣り場の名前と位置は、画面に送る前に取り除きます。</li>
                    <li>公開する釣り場の位置は、約1km四方にぼかして表示します。正確な位置は、登録した本人にだけ表示します。</li>
                    <li>釣行・釣果のメモは、本人にだけ表示します。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">4. 写真</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>写真に入っている撮影日時や撮影場所などの情報（Exif）は、保存するときに必ず消します。あとで非公開から公開に変えても、撮影場所がもれないようにするためです。</li>
                    <li>写真から撮影日時と撮影場所を読み取るのは、写真を選んだ端末（ブラウザ）の中で行います。読み取った撮影場所は、近くの登録済みの釣り場を探すためにだけサーバーに送り、保存しません。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">5. 報告</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>報告した人の名前は、投稿した人には伝えません。管理者は、対応のために報告した人と内容を見ます。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">6. 外部のサービス</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>天気予報と、釣行の日時の天候を調べるために、釣り場の位置（緯度・経度）と日付を <a href="https://open-meteo.com/" target="_blank" rel="noopener" class="underline text-sea">Open-Meteo</a> に送ります。名前やメールアドレスは送りません。</li>
                    <li>地図は <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline text-sea">OpenStreetMap</a> の地図の画像を、見る人のブラウザから読み込みます。</li>
                    <li>文字の形（フォント）を Google Fonts から読み込みます。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">7. Cookie</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>ログインしたままにするためと、送信を安全に行うために Cookie を使います。広告や、ほかのサイトでの追跡には使いません。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">8. 退会したとき</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>自分の釣行・釣果・写真・報告・お知らせは、すべて削除します。</li>
                    <li>釣り場はみんなで使う情報なので削除せず、登録した人の名前を外して残します。</li>
                </ul>
            </section>

            <p class="text-xs text-sand text-right">2026年10月5日</p>
        </div>
    </div>
</x-app-layout>
