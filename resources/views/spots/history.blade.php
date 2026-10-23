<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">現地の情報の変更の履歴</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="crayon-note p-3 text-sm">{{ session('status') }}</p>
            @endif

            <section class="crayon-card p-5">
                <h3 class="crayon-subheading">{{ $spot->name }}</h3>
                <p class="mt-2 text-sm text-sand">
                    現地の情報は、この釣り場を見られる人みんなで直せます。だれが・いつ・どの項目を・何から何に変えたかを、新しい順に並べています。まちがいに気づいたら、現地の情報を直すか、カルテから報告してください。
                </p>

                @if ($changes->isEmpty())
                <p class="mt-4 crayon-empty p-4 text-sm">まだ変更の履歴はありません。</p>
                @else
                <div class="mt-4">
                    @include('spots.partials.local-info-changes', ['changes' => $changes])
                </div>
                <div class="mt-4">
                    {{ $changes->links() }}
                </div>
                @endif
            </section>

            <div class="text-right">
                <a href="{{ route('spots.show', $spot) }}" class="text-sm underline text-sea">カルテに戻る</a>
            </div>
        </div>
    </div>
</x-app-layout>
