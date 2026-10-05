@php($hideSpot = $trip->effectiveVisibility() === 'spot_hidden')
<article class="crayon-card p-4">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="font-bold">
            {{ $trip->went_at->format('Y/m/d') }}
            <span class="ml-2 text-sm font-normal">{{ $trip->time_of_day }}</span>
        </p>
        <p class="text-sm text-sand">
            {{ $trip->user_id === auth()->id() ? '自分' : $trip->user->name }}
        </p>
    </div>

    <p class="mt-1 text-sm">
        @if ($hideSpot)
        <span class="text-sand">釣り場は非公開（{{ $trip->spot->prefecture }}）</span>
        @else
        <a href="{{ route('spots.show', $trip->spot) }}" class="underline text-sea">{{ $trip->spot->name }}</a>
        <span class="text-sand">（{{ $trip->spot->prefecture }}）</span>
        @endif
    </p>
    <p class="mt-1 text-sm text-sand">
        潮：{{ $trip->tide ?? '不明' }} 天候：{{ $trip->weather ?? '不明' }}
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

    <div class="mt-3 text-right">
        <a href="{{ route('trips.show', $trip) }}" class="text-sm underline text-sea">詳しく見る</a>
    </div>
</article>