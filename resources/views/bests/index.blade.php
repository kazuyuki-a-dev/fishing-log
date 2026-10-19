{{-- 魚種ごとの自己ベスト（#170）。写真つきのカードで、大きい順。押すとその釣行へ --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">自己ベスト</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-sand">魚種ごとに、いちばん大きい（長さ）釣果です。自分の記録から作っています。</p>

            @if ($bests->isEmpty())
            <div class="crayon-card crayon-empty p-6 text-center space-y-3">
                <p>サイズを入れた釣果がまだありません。釣果の長さ（cm）を記録すると、ここに自己ベストが並びます。</p>
                <a href="{{ route('trips.create') }}" class="crayon-button font-hand">釣行を記録</a>
            </div>
            @else
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($bests as $best)
                <li>
                    <a href="{{ route('trips.show', $best->trip_id) }}" class="crayon-card block overflow-hidden hover:opacity-90">
                        @if ($best->photoUrl())
                        <img src="{{ $best->photoUrl() }}" alt="{{ $best->fish_species }}の自己ベストの写真" loading="lazy"
                            class="aspect-[4/3] w-full object-cover">
                        @else
                        {{-- 写真がない釣果は、魚のアイコン --}}
                        <div class="aspect-[4/3] w-full grid place-items-center bg-sea-100/50">
                            <x-icon name="fish" class="!h-16 !w-16 text-[#2F86B5]" />
                        </div>
                        @endif
                        <div class="p-4 space-y-1">
                            <p class="crayon-scrawl">{{ $best->fish_species }}の自己ベスト</p>
                            <p class="text-2xl font-bold">{{ $best->length_cm }}<span class="ml-0.5 text-sm font-normal">cm</span></p>
                            <p class="text-xs text-sand">{{ $best->trip->went_at->format('Y/m/d') }}・{{ $best->trip->spot->name }}</p>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('dashboard') }}" class="text-sm underline text-sea">ダッシュボードに戻る</a>
            </div>
        </div>
    </div>
</x-app-layout>
