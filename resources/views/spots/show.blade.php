<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="crayon-heading">{{ $spot->name }}</h2>
            <p class="text-sm text-sand">
                {{ $spot->prefecture }}
                @if ($spot->visibility === 'private')
                ・非公開
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
            <p class="crayon-note p-3 text-sm">{{ session('status') }}</p>
            @endif

            {{-- 釣り場を登録した直後だけ出す（PG08） --}}
            @if (session('registered'))
            <div class="crayon-card p-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm">今日ここに行ったなら、続けて釣行を記録しましょう。</p>
                <a href="{{ route('trips.create', ['spot' => $spot->id]) }}"
                    class="crayon-button">
                    この釣り場で釣行を記録する
                </a>
            </div>
            @endif

            {{-- 注意区分の警告（FN-14・NF-04）。見出しは注意区分だけ（「!」の印があるので「注意：」は付けない。#110） --}}
            @if ($spot->caution_type && $spot->caution_type !== 'なし')
            <div class="crayon-caution">
                <span class="crayon-caution-icon" aria-hidden="true">!</span>
                <div>
                    <p class="font-bold text-float-dark">{{ $spot->caution_type }}</p>
                    <p class="text-sm">現地の表示や決まりを守ってください。安全と法律を守る責任は、利用する人自身にあります。</p>
                </div>
            </div>
            @endif

            {{-- 釣行判断（FN-11）。黄色の付箋（#98） --}}
            <section class="crayon-sticky crayon-sticky-yellow p-5 space-y-4">
                <form method="GET" action="{{ route('spots.show', $spot) }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date" class="block text-xs text-sand">行く日</label>
                        <input id="date" type="date" name="date" value="{{ $judge['date']->format('Y-m-d') }}"
                            onchange="this.form.submit()" class="mt-1 rounded-md border-gray-300 text-ink">
                    </div>
                    <div>
                        <label for="time_of_day" class="block text-xs text-sand">時間帯</label>
                        <x-option-select id="time_of_day" name="time_of_day" class="mt-1 border-gray-300 text-ink"
                            :options="config('fishing.times_of_day')" :selected="$judge['timeOfDay']"
                            placeholder="指定なし" onchange="this.form.submit()" />
                    </div>
                </form>

                <div>
                    <p class="text-sm text-sand">{{ $judge['date']->format('Y年n月j日') }}（旧暦{{ $judge['lunarDay'] }}日）</p>
                    <p class="font-hand text-4xl text-sea">{{ $judge['tide'] }}</p>
                </div>
                @if ($judge['forecast'])
                <div>
                    <p class="text-sm text-sand">天気予報</p>
                    <p class="text-xl font-bold">{{ $judge['forecast'] }}</p>
                    <p class="text-xs text-sand">
                        天気データ：<a href="https://open-meteo.com/" target="_blank" rel="noopener" class="underline">Open-Meteo</a>
                    </p>
                </div>
                @elseif ($spot->latitude === null)
                <p class="text-xs text-sand">この釣り場は位置が登録されていないので、天気予報は出せません。</p>
                @endif

                <div class="border-t-2 border-dashed border-sea/30 pt-4 space-y-2">
                    <p class="flex flex-wrap items-center gap-2 text-sm text-sand">
                        @if ($judge['visits'] > 0)
                        <x-match-level :level="$judge['level']" :label="$judge['label']" />
                        @endif
                        この釣り場で「{{ $judge['condition'] }}」だった日
                    </p>
                    @if ($judge['visits'] > 0)
                    <p class="text-2xl font-bold">{{ $judge['visits'] }}回行って {{ $judge['caught'] }}回釣れた</p>
                    {{-- 条件をゆるめたときも、ぴったりの条件の結果（坊主の記録）を隠さない --}}
                    @if ($judge['level'] !== 'exact' && $judge['exact']['visits'] > 0)
                    <p class="text-xs text-sand">
                        ぴったりの条件（{{ $judge['exact']['condition'] }}）では {{ $judge['exact']['visits'] }}回行って {{ $judge['exact']['caught'] }}回釣れた
                    </p>
                    @endif
                    @if ($judge['methods']->isNotEmpty())
                    <p class="text-sm">
                        釣れたときの釣り方：
                        @foreach ($judge['methods'] as $method => $count)
                        {{ $method }} {{ $count }}匹{{ ! $loop->last ? '・' : '' }}
                        @endforeach
                    </p>
                    <p class="text-sm">
                        よく釣れた魚：
                        @foreach ($judge['species'] as $species => $count)
                        {{ $species }} {{ $count }}匹{{ ! $loop->last ? '・' : '' }}
                        @endforeach
                    </p>
                    @endif
                    @else
                    <p>この条件で釣れた記録はまだありません。</p>
                    @endif
                </div>

                <p class="text-sm text-sand">次の大潮：{{ $judge['nextBigTide']->format('n月j日') }}</p>
            </section>

            {{-- 実績（FN-09） --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @auth
                {{-- 実績は付箋。自分はピンク、ほかの人は水色（見出しの文字でも分かるようにする） --}}
                <div class="crayon-sticky crayon-sticky-pink p-5">
                    <h3 class="text-sm text-sand">自分の実績</h3>
                    @if ($mine['visits'] > 0)
                    <p class="mt-2 text-2xl font-bold">
                        {{ $mine['visits'] }}回行って {{ $mine['caught'] }}回釣れた
                    </p>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-sand">最大サイズ</dt>
                        <dd>{{ $mine['maxSize'] ? $mine['maxSize'] . ' cm' : '記録なし' }}</dd>
                        <dt class="text-sand">最後に行った日</dt>
                        <dd>{{ $mine['lastWentAt']->format('Y/m/d') }}</dd>
                    </dl>
                    @else
                    <p class="mt-2">まだ記録がありません。</p>
                    @endif
                </div>
                @endauth

                <div class="crayon-sticky crayon-sticky-blue p-5">
                    <h3 class="text-sm text-sand">ほかの人の公開実績</h3>
                    @if ($others['visits'] > 0)
                    <p class="mt-2 text-2xl font-bold">
                        {{ $others['visits'] }}回行って {{ $others['caught'] }}回釣れた
                    </p>
                    @else
                    <p class="mt-2">公開されている記録はまだありません。</p>
                    @endif
                </div>
            </div>

            @auth
            <div class="flex justify-end">
                <a href="{{ route('trips.create', ['spot' => $spot->id]) }}" class="crayon-button">
                    この釣り場で釣行を記録
                </a>
            </div>
            @else
            <div class="crayon-card p-4 text-sm text-center">
                自分の釣行を記録して、釣れる条件を貯めていきませんか？
                <a href="{{ route('register') }}" class="ml-2 font-bold underline text-sea">会員登録する</a>
            </div>
            @endauth

            {{-- 月別×魚種の小さな表（PG07・FN-09）。判断ビューで選んだ日付の月に印を付ける --}}
            <section class="space-y-3">
                <h3 class="crayon-subheading">この釣り場の季節</h3>
                @if (empty($season['rows']))
                <p class="crayon-card p-4 text-sm">まだ釣果の記録がありません。</p>
                @else
                <p class="text-sm">月ごとの釣れた数です。この表は釣行 <span class="font-bold">{{ $season['tripCount'] }}</span> 件から作っています。</p>
                <x-season-table :rows="$season['rows']" :highlight-month="$judge['date']->month" />
                @endif
            </section>

            {{-- 釣行の履歴（FN-09） --}}
            <section class="space-y-3">
                <h3 class="crayon-subheading">釣行の履歴</h3>
                @forelse ($trips as $trip)
                {{-- 条件が一致した履歴には、赤いクレヨンのチェック（#98） --}}
                <article class="crayon-card p-4 {{ $judge['matchedIds']->contains($trip->id) ? 'crayon-check' : '' }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-bold">
                            {{ $trip->went_at->format('Y/m/d') }}
                            <span class="ml-2 text-sm font-normal">{{ $trip->time_of_day }}</span>
                            @if ($judge['matchedIds']->contains($trip->id))
                            <x-match-level class="ml-2" :level="$judge['level']" :label="$judge['label']" />
                            @endif
                        </p>
                        <p class="text-sm text-sand">
                            {{ $trip->user_id === auth()->id() ? '自分' : $trip->user->name }}
                        </p>
                    </div>
                    <p class="mt-1 text-sm text-sand">
                        潮：{{ $trip->tide ?? '不明' }}　天候：{{ $trip->weather ?? '不明' }}
                    </p>

                    @if ($trip->catches->isEmpty())
                    <p class="mt-2 text-sm text-sand"><x-icon name="bucket" class="mr-1 h-5 w-5 align-[-0.3em]" />坊主</p>
                    @else
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($trip->catches as $catch)
                        <li>
                            <x-icon name="fish" class="mr-1 h-4 w-4 text-[#2F86B5] align-[-0.2em]" />{{ $catch->fish_species }}
                            @if ($catch->length_cm)
                            {{ $catch->length_cm }} cm
                            @endif
                            <span class="text-sand">（<x-icon :name="$catch->method === 'ルアー' ? 'lure' : 'worm'" class="h-4 w-4 text-float-dark align-[-0.2em]" />{{ $catch->method }}{{ $catch->method_detail ? '・' . $catch->method_detail : '' }}）</span>
                            @if ($catch->photoUrl())
                            <img src="{{ $catch->photoUrl() }}" alt="{{ $catch->fish_species }}の写真" loading="lazy"
                                class="mt-2 max-h-48 rounded-md object-cover">
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </article>
                @empty
                <p class="crayon-card p-4 text-sm">
                    まだこの釣り場の記録はありません。最初の釣行を記録しましょう。
                </p>
                @endforelse
            </section>

            {{-- 現地の情報（FN-14） --}}
            <section class="crayon-card p-5">
                <h3 class="crayon-subheading">現地の情報</h3>
                <dl class="mt-3 grid grid-cols-[8rem_1fr] gap-y-2 text-sm">
                    <dt class="text-sand">駐車場</dt>
                    <dd>{{ $spot->parking_type ?? '未入力' }}{{ $spot->parking_note ? '（' . $spot->parking_note . '）' : '' }}</dd>
                    <dt class="text-sand">トイレ</dt>
                    <dd>{{ $spot->toilet_available ?? '未入力' }}{{ $spot->toilet_note ? '（' . $spot->toilet_note . '）' : '' }}</dd>
                    <dt class="text-sand">コンビニまで</dt>
                    <dd>{{ $spot->convenience_distance_m !== null ? $spot->convenience_distance_m . ' m' : '未入力' }}</dd>
                    <dt class="text-sand">現地のメモ</dt>
                    <dd class="whitespace-pre-line">{{ $spot->facility_note ?? '未入力' }}</dd>
                </dl>
                {{-- 釣り場のメモは、登録した本人にだけ見せる自分用の覚え書き（釣行のメモと同じ。#128） --}}
                @can('updateBasic', $spot)
                @if ($spot->notes)
                <div class="mt-4 crayon-note p-3 text-sm">
                    <p class="text-xs text-sand">自分のメモ（あなたにだけ表示しています）</p>
                    <p class="mt-1 whitespace-pre-line">{{ $spot->notes }}</p>
                </div>
                @endif
                @endcan
                @if ($location)
                <div x-data="spotMapView(@js($location))" class="mt-4">
                    <div x-ref="map" class="h-56 rounded-md border border-gray-200"></div>
                    <p class="mt-1 text-xs text-sand">
                        {{ $location['exact'] ? '登録した位置です（正確な位置は、あなたにだけ表示しています）。' : 'だいたいの位置です（約1km四方）。' }}
                    </p>
                </div>
                @endif
                <p class="mt-4 text-xs text-sand">
                    最終更新：{{ $spot->editor?->name ?? '退会したユーザー' }}（{{ $spot->updated_at->format('Y/m/d') }}）
                    ・現地の情報は参考情報です。
                </p>
                @auth
                <div class="mt-4 text-right">
                    <a href="{{ route('spots.edit', $spot) }}" class="text-sm underline text-sea">
                        @can('updateBasic', $spot)
                        釣り場を編集する
                        @else
                        現地の情報を更新する
                        @endcan
                    </a>
                </div>
                @endauth
            </section>

            {{-- 不適切な投稿の報告（PG21）。ほかの人が登録した公開の釣り場だけ。ゲストには出さない --}}
            @auth
            @can('report', $spot)
            @include('reports.partials.modal', ['field' => 'spot_id', 'targetId' => $spot->id, 'targetLabel' => 'この釣り場'])
            @endcan
            @endauth
        </div>
    </div>
</x-app-layout>