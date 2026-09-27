<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">釣り場</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('spots.index') }}">
                    <x-prefecture-select name="prefecture" :selected="$prefecture" :with-all="true"
                        onchange="this.form.submit()" />
                </form>
                <a href="{{ route('spots.create') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    釣り場を登録
                </a>
            </div>

            @if ($spots->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>まだこの県には釣り場がありません。</p>
                <div class="flex justify-center gap-4 text-sm">
                    <a href="{{ route('spots.index', ['prefecture' => 'all']) }}" class="underline text-sea">全国を見る</a>
                    <a href="{{ route('spots.create') }}" class="underline text-sea">最初に登録する</a>
                </div>
            </div>
            @else
            <ul class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
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
                        <p>自分の釣行 {{ $spot->my_trips_count }} 回</p>
                        <p class="text-sand">
                            最後に行った日：{{ $spot->my_last_went_at ? \Illuminate\Support\Carbon::parse($spot->my_last_went_at)->format('Y/m/d') : 'まだありません' }}
                        </p>
                        <a href="{{ route('trips.create', ['spot' => $spot->id]) }}" class="mt-2 inline-block text-sm underline text-sea">ここで釣行を記録</a>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
</x-app-layout>