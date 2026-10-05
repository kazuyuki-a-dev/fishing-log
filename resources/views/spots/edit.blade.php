<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">
            {{ $canEditBasic ? '釣り場を編集' : '現地の情報を更新' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('spots.update', $spot) }}" class="crayon-card p-6 space-y-6">
                @csrf
                @method('PUT')

                @include('spots.partials.form', ['spot' => $spot, 'canEditBasic' => $canEditBasic])

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('spots.show', $spot) }}" class="text-sm underline text-sand">キャンセル</a>
                    <x-primary-button>更新する</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>