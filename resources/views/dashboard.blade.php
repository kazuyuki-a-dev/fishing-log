<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">ダッシュボード</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- 主役の釣行プランナーへ --}}
            <div class="crayon-card p-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm">今日の潮と過去の記録から、行き先を決めましょう。</p>
                <a href="{{ route('planner') }}"
                    class="crayon-button w-full sm:w-auto">
                    次の釣行をプランする
                </a>
            </div>

            {{-- サマリー指標（FN-06）と継続カウンタ（FN-10）。ノートの点線の表と、連続の月数のスタンプ（#100） --}}
            @php
            $counter = $keep['counter'];
            @endphp
            <section class="crayon-card p-5 space-y-3">
                <div class="flex items-start gap-4">
                    <dl class="crayon-ledger min-w-0 flex-1">
                        <dt>累計の釣果</dt>
                        <dd>{{ $summary['catchCount'] }}<span class="ml-0.5 text-sm font-normal">匹</span></dd>
                        <dt>釣った魚種</dt>
                        <dd>{{ $summary['speciesCount'] }}<span class="ml-0.5 text-sm font-normal">種類</span></dd>
                        <dt>釣行した日</dt>
                        <dd>{{ $summary['tripDays'] }}<span class="ml-0.5 text-sm font-normal">日</span></dd>
                        <dt>自分の最大サイズ</dt>
                        <dd>
                            @if ($summary['biggest'])
                            {{ $summary['biggest']->length_cm }}<span class="ml-0.5 text-sm font-normal">cm</span>
                            <span class="block text-xs font-normal text-sand">{{ $summary['biggest']->fish_species }}</span>
                            @else
                            <span class="text-sm font-normal">記録なし</span>
                            @endif
                        </dd>
                        {{-- 継続カウンタ。数えるのは自分の釣行の回数（坊主も1回） --}}
                        <dt>今月の釣行</dt>
                        <dd>{{ $counter['thisMonth'] }}<span class="ml-0.5 text-sm font-normal">回</span></dd>
                        <dt>去年の{{ $counter['month'] }}月</dt>
                        <dd>{{ $counter['lastYear'] }}<span class="ml-0.5 text-sm font-normal">回</span></dd>
                    </dl>

                    {{-- 連続で記録している月数のスタンプ。0か月なら点線の空き枠 --}}
                    @if ($counter['streak'] > 0)
                    <p class="crayon-stamp">
                        <span>連続<span class="block text-2xl">{{ $counter['streak'] }}</span>か月</span>
                    </p>
                    @else
                    <p class="crayon-stamp crayon-stamp-empty">記録すると<br>押されます</p>
                    @endif
                </div>

                <div class="space-y-0.5 text-xs text-sand">
                    @if (! $counter['recordedThisMonth'])
                    <p>今月記録すると {{ $counter['streak'] + 1 }}か月に</p>
                    @endif
                    @if ($counter['lastYear'] === 0)
                    <p>去年の{{ $counter['month'] }}月は記録なし</p>
                    @elseif ($counter['thisMonth'] > $counter['lastYear'])
                    <p>今月は去年より {{ $counter['thisMonth'] - $counter['lastYear'] }}回多い</p>
                    @elseif ($counter['thisMonth'] === $counter['lastYear'])
                    <p>今月は去年と同じ</p>
                    @else
                    <p>あと {{ $counter['lastYear'] - $counter['thisMonth'] }}回で去年に並ぶ</p>
                    @endif
                </div>
            </section>

            {{-- 気づきカード（FN-10）。何件の記録から言っているかを必ず出す --}}
            @php
            $insights = $keep['insights'];
            @endphp
            <section class="space-y-3">
                <h3 class="crayon-subheading">気づき</h3>
                @if ($insights['remaining'] > 0)
                <p class="crayon-card p-4 text-sm">釣行をあと {{ $insights['remaining'] }}件記録すると、潮・時間帯・天候の傾向をお知らせします。</p>
                @elseif (empty($insights['cards']))
                <p class="crayon-card p-4 text-sm">まだはっきりした傾向は見えていません。同じ条件で2回以上行くと、ここに出てきます（自分の釣行 {{ $insights['tripCount'] }}件から）。</p>
                @else
                <ul class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($insights['cards'] as $card)
                    {{-- オレンジの手書きのひとことと、数字に黄色のマーカー（#100） --}}
                    <li class="crayon-card p-4 text-sm space-y-1">
                        <p class="text-xs text-sand">{{ $card['label'] }}</p>
                        <p class="crayon-scrawl"><span aria-hidden="true">→ </span>{{ $card['value'] }}のときに釣れているようです</p>
                        <p>{{ $card['value'] }}で <span class="crayon-marker">{{ $card['visits'] }}回行って {{ $card['caught'] }}回釣れた</span></p>
                    </li>
                    @endforeach
                </ul>
                <p class="text-xs text-sand text-right">自分の釣行 {{ $insights['tripCount'] }}件から。記録が増えるほど確かになります。</p>
                @endif
            </section>

            {{-- シーズンヒートマップへ（PG16） --}}
            <div class="flex justify-end">
                <a href="{{ route('analysis.heatmap') }}" class="text-sm underline text-sea">月ごとに釣れる魚を見る（シーズンヒートマップ）</a>
            </div>

            {{-- 直近の釣行 --}}
            <section class="space-y-3">
                <h3 class="crayon-subheading">直近の釣行</h3>
                @if ($recentTrips->isEmpty())
                <div class="crayon-card p-4 text-sm space-y-3">
                    <p>まだ釣行の記録がありません。1件目を記録すると、ここに並びます。</p>
                    <p>前に行った釣行を覚えていたら、まとめて入れておくと、プランナーやカルテがすぐ役に立ちます。釣った魚の写真があれば、日時と釣り場を写真から読み取ります。</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('trips.create', ['mode' => 'bulk']) }}"
                            class="crayon-button-secondary w-full sm:w-auto">
                            昔の釣行をまとめて登録する
                        </a>
                        <a href="{{ route('trips.create') }}" class="underline text-sea">1件ずつ記録する</a>
                    </div>
                </div>
                @else
                <ul class="crayon-card divide-y divide-gray-100">
                    @foreach ($recentTrips as $trip)
                    <li class="p-4 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                        <a href="{{ route('trips.show', $trip) }}" class="hover:underline">
                            <span class="font-bold">{{ $trip->went_at->format('Y/m/d') }}</span>
                            <span class="ml-2">{{ $trip->spot->name }}</span>
                        </a>
                        <span class="text-sand">
                            {{ $trip->catches->isEmpty() ? '坊主' : $trip->catches->count() . '匹' }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap justify-end gap-4 text-sm">
                    <a href="{{ route('trips.create', ['mode' => 'bulk']) }}" class="underline text-sand">昔の釣行をまとめて登録</a>
                    <a href="{{ route('trips.index') }}" class="underline text-sea">釣行をすべて見る</a>
                </div>
                @endif
            </section>

            {{-- 自分の釣り場カルテ --}}
            <section class="space-y-3">
                <h3 class="crayon-subheading">自分の釣り場カルテ</h3>
                @if ($mySpots->isEmpty())
                <p class="crayon-card p-4 text-sm">釣行を記録すると、行った釣り場がここに並びます。</p>
                @else
                <ul class="crayon-card divide-y divide-gray-100">
                    @foreach ($mySpots as $spot)
                    <li class="p-4 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                        <a href="{{ route('spots.show', $spot) }}" class="font-bold hover:underline">{{ $spot->name }}</a>
                        <span>{{ $spot->visits }}回行って {{ $spot->caught }}回釣れた</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </section>

            {{-- 公開釣果の新着 --}}
            <section class="space-y-3">
                <h3 class="crayon-subheading">{{ auth()->user()->home_prefecture }}の新着釣果</h3>
                @forelse ($feedTrips as $trip)
                @include('trips.partials.feed-card', ['trip' => $trip])
                @empty
                <p class="crayon-card p-4 text-sm">この県の公開釣果は、まだありません。</p>
                @endforelse
                <div class="text-right">
                    <a href="{{ route('feed') }}" class="text-sm underline text-sea">釣果フィードをもっと見る</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>