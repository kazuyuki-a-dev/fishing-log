@props(['breakdown', 'timeFiltered' => false])
{{-- 記録の内訳（#211）。数えた記録が、どの時間帯・潮・年に偏っているかを見る --}}
{{-- カルテとプランナーで同じ形にする（#223）。一覧を長くしないよう、押すと開く --}}
@php($missingTimes = $timeFiltered ? [] : array_values(array_diff(config('fishing.times_of_day'), array_keys($breakdown['time_of_day'] ?? []))))
@if ($breakdown !== [])
<details {{ $attributes->merge(['class' => 'text-sm']) }}>
    <summary class="w-fit cursor-pointer font-hand text-sea underline">記録の内訳を見る</summary>
    <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-xs">
        @foreach (['time_of_day' => '時間帯', 'tide' => '潮', 'year' => '年'] as $key => $title)
        <dt class="text-sand">{{ $title }}</dt>
        <dd>
            @foreach ($breakdown[$key] ?? [] as $value => $count)
            {{ $value }}{{ $key === 'year' ? '年' : '' }} {{ $count['visits'] }}回（釣れた {{ $count['caught'] }}）{{ ! $loop->last ? '・' : '' }}
            @endforeach
        </dd>
        @endforeach
    </dl>
    {{-- 時間帯で絞って数えたときは、ほかの時間帯がないのは当たり前なので書かない --}}
    @if ($missingTimes !== [])
    <p class="mt-1 text-xs text-sand">{{ implode('・', $missingTimes) }}の記録はまだありません。</p>
    @endif
</details>
@endif
