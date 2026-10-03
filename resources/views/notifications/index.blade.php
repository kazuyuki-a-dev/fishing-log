<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">お知らせ</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @unless ($notifyEnabled)
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">
                お知らせは今 OFF です。新しいお知らせは届きません。
                <a href="{{ route('profile.edit') }}" class="underline">プロフィールで ON にする</a>
            </p>
            @endunless

            @if ($items->isEmpty())
            <div class="bg-white rounded-md shadow-sm p-6 text-center space-y-3">
                <p>まだお知らせはありません。</p>
                <p class="text-sm text-sand">メインフィールドの県で、釣果や釣り場が公開されるとここに届きます。</p>
                <a href="{{ route('feed') }}" class="underline text-sea">釣果フィードを見る</a>
            </div>
            @else
            <ul class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
                @foreach ($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-4 p-4 hover:bg-tide">
                        <p class="min-w-0">
                            @if (in_array($item['notification']->id, $newIds, true))
                            <span class="inline-block mr-2 px-2 py-0.5 rounded bg-float text-white text-xs font-bold">NEW</span>
                            @endif
                            {{ $item['text'] }}
                        </p>
                        <p class="text-sm text-sand shrink-0">{{ $item['notification']->created_at->format('Y/m/d H:i') }}</p>
                    </a>
                </li>
                @endforeach
            </ul>
            @endif

            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
