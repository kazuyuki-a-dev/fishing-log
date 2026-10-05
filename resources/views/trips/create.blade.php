<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">{{ $bulk ? '過去の釣行をまとめて登録' : '釣行を記録' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="{{ $bulk ? 'max-w-4xl' : 'max-w-2xl' }} mx-auto px-4 sm:px-6 lg:px-8">
            @if ($spots->isEmpty())
            <div class="crayon-card p-6 text-center space-y-3">
                <p>選べる釣り場がまだありません。先に釣り場を登録してください。</p>
                <a href="{{ route('spots.create') }}" class="underline text-sea">釣り場を登録する</a>
            </div>
            @elseif ($bulk)
            {{-- 過去の釣行のまとめて登録モード（PG15） --}}
            <form method="POST" action="{{ route('trips.bulk-store') }}" enctype="multipart/form-data" class="crayon-card p-6 space-y-6">
                @csrf

                @include('trips.partials.bulk-form')

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('trips.create') }}" class="text-sm underline text-sand">1件ずつ記録する</a>
                    <x-primary-button>まとめて登録する</x-primary-button>
                </div>
            </form>
            @else
            <p class="mb-4 text-right text-sm">
                <a href="{{ route('trips.create', ['mode' => 'bulk']) }}" class="underline text-sea">昔の釣行をまとめて登録する</a>
            </p>
            <form method="POST" action="{{ route('trips.store') }}" enctype="multipart/form-data" class="crayon-card p-6 space-y-6">
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