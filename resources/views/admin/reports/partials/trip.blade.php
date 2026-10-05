{{-- 報告された釣行（管理画面）。釣り場名は「釣り場だけ隠す」でも出す（判断のため）。メモは本人にだけなので出さない --}}
@php
$visibilityLabels = config('fishing.visibility_labels');
@endphp
<dl class="grid grid-cols-[6rem_1fr] gap-y-1">
    <dt class="text-sand">日時</dt>
    <dd>{{ $trip->went_at->format('Y/m/d H:i') }} {{ $trip->time_of_day }}</dd>

    <dt class="text-sand">釣り場</dt>
    <dd>{{ $trip->spot->name }}（{{ $trip->spot->prefecture }}）</dd>

    <dt class="text-sand">記録した人</dt>
    <dd>{{ $trip->user->name }}</dd>

    <dt class="text-sand">公開範囲</dt>
    <dd>{{ $visibilityLabels[$trip->effectiveVisibility()] }}</dd>

    <dt class="text-sand">潮・天候</dt>
    <dd>{{ $trip->tide ?? '不明' }}・{{ $trip->weather ?? '不明' }}</dd>
</dl>

<div class="mt-3 space-y-3">
    @forelse ($trip->catches as $catch)
    <div>
        <p>
            <span class="font-bold">{{ $catch->fish_species }}</span>
            @if ($catch->length_cm)
            {{ $catch->length_cm }} cm
            @endif
            <span class="text-sand">{{ $catch->method }}{{ $catch->method_detail ? '・' . $catch->method_detail : '' }}</span>
        </p>
        @if ($catch->photoUrl())
        <img src="{{ $catch->photoUrl() }}" alt="{{ $catch->fish_species }}の写真" loading="lazy"
            class="mt-1 max-h-60 w-full rounded-md object-cover">
        @endif
    </div>
    @empty
    <p>坊主</p>
    @endforelse
</div>
