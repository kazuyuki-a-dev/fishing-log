<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">釣果フィード</h2>
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
            <div class="crayon-card p-6 text-center">
                <p>見たい都道府県を選んでください。</p>
            </div>
            @elseif ($trips->isEmpty())
            <div class="crayon-card p-6 text-center space-y-3">
                <p>この条件の公開釣果は、まだありません。</p>
                <a href="{{ route('trips.create') }}"
                    class="font-hand inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                    最初の投稿者になる
                </a>
            </div>

            @if ($spots->isNotEmpty())
            <section class="space-y-3">
                <h3 class="crayon-subheading">この県の釣り場の現地情報</h3>
                @foreach ($spots as $spot)
                <article class="crayon-card p-4 text-sm">
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
            @include('trips.partials.feed-card', ['trip' => $trip])
            @endforeach

            {{ $trips->links() }}
            @endif
        </div>
    </div>
</x-app-layout>