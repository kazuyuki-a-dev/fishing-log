<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">釣行を編集</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('trips.update', $trip) }}" enctype="multipart/form-data" class="bg-white rounded-md shadow-sm p-6 space-y-6">
                @csrf
                @method('PUT')

                @include('trips.partials.form', ['trip' => $trip])

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('trips.show', $trip) }}" class="text-sm underline text-sand">キャンセル</a>
                    <x-primary-button>更新する</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>