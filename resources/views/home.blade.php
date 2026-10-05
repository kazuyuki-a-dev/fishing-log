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