<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">シーズンヒートマップ</h2>
    </x-slot>

    @php
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
            <div class="crayon-card p-6 text-center space-y-3">
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

            <x-season-table :rows="$rows" />
            @endif
        </div>
    </div>
</x-app-layout>
