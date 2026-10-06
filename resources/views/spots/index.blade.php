<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">釣り場</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="crayon-note p-3 text-sm">{{ session('status') }}</p>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('spots.index') }}">
                    {{-- 県を変えても、並べ方はそのまま --}}
                    @auth
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @endauth
                    <x-prefecture-select name="prefecture" :selected="$prefecture" :with-all="true"
                        :placeholder="$prefecture === null" onchange="this.form.submit()" />
                </form>
                @auth
                <a href="{{ route('spots.create') }}"
                    class="crayon-button">
                    釣り場を登録
                </a>
                @endauth
            </div>
            {{-- 並べ替え（PG06）。自分の記録で並べるので、ログインしている人だけ。今選んでいるほうを濃くする --}}
            @auth
            @if ($prefecture !== null)
            @php
            $sorts = ['name' => '名前順', 'last' => '最後に行った日順', 'catches' => '釣果数順'];
            @endphp
            <div class="flex flex-wrap rounded-md shadow-sm w-fit" role="group" aria-label="並べ方">
                @foreach ($sorts as $value => $label)
                <a href="{{ route('spots.index', ['prefecture' => $prefecture, 'sort' => $value]) }}"
                    @if ($sort === $value) aria-current="true" @endif
                    class="font-hand px-4 py-2 text-sm font-bold border border-sea {{ $loop->first ? 'rounded-l-md' : '-ml-px' }} {{ $loop->last ? 'rounded-r-md' : '' }} {{ $sort === $value ? 'bg-sea text-white' : 'bg-white text-sea hover:bg-tide' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            @endif
            @endauth

            @if ($prefecture === null)
            <div class="crayon-card p-6 text-center">
                <p>見たい都道府県を選んでください。</p>
            </div>
            @else

            @if ($spots->isEmpty())
            <div class="crayon-card crayon-empty p-6 text-center space-y-3">
                <p>まだこの県には釣り場がありません。</p>
                <div class="flex justify-center gap-4 text-sm">
                    <a href="{{ route('spots.index', ['prefecture' => 'all']) }}" class="underline text-sea">全国を見る</a>
                    <a href="{{ route('spots.create') }}" class="underline text-sea">最初に登録する</a>
                </div>
            </div>
            @else
            <ul class="crayon-card divide-y divide-gray-100">
                @foreach ($spots as $spot)
                <li class="p-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-bold">
                            <a href="{{ route('spots.show', $spot) }}" class="hover:underline">{{ $spot->name }}</a>
                        </p>
                        <p class="text-sm text-sand">
                            {{ $spot->prefecture }}
                            @if ($spot->visibility === 'private')
                            ・非公開
                            @endif
                        </p>
                    </div>
                    <div class="text-sm text-right">
                        @auth
                        <p>自分の釣行 {{ $spot->my_trips_count }} 回・釣果 {{ $spot->my_catches_count }} 匹</p>
                        <p class="text-sand">
                            最後に行った日：{{ $spot->my_last_went_at ? \Illuminate\Support\Carbon::parse($spot->my_last_went_at)->format('Y/m/d') : 'まだありません' }}
                        </p>
                        <a href="{{ route('trips.create', ['spot' => $spot->id]) }}" class="mt-2 inline-block text-sm underline text-sea">ここで釣行を記録</a>
                        @endauth
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
            @endif
        </div>
    </div>
</x-app-layout>