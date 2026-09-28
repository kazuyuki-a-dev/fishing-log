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

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif
            @if ($trips->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>まだ釣行の記録がありません。</p>
                <a href="{{ route('trips.create') }}" class="underline text-sea">最初の釣行を記録する</a>
            </div>
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

            {{ $trips->links() }}
            @endif
        </div>
    </div>
</x-app-layout>