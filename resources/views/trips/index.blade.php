<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-sea">釣行の記録</h2>
            <a href="{{ route('trips.create') }}"
                class="inline-flex items-center px-4 py-2 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                釣行を記録
            </a>
        </div>
    </x-slot>

    @php
    $scopes = ['mine' => '自分', 'public' => 'みんな'];
    // 自分／みんなを切り替えても、ほかの条件はそのまま残す
    $keep = request()->except(['page', 'scope', 'prefecture']);
    $spotLabels = $spots->mapWithKeys(fn($spot) => [$spot->id => "{$spot->name}（{$spot->prefecture}）"])->all();
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif

            {{-- 自分／みんなの切り替え（FN-03） --}}
            <div class="inline-flex rounded-md shadow-sm" role="group" aria-label="検索する記録">
                @foreach ($scopes as $value => $label)
                <a href="{{ route('trips.index', array_merge($keep, ['scope' => $value])) }}"
                    @if ($filters['scope'] === $value) aria-current="true" @endif
                    class="px-4 py-2 text-sm font-bold border border-sea {{ $loop->first ? 'rounded-l-md' : 'rounded-r-md -ml-px' }} {{ $filters['scope'] === $value ? 'bg-sea text-white' : 'bg-white text-sea hover:bg-tide' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            {{-- 条件検索（FN-03）。条件は URL に残す --}}
            <form method="GET" action="{{ route('trips.index') }}" class="bg-white rounded-md shadow-sm p-4 space-y-4">
                <input type="hidden" name="scope" value="{{ $filters['scope'] }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    @if ($filters['scope'] === 'public')
                    <div>
                        <x-input-label for="prefecture" value="県" />
                        <x-prefecture-select id="prefecture" name="prefecture" class="mt-1 block w-full"
                            :selected="$filters['prefecture']" :with-all="true" />
                    </div>
                    @endif
                    <div>
                        <x-input-label for="spot_id" value="釣り場" />
                        <x-option-select id="spot_id" name="spot_id" class="mt-1 block w-full"
                            :options="array_keys($spotLabels)" :labels="$spotLabels"
                            :selected="$filters['spot_id']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="species" value="魚種" />
                        <x-option-select id="species" name="species" class="mt-1 block w-full"
                            :options="config('fishing.fish_species')" :selected="$filters['species']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="method" value="釣り方" />
                        <x-option-select id="method" name="method" class="mt-1 block w-full"
                            :options="config('fishing.methods')" :selected="$filters['method']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="tide" value="潮" />
                        <x-option-select id="tide" name="tide" class="mt-1 block w-full"
                            :options="config('fishing.tides')" :selected="$filters['tide']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="weather" value="天候" />
                        <x-option-select id="weather" name="weather" class="mt-1 block w-full"
                            :options="config('fishing.weathers')" :selected="$filters['weather']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="time_of_day" value="時間帯" />
                        <x-option-select id="time_of_day" name="time_of_day" class="mt-1 block w-full"
                            :options="config('fishing.times_of_day')" :selected="$filters['time_of_day']" placeholder="指定なし" />
                    </div>
                    <div>
                        <x-input-label for="from" value="期間（から）" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from']" />
                    </div>
                    <div>
                        <x-input-label for="to" value="期間（まで）" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to']" />
                    </div>
                </div>
                @if ($filters['scope'] === 'public' && $filters['spot_id'])
                <p class="text-xs text-sand">釣り場を指定したときは、「釣り場だけ隠す」の釣行は出しません。</p>
                @endif
                <div class="flex flex-wrap items-center justify-end gap-4">
                    @if ($isFiltered)
                    <a href="{{ route('trips.index', ['scope' => $filters['scope']]) }}" class="text-sm underline text-sand">条件をクリア</a>
                    @endif
                    <x-primary-button>絞り込む</x-primary-button>
                </div>
            </form>

            {{-- 結果のまとめ：件数を隠さない --}}
            @if ($summary['visits'] > 0)
            <div class="bg-sea-50 rounded-md p-3 text-sm flex flex-wrap items-center justify-between gap-3">
                <p>
                    {{ $isFiltered ? 'この条件で' : '全部で' }}
                    <span class="font-bold">{{ $summary['visits'] }}回行って {{ $summary['caught'] }}回釣れた</span>
                    @if ($summary['methods']->isNotEmpty())
                    （@foreach ($summary['methods'] as $method => $fish){{ $method }} {{ $fish }}匹{{ ! $loop->last ? '・' : '' }}@endforeach）
                    @endif
                </p>
                {{-- CSV は自分のデータだけ。「みんな」のときは出さない（FN-04） --}}
                @if ($filters['scope'] === 'mine')
                <a href="{{ route('trips.export', request()->except(['page', 'scope', 'prefecture'])) }}"
                    class="inline-flex w-full sm:w-auto justify-center items-center px-4 py-2 bg-white border border-sea rounded-md font-bold text-sea hover:bg-tide">
                    CSV で保存
                </a>
                @endif
            </div>
            @endif

            @if ($trips->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                @if ($isFiltered)
                <p>この条件の釣行はありません。</p>
                @elseif ($filters['scope'] === 'public')
                <p>この県の公開の釣行は、まだありません。</p>
                @else
                <p>まだ釣行の記録がありません。</p>
                <a href="{{ route('trips.create') }}" class="underline text-sea">最初の釣行を記録する</a>
                @endif
            </div>
            @elseif ($filters['scope'] === 'public')
            {{-- みんな：フィードと同じカード。「釣り場だけ隠す」は釣り場名を伏せる --}}
            @foreach ($trips as $trip)
            @include('trips.partials.feed-card', ['trip' => $trip])
            @endforeach
            @else
            <ul class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
                @foreach ($trips as $trip)
                <li>
                    <a href="{{ route('trips.show', $trip) }}" class="flex items-start justify-between gap-4 p-4 hover:bg-tide">
                        <div class="min-w-0">
                            <p class="font-bold">
                                {{ $trip->went_at->format('Y/m/d') }}
                                <span class="ml-2 text-sm font-normal">{{ $trip->time_of_day }}</span>
                            </p>
                            <p class="text-sm text-sand">{{ $trip->spot->name }}（{{ $trip->spot->prefecture }}）・{{ $trip->tide }}</p>
                        </div>
                        <div class="text-sm text-right shrink-0">
                            @if ($trip->catches->isEmpty())
                            <p>坊主</p>
                            @else
                            <p class="font-bold">{{ $trip->catches->count() }}匹</p>
                            <p class="text-sand">{{ $trip->catches->pluck('fish_species')->unique()->take(3)->join('・') }}</p>
                            @endif
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
            @endif

            {{ $trips->links() }}
        </div>
    </div>
</x-app-layout>
