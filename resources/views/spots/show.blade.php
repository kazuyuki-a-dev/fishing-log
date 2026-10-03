<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-xl font-bold text-sea">{{ $spot->name }}</h2>
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
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif

            {{-- 釣り場を登録した直後だけ出す（PG08） --}}
            @if (session('registered'))
            <div class="bg-white rounded-md shadow-sm p-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm">今日ここに行ったなら、続けて釣行を記録しましょう。</p>
                <a href="{{ route('trips.create', ['spot' => $spot->id]) }}"
                    class="inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    この釣り場で釣行を記録する
                </a>
            </div>
            @endif

            {{-- 注意区分の警告（FN-14・NF-04） --}}
            @if ($spot->caution_type && $spot->caution_type !== 'なし')
            <div class="rounded-md border-l-4 border-float bg-white p-4 shadow-sm">
                <p class="font-bold text-float">注意：{{ $spot->caution_type }}</p>
                <p class="text-sm">現地の表示や決まりを守ってください。安全と法律を守る責任は、利用する人自身にあります。</p>
            </div>
            @endif

            {{-- 釣行判断（FN-11） --}}
            <section class="bg-sea text-white rounded-md shadow-sm p-5 space-y-4">
                <form method="GET" action="{{ route('spots.show', $spot) }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date" class="block text-xs text-sea-100">行く日</label>
                        <input id="date" type="date" name="date" value="{{ $judge['date']->format('Y-m-d') }}"
                            onchange="this.form.submit()" class="mt-1 rounded-md border-0 text-ink">
                    </div>
                    <div>
                        <label for="time_of_day" class="block text-xs text-sea-100">時間帯</label>
                        <x-option-select id="time_of_day" name="time_of_day" class="mt-1 border-0 text-ink"
                            :options="config('fishing.times_of_day')" :selected="$judge['timeOfDay']"
                            placeholder="指定なし" onchange="this.form.submit()" />
                    </div>
                </form>

                <div>
                    <p class="text-sm text-sea-100">{{ $judge['date']->format('Y年n月j日') }}（旧暦{{ $judge['lunarDay'] }}日）</p>
                    <p class="text-3xl font-bold">{{ $judge['tide'] }}</p>
                </div>
                @if ($judge['forecast'])
                <div>
                    <p class="text-sm text-sea-100">天気予報</p>
                    <p class="text-xl font-bold">{{ $judge['forecast'] }}</p>
                    <p class="text-xs text-sea-100">
                        天気データ：<a href="https://open-meteo.com/" target="_blank" rel="noopener" class="underline">Open-Meteo</a>
                    </p>
                </div>
                @elseif ($spot->latitude === null)
                <p class="text-xs text-sea-100">この釣り場は位置が登録されていないので、天気予報は出せません。</p>
                @endif

                <div class="border-t border-sea-400 pt-4 space-y-2">
                    <p class="text-sm text-sea-100">
                        この釣り場で「{{ $judge['tide'] }}{{ $judge['timeOfDay'] ? '・' . $judge['timeOfDay'] : '' }}」だった日
                    </p>
                    @if ($judge['visits'] > 0)
                    <p class="text-2xl font-bold">{{ $judge['visits'] }}回行って {{ $judge['caught'] }}回釣れた</p>
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

                <p class="text-sm text-sea-100">次の大潮：{{ $judge['nextBigTide']->format('n月j日') }}</p>
            </section>

            {{-- 実績（FN-09） --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @auth
                <div class="bg-white rounded-md shadow-sm p-5">
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

                <div class="bg-white rounded-md shadow-sm p-5">
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
                <a href="{{ route('trips.create', ['spot' => $spot->id]) }}"
                    class="inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    この釣り場で釣行を記録
                </a>
            </div>
            @else
            <div class="bg-white rounded-md shadow-sm p-4 text-sm text-center">
                自分の釣行を記録して、釣れる条件を貯めていきませんか？
                <a href="{{ route('register') }}" class="ml-2 font-bold underline text-sea">会員登録する</a>
            </div>
            @endauth

            {{-- 月別×魚種の小さな表（PG07・FN-09）。判断ビューで選んだ日付の月に印を付ける --}}
            <section class="space-y-3">
                <h3 class="font-bold">この釣り場の季節</h3>
                @if (empty($season['rows']))
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">まだ釣果の記録がありません。</p>
                @else
                <p class="text-sm">月ごとの釣れた数です。この表は釣行 <span class="font-bold">{{ $season['tripCount'] }}</span> 件から作っています。</p>
                <x-season-table :rows="$season['rows']" :highlight-month="$judge['date']->month" />
                @endif
            </section>

            {{-- 釣行の履歴（FN-09） --}}
            <section class="space-y-3">
                <h3 class="font-bold">釣行の履歴</h3>
                @forelse ($trips as $trip)
                <article class="bg-white rounded-md shadow-sm p-4 {{ $judge['matchedIds']->contains($trip->id) ? 'ring-2 ring-float' : '' }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-bold">
                            {{ $trip->went_at->format('Y/m/d') }}
                            <span class="ml-2 text-sm font-normal">{{ $trip->time_of_day }}</span>
                            @if ($judge['matchedIds']->contains($trip->id))
                            <span class="ml-2 rounded bg-float px-2 py-0.5 text-xs font-bold text-white">条件一致</span>
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
                    <p class="mt-2 text-sm">坊主</p>
                    @else
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($trip->catches as $catch)
                        <li>
                            {{ $catch->fish_species }}
                            @if ($catch->length_cm)
                            {{ $catch->length_cm }} cm
                            @endif
                            <span class="text-sand">（{{ $catch->method }}{{ $catch->method_detail ? '・' . $catch->method_detail : '' }}）</span>
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
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">
                    まだこの釣り場の記録はありません。最初の釣行を記録しましょう。
                </p>
                @endforelse
            </section>

            {{-- 現地の情報（FN-14） --}}
            <section class="bg-white rounded-md shadow-sm p-5">
                <h3 class="font-bold">現地の情報</h3>
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
        </div>
    </div>
</x-app-layout>