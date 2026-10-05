<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">釣行プランナー</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- 条件を選ぶ（#100：白いクレヨンの枠のカード） --}}
            <section class="crayon-card p-5 space-y-4">
                <form method="GET" action="{{ route('planner') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date" class="block text-xs text-sand">行く日</label>
                        <input id="date" type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                            onchange="this.form.submit()" class="mt-1 rounded-md border-gray-300 text-ink">
                    </div>
                    <div>
                        <label for="prefecture" class="block text-xs text-sand">県</label>
                        <x-prefecture-select id="prefecture" name="prefecture" class="mt-1 border-gray-300 text-ink"
                            :selected="$prefecture" :with-all="true" onchange="this.form.submit()" />
                    </div>
                    <div>
                        <label for="time_of_day" class="block text-xs text-sand">時間帯</label>
                        <x-option-select id="time_of_day" name="time_of_day" class="mt-1 border-gray-300 text-ink"
                            :options="config('fishing.times_of_day')" :selected="$timeOfDay"
                            placeholder="指定なし" onchange="this.form.submit()" />
                    </div>
                    <div>
                        <label for="scope" class="block text-xs text-sand">使う記録</label>
                        <x-option-select id="scope" name="scope" class="mt-1 border-gray-300 text-ink"
                            :options="['all', 'mine']"
                            :labels="['all' => 'みんなの公開記録も使う', 'mine' => '自分の記録だけ']"
                            :selected="$scope" onchange="this.form.submit()" />
                    </div>
                </form>

                <div>
                    <p class="text-sm text-sand">{{ $date->format('Y年n月j日') }}（旧暦{{ $lunarDay }}日）</p>
                    <p class="font-hand text-4xl text-sea">{{ $tide }}</p>
                </div>
            </section>

            {{-- 釣り場の候補 --}}
            @if ($plans->isEmpty())
            <div class="crayon-card p-6 text-center space-y-3">
                <p>まだこの県には釣り場がありません。</p>
                <div class="flex justify-center gap-4 text-sm">
                    <a href="{{ route('planner', ['date' => $date->format('Y-m-d'), 'prefecture' => 'all']) }}" class="underline text-sea">全国を見る</a>
                    <a href="{{ route('spots.create') }}" class="underline text-sea">最初に登録する</a>
                </div>
            </div>
            @else
            <ol class="space-y-3">
                @foreach ($plans as $plan)
                <li class="crayon-card p-4 flex gap-4">
                    {{-- 順位の数字にクレヨンの下線（#100） --}}
                    <span class="crayon-underline w-8 shrink-0 self-start text-center font-hand text-2xl text-sea">{{ $loop->iteration }}</span>

                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <a href="{{ route('spots.show', ['spot' => $plan['spot'], 'date' => $date->format('Y-m-d'), 'time_of_day' => $timeOfDay]) }}"
                                class="font-bold hover:underline">
                                {{-- 1位の釣り場名に黄色のマーカー（#100） --}}
                                <span class="{{ $loop->first ? 'crayon-marker' : '' }}">{{ $plan['spot']->name }}</span>
                            </a>
                            <span class="text-xs text-sand">{{ $plan['spot']->prefecture }}</span>
                            @if ($plan['spot']->caution_type && $plan['spot']->caution_type !== 'なし')
                            <span class="crayon-caution-tag">
                                注意：{{ $plan['spot']->caution_type }}
                            </span>
                            @endif
                        </div>

                        @if ($plan['visits'] > 0)
                        <p class="flex flex-wrap items-center gap-2">
                            <x-match-level :level="$plan['level']" :label="$plan['label']" />
                            <span>
                                {{ $plan['condition'] }}の日：
                                <span class="font-bold">{{ $plan['visits'] }}回行って {{ $plan['caught'] }}回釣れた</span>
                            </span>
                        </p>
                        {{-- 条件をゆるめたときも、ぴったりの条件の結果（坊主の記録）を隠さない --}}
                        @if ($plan['level'] !== 'exact' && $plan['exact']['visits'] > 0)
                        <p class="text-xs text-sand">
                            ぴったりの条件（{{ $plan['exact']['condition'] }}）では {{ $plan['exact']['visits'] }}回行って {{ $plan['exact']['caught'] }}回釣れた
                        </p>
                        @endif
                        @if ($plan['caught'] > 0)
                        <p class="text-sm text-sand">
                            @if ($plan['bestTimeOfDay'])
                            よく釣れた時間帯：{{ $plan['bestTimeOfDay'] }}
                            @endif
                            釣り方：
                            @foreach ($plan['methods'] as $method => $count)
                            {{ $method }} {{ $count }}匹{{ ! $loop->last ? '・' : '' }}
                            @endforeach
                        </p>
                        <p class="text-sm text-sand">
                            魚：
                            @foreach ($plan['species'] as $species => $count)
                            {{ $species }}{{ ! $loop->last ? '・' : '' }}
                            @endforeach
                            @if ($plan['maxSize'])
                            最大 {{ $plan['maxSize'] }} cm
                            @endif
                        </p>
                        @endif
                        @else
                        <p class="text-sm text-sand">この条件で釣れた記録はまだありません。</p>
                        @endif
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
    </div>
</x-app-layout>