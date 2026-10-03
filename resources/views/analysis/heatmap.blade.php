<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">シーズンヒートマップ</h2>
    </x-slot>

    @php
    // 色の段階（1〜4）。Tailwind が見つけられるように、クラス名はそのまま書いておく
    $levelClasses = [
        1 => 'bg-sea-100 text-ink',
        2 => 'bg-sea-400 text-white',
        3 => 'bg-sea-600 text-white',
        4 => 'bg-sea-800 text-white',
    ];
    $scopes = ['public' => 'みんな', 'mine' => '自分'];
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm">月ごとに、その魚が釣れた数（匹）を色の濃さで表しています。下の小さな数字は、何回の釣行で釣れたかです。</p>

            <div class="flex flex-wrap items-center gap-3">
                {{-- みんな／自分の切り替え。今選んでいるほうを濃くする --}}
                <div class="inline-flex rounded-md shadow-sm" role="group" aria-label="集計する記録">
                    @foreach ($scopes as $value => $label)
                    <a href="{{ route('analysis.heatmap', ['scope' => $value, 'prefecture' => $prefecture]) }}"
                        @if ($scope === $value) aria-current="true" @endif
                        class="px-4 py-2 text-sm font-bold border border-sea {{ $loop->first ? 'rounded-l-md' : 'rounded-r-md -ml-px' }} {{ $scope === $value ? 'bg-sea text-white' : 'bg-white text-sea hover:bg-tide' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('analysis.heatmap') }}">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    <x-prefecture-select name="prefecture" :selected="$prefecture" :with-all="true"
                        aria-label="都道府県" onchange="this.form.submit()" />
                </form>
            </div>

            @if (empty($rows))
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>まだこの条件の記録がありません。</p>
                <div class="flex flex-wrap justify-center gap-4 text-sm">
                    <a href="{{ route('trips.create') }}" class="underline text-sea">釣行を記録する</a>
                    @if ($prefecture !== 'all')
                    <a href="{{ route('analysis.heatmap', ['scope' => $scope, 'prefecture' => 'all']) }}" class="underline text-sea">全国を見る</a>
                    @endif
                </div>
            </div>
            @else
            {{-- 元になった件数を見せて、記録を続けるきっかけにする（FN-10） --}}
            <p class="text-sm">この表は釣行 <span class="font-bold">{{ $tripCount }}</span> 件から作っています。記録が増えるほど確かになります。</p>

            {{-- スマホでは表を横に動かせる。魚種の列は動かさない --}}
            <div class="bg-white rounded-md shadow-sm overflow-x-auto">
                <table class="w-full text-sm text-center border-collapse">
                    <thead>
                        <tr>
                            <th scope="col" class="sticky left-0 bg-white px-3 py-2 text-left whitespace-nowrap">魚種</th>
                            @foreach (range(1, 12) as $month)
                            <th scope="col" class="px-1 py-2 font-normal text-sand whitespace-nowrap">{{ $month }}月</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $species => $months)
                        <tr class="border-t border-gray-100">
                            <th scope="row" class="sticky left-0 bg-white px-3 py-2 text-left font-bold whitespace-nowrap">{{ $species }}</th>
                            @foreach ($months as $month => $cell)
                            @if ($cell['level'] > 0)
                            <td class="p-0.5">
                                <span class="block min-w-10 rounded px-1 py-1 {{ $levelClasses[$cell['level']] }}">
                                    <span class="block font-bold">{{ $cell['fish'] }}</span>
                                    <span class="block text-xs">{{ $cell['trips'] }}回</span>
                                </span>
                            </td>
                            @else
                            {{-- 記録のない月は白 --}}
                            <td class="p-0.5">
                                <span class="block min-w-10 rounded border border-gray-100 bg-white px-1 py-1">
                                    <span class="block">&nbsp;</span>
                                    <span class="block text-xs">&nbsp;</span>
                                </span>
                            </td>
                            @endif
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 凡例 --}}
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-sand">
                <p>大きい数字＝釣れた数（匹）</p>
                <p>小さい数字＝釣行の回数</p>
                <p>白＝記録なし</p>
                <p class="flex items-center gap-1">
                    少ない
                    @foreach ($levelClasses as $class)
                    <span class="inline-block w-4 h-4 rounded {{ $class }}"></span>
                    @endforeach
                    多い
                </p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
