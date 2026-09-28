<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">釣果フィード</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('feed') }}" class="flex flex-wrap items-center gap-3">
                <x-prefecture-select name="prefecture" :selected="$prefecture" :with-all="true"
                    :placeholder="$prefecture === null" onchange="this.form.submit()" />
                <x-option-select name="species" :options="config('fishing.fish_species')" :selected="$species"
                    placeholder="すべての魚種" onchange="this.form.submit()" />
            </form>

            @if ($prefecture === null)
            <div class="bg-white rounded-md shadow-sm p-6 text-center">
                <p>見たい都道府県を選んでください。</p>
            </div>
            @elseif ($trips->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>この条件の公開釣果は、まだありません。</p>
                <a href="{{ route('trips.create') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    最初の投稿者になる
                </a>
            </div>

            @if ($spots->isNotEmpty())
            <section class="space-y-3">
                <h3 class="font-bold">この県の釣り場の現地情報</h3>
                @foreach ($spots as $spot)
                <article class="bg-white rounded-md shadow-sm p-4 text-sm">
                    <a href="{{ route('spots.show', $spot) }}" class="font-bold hover:underline">{{ $spot->name }}</a>
                    <p class="mt-1 text-sand">
                        駐車場：{{ $spot->parking_type ?? '未入力' }}
                        ・トイレ：{{ $spot->toilet_available ?? '未入力' }}
                    </p>
                </article>
                @endforeach
            </section>
            @endif
            @else
            @foreach ($trips as $trip)
            @php($hideSpot = $trip->effectiveVisibility() === 'spot_hidden')
            <article class="bg-white rounded-md shadow-sm p-4">
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
                    </li>
                    @endforeach
                </ul>
                @endif

                <div class="mt-3 text-right">
                    <a href="{{ route('trips.show', $trip) }}" class="text-sm underline text-sea">詳しく見る</a>
                </div>
            </article>
            @endforeach

            {{ $trips->links() }}
            @endif
        </div>
    </div>
</x-app-layout>