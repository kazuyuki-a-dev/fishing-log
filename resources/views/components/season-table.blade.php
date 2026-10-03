{{-- 月別×魚種の表（FN-02・PG07）。ヒートマップとカルテの両方で使う --}}
{{-- rows：SeasonHeatmap が作った表／highlightMonth：印を付ける月（カルテで選んだ日付の月） --}}
@props(['rows', 'highlightMonth' => null])

@php
// 色の段階（1〜4）。Tailwind が見つけられるように、クラス名はそのまま書いておく
$levelClasses = [
    1 => 'bg-sea-100 text-ink',
    2 => 'bg-sea-400 text-white',
    3 => 'bg-sea-600 text-white',
    4 => 'bg-sea-800 text-white',
];
@endphp

{{-- スマホでは表を横に動かせる。魚種の列は動かさない --}}
<div class="bg-white rounded-md shadow-sm overflow-x-auto">
    <table class="w-full text-sm text-center border-collapse">
        <thead>
            <tr>
                <th scope="col" class="sticky left-0 bg-white px-3 py-2 text-left whitespace-nowrap">魚種</th>
                @foreach (range(1, 12) as $month)
                @if ($month === $highlightMonth)
                <th scope="col" class="px-1 py-2 font-bold text-float whitespace-nowrap">
                    {{ $month }}月<span class="sr-only">（選んだ日の月）</span>
                </th>
                @else
                <th scope="col" class="px-1 py-2 font-normal text-sand whitespace-nowrap">{{ $month }}月</th>
                @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $species => $months)
            <tr class="border-t border-gray-100">
                <th scope="row" class="sticky left-0 bg-white px-3 py-2 text-left font-bold whitespace-nowrap">{{ $species }}</th>
                @foreach ($months as $month => $cell)
                @php
                // 選んだ日の月は、オレンジの枠で囲む
                $ring = $month === $highlightMonth ? 'ring-2 ring-float' : '';
                @endphp
                @if ($cell['level'] > 0)
                <td class="p-0.5">
                    <span class="block min-w-10 rounded px-1 py-1 {{ $levelClasses[$cell['level']] }} {{ $ring }}">
                        <span class="block font-bold">{{ $cell['fish'] }}</span>
                        <span class="block text-xs">{{ $cell['trips'] }}回</span>
                    </span>
                </td>
                @else
                {{-- 記録のない月は白 --}}
                <td class="p-0.5">
                    <span class="block min-w-10 rounded border border-gray-100 bg-white px-1 py-1 {{ $ring }}">
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
    @if ($highlightMonth)
    <p>オレンジの枠＝選んだ日の月</p>
    @endif
    <p class="flex items-center gap-1">
        少ない
        @foreach ($levelClasses as $class)
        <span class="inline-block w-4 h-4 rounded {{ $class }}"></span>
        @endforeach
        多い
    </p>
</div>
