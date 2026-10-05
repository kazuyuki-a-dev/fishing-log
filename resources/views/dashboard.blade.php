<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">ダッシュボード</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- 主役の釣行プランナーへ --}}
            <div class="bg-white rounded-md shadow-sm p-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm">今日の潮と過去の記録から、行き先を決めましょう。</p>
                <a href="{{ route('planner') }}"
                    class="inline-flex w-full sm:w-auto justify-center items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    次の釣行をプランする
                </a>
            </div>

            {{-- サマリー指標（FN-06） --}}
            <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">累計の釣果</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $summary['catchCount'] }}<span class="ml-1 text-sm font-normal">匹</span></p>
                </div>
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">釣った魚種</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $summary['speciesCount'] }}<span class="ml-1 text-sm font-normal">種類</span></p>
                </div>
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">釣行した日</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $summary['tripDays'] }}<span class="ml-1 text-sm font-normal">日</span></p>
                </div>
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">自分の最大サイズ</p>
                    @if ($summary['biggest'])
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $summary['biggest']->length_cm }}<span class="ml-1 text-sm font-normal">cm</span></p>
                    <p class="text-xs text-sand">{{ $summary['biggest']->fish_species }}</p>
                    @else
                    <p class="mt-1 text-sm">記録なし</p>
                    @endif
                </div>
            </section>

            {{-- 継続カウンタ（FN-10）。数えるのは自分の釣行の回数（坊主も1回） --}}
            @php
            $counter = $keep['counter'];
            @endphp
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">今月の釣行</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $counter['thisMonth'] }}<span class="ml-1 text-sm font-normal">回</span></p>
                </div>
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">連続で記録している月</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $counter['streak'] }}<span class="ml-1 text-sm font-normal">か月</span></p>
                    @if (! $counter['recordedThisMonth'])
                    <p class="text-xs text-sand">今月記録すると {{ $counter['streak'] + 1 }}か月に</p>
                    @endif
                </div>
                <div class="bg-white rounded-md shadow-sm p-4">
                    <p class="text-xs text-sand">去年の{{ $counter['month'] }}月</p>
                    <p class="mt-1 text-2xl font-bold text-sea">{{ $counter['lastYear'] }}<span class="ml-1 text-sm font-normal">回</span></p>
                    @if ($counter['lastYear'] === 0)
                    <p class="text-xs text-sand">去年の{{ $counter['month'] }}月は記録なし</p>
                    @elseif ($counter['thisMonth'] > $counter['lastYear'])
                    <p class="text-xs text-sand">今月は去年より {{ $counter['thisMonth'] - $counter['lastYear'] }}回多い</p>
                    @elseif ($counter['thisMonth'] === $counter['lastYear'])
                    <p class="text-xs text-sand">今月は去年と同じ</p>
                    @else
                    <p class="text-xs text-sand">あと {{ $counter['lastYear'] - $counter['thisMonth'] }}回で去年に並ぶ</p>
                    @endif
                </div>
            </section>

            {{-- 気づきカード（FN-10）。何件の記録から言っているかを必ず出す --}}
            @php
            $insights = $keep['insights'];
            @endphp
            <section class="space-y-3">
                <h3 class="font-bold">気づき</h3>
                @if ($insights['remaining'] > 0)
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">釣行をあと {{ $insights['remaining'] }}件記録すると、潮・時間帯・天候の傾向をお知らせします。</p>
                @elseif (empty($insights['cards']))
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">まだはっきりした傾向は見えていません。同じ条件で2回以上行くと、ここに出てきます（自分の釣行 {{ $insights['tripCount'] }}件から）。</p>
                @else
                <ul class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($insights['cards'] as $card)
                    <li class="bg-white rounded-md shadow-sm p-4 text-sm space-y-1">
                        <p class="text-xs text-sand">{{ $card['label'] }}</p>
                        <p class="font-bold text-sea">{{ $card['value'] }}のときに釣れているようです</p>
                        <p>{{ $card['value'] }}で {{ $card['visits'] }}回行って {{ $card['caught'] }}回釣れた</p>
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
                <h3 class="font-bold">直近の釣行</h3>
                @if ($recentTrips->isEmpty())
                <div class="bg-white rounded-md shadow-sm p-4 text-sm space-y-3">
                    <p>まだ釣行の記録がありません。1件目を記録すると、ここに並びます。</p>
                    <p>前に行った釣行を覚えていたら、まとめて入れておくと、プランナーやカルテがすぐ役に立ちます。釣った魚の写真があれば、日時と釣り場を写真から読み取ります。</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('trips.create', ['mode' => 'bulk']) }}"
                            class="inline-flex items-center px-5 py-2.5 bg-sea rounded-md font-bold text-sm text-white hover:opacity-90 w-full sm:w-auto justify-center">
                            昔の釣行をまとめて登録する
                        </a>
                        <a href="{{ route('trips.create') }}" class="underline text-sea">1件ずつ記録する</a>
                    </div>
                </div>
                @else
                <ul class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
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
                <h3 class="font-bold">自分の釣り場カルテ</h3>
                @if ($mySpots->isEmpty())
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">釣行を記録すると、行った釣り場がここに並びます。</p>
                @else
                <ul class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
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
                <h3 class="font-bold">{{ auth()->user()->home_prefecture }}の新着釣果</h3>
                @forelse ($feedTrips as $trip)
                @include('trips.partials.feed-card', ['trip' => $trip])
                @empty
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">この県の公開釣果は、まだありません。</p>
                @endforelse
                <div class="text-right">
                    <a href="{{ route('feed') }}" class="text-sm underline text-sea">釣果フィードをもっと見る</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>