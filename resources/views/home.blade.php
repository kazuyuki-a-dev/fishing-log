<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- いちばん上は黄色の付箋（#102） --}}
            <section class="crayon-sticky crayon-sticky-yellow p-6 space-y-4">
                <h1 class="font-hand text-3xl text-sea">釣れる条件を、記録から見つける。</h1>
                <p class="text-sm">
                    釣行を記録すると、潮・時間帯・釣り方ごとに「何回行って何回釣れたか」が分かります。
                    みんなの公開釣果も参考にして、次の釣行先を決めましょう。
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('register') }}"
                        class="crayon-button">
                        会員登録（無料）
                    </a>
                    <a href="{{ route('login') }}"
                        class="crayon-button-secondary">
                        ログイン
                    </a>
                </div>
                <p class="text-sm">はじめての方は <a href="{{ route('guide') }}" class="underline text-sea">使い方を見る</a></p>
            </section>

            {{-- 登録の前に、どこまで試せるか（#209） --}}
            <section class="crayon-card p-5 space-y-4">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <h2 class="crayon-subheading">登録しなくても試せること</h2>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            <li><b>釣行プランナー</b>：行く日と県を選ぶと、みんなの公開記録から釣り場が並ぶ</li>
                            <li><b>釣り場カルテ</b>：その日の潮・天気予報と「何回行って何回釣れたか」、現地の情報</li>
                            <li><b>釣果フィード</b>：みんなの全体公開の釣果</li>
                            <li><b>単位変換</b>：重さ・長さ・ラインの号と lb</li>
                        </ul>
                    </div>
                    <div class="space-y-2">
                        <h2 class="crayon-subheading">登録すると使えること</h2>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            <li>釣行の記録（写真つき。公開範囲は3段階で、最初は非公開）</li>
                            <li>自分の記録を使ったプランナーとカルテ（非公開の記録も使える）</li>
                            <li>ダッシュボード・自己ベスト・シーズンヒートマップ・CSV 保存</li>
                            <li>現地の情報を直す・投稿の報告・同じ県の新着のお知らせ</li>
                        </ul>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a href="{{ route('planner', ['prefecture' => '秋田県']) }}" class="crayon-button font-hand w-full sm:w-auto text-center">
                        プランナーを試す（秋田県）
                    </a>
                </div>
            </section>

            <section class="crayon-card p-5 space-y-3">
                <h2 class="crayon-subheading">都道府県の釣果を見る</h2>
                <form method="GET" action="{{ route('feed') }}">
                    <x-prefecture-select name="prefecture" :selected="null" :with-all="true"
                        :placeholder="true" onchange="this.form.submit()" />
                </form>
            </section>

            <section class="space-y-3">
                <h2 class="crayon-subheading">みんなの新着釣果</h2>
                @forelse ($trips as $trip)
                @include('trips.partials.feed-card', ['trip' => $trip])
                @empty
                <p class="crayon-card p-4 text-sm">公開された釣果は、まだありません。</p>
                @endforelse
                <div class="text-right">
                    <a href="{{ route('feed', ['prefecture' => 'all']) }}" class="text-sm underline text-sea">釣果フィードをもっと見る</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>