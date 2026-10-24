{{-- 記録の内訳の表（カルテの判断ビュー。#211）。時間帯・潮ごとに、何回行って何回釣れたか --}}
<div class="overflow-x-auto">
    <table class="w-full max-w-sm text-sm tabular-nums">
        <caption class="pb-1 text-left text-sm text-sand">記録の内訳</caption>
        <thead>
            <tr class="border-b border-dashed border-gray-300">
                <th scope="col" class="py-1 text-left font-normal text-sand"><span class="sr-only">条件</span></th>
                <th scope="col" class="py-1 text-right font-normal text-sand">行った</th>
                <th scope="col" class="py-1 pl-4 text-right font-normal text-sand">釣れた</th>
            </tr>
        </thead>
        @foreach (['time_of_day' => '時間帯', 'tide' => '潮'] as $key => $title)
        <tbody>
            <tr>
                <th scope="rowgroup" colspan="3" class="pt-2 text-left font-bold">{{ $title }}</th>
            </tr>
            @foreach ($breakdown[$key] ?? [] as $value => $count)
            <tr class="border-b border-dashed border-gray-300">
                <th scope="row" class="py-1 text-left font-normal">{{ $value }}</th>
                <td class="py-1 text-right">{{ $count['visits'] }}</td>
                <td class="py-1 pl-4 text-right">{{ $count['caught'] }}</td>
            </tr>
            @endforeach
        </tbody>
        @endforeach
    </table>
</div>
@if ($missingTimes !== [])
<p class="text-xs text-sand">{{ implode('・', $missingTimes) }}の記録はまだありません。</p>
@endif
