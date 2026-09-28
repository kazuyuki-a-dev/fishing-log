<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">釣行を記録</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($spots->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>選べる釣り場がまだありません。先に釣り場を登録してください。</p>
                <a href="{{ route('spots.create') }}" class="underline text-sea">釣り場を登録する</a>
            </div>
            @else
            <form method="POST" action="{{ route('trips.store') }}" class="bg-white rounded-md shadow-sm p-6 space-y-6">
                @csrf

                @include('trips.partials.form')

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('trips.index') }}" class="text-sm underline text-sand">キャンセル</a>
                    <x-primary-button>記録する</x-primary-button>
                </div>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>