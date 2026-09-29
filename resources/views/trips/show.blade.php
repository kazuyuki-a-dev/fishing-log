<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">
            {{ $trip->went_at->format('Y年n月j日') }}
            <span class="ml-2 text-base font-normal">{{ $trip->time_of_day }}</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif

            {{-- 釣果ハイライト（FN-07）。登録した直後だけ出る --}}
            @if (session('highlights'))
            <div class="rounded-md bg-float text-white p-4 shadow-sm space-y-1">
                @foreach (session('highlights') as $highlight)
                <p class="font-bold">{{ $highlight }}</p>
                @endforeach
            </div>
            @endif

            {{-- 現地の情報の質問（FN-14）。登録した直後だけ出る --}}
            @if ($localQuestions)
            @php
            $questionLabels = [
            'caution_type' => '注意区分',
            'parking_type' => '駐車場',
            'toilet_available' => 'トイレ',
            'convenience_distance_m' => '最寄りのコンビニまでの距離（メートル）',
            ];
            $questionOptions = [
            'caution_type' => config('fishing.caution_types'),
            'parking_type' => config('fishing.parking_types'),
            'toilet_available' => config('fishing.toilet_available'),
            ];
            @endphp
            <form method="POST" action="{{ route('spots.local-info', $trip->spot) }}"
                class="bg-white rounded-md shadow-sm p-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <p class="font-bold">{{ $trip->spot->name }}の現地の情報を教えてください</p>
                    <p class="text-sm text-sand">まだ誰も入力していない項目です。分かるものだけで大丈夫です。ほかの釣り人の役に立ちます。</p>
                </div>

                @foreach ($localQuestions as $field)
                <div>
                    <x-input-label :for="$field" :value="$questionLabels[$field]" />
                    @if ($field === 'convenience_distance_m')
                    <x-text-input :id="$field" :name="$field" type="number" min="0" class="mt-1 block w-full" />
                    @else
                    <x-option-select :id="$field" :name="$field" class="mt-1 block w-full"
                        :options="$questionOptions[$field]" placeholder="分からない" />
                    @endif
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
                @endforeach

                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button>送る</x-primary-button>
                    <span class="text-xs text-sand">答えなくても、このまま画面を離れれば大丈夫です。</span>
                </div>
            </form>
            @endif

            <section class="bg-white rounded-md shadow-sm p-5 space-y-3">
                <dl class="grid grid-cols-[6rem_1fr] gap-y-2 text-sm">
                    <dt class="text-sand">釣り場</dt>
                    <dd>
                        @if ($hideSpot)
                        {{-- 釣り場だけ隠す：釣り場名とリンクは出さない（FN-12） --}}
                        非公開（{{ $trip->spot->prefecture }}）
                        @else
                        <a href="{{ route('spots.show', $trip->spot) }}" class="underline text-sea">{{ $trip->spot->name }}</a>
                        （{{ $trip->spot->prefecture }}）
                        @endif
                    </dd>

                    <dt class="text-sand">潮</dt>
                    <dd>{{ $trip->tide ?? '不明' }}</dd>

                    <dt class="text-sand">天候</dt>
                    <dd>{{ $trip->weather ?? '不明' }}</dd>

                    @unless ($isOwner)
                    <dt class="text-sand">記録した人</dt>
                    <dd>{{ $trip->user->name }}</dd>
                    @endunless

                    @if ($isOwner)
                    <dt class="text-sand">公開範囲</dt>
                    <dd>
                        {{ config('fishing.visibility_labels')[$trip->visibility] }}
                        @if ($visibility !== $trip->visibility)
                        <span class="block text-xs text-sand">
                            釣り場が非公開のため、ほかの人には「{{ config('fishing.visibility_labels')[$visibility] }}」として表示されます。
                        </span>
                        @endif
                    </dd>
                    @endif
                </dl>

                @if ($isOwner && $trip->notes)
                <p class="border-t border-gray-100 pt-3 text-sm whitespace-pre-line">{{ $trip->notes }}</p>
                @endif
            </section>

            <section class="space-y-3">
                <h3 class="font-bold">釣果</h3>
                @forelse ($trip->catches as $catch)
                <article class="bg-white rounded-md shadow-sm p-4 text-sm space-y-1">
                    <p class="text-base font-bold">
                        {{ $catch->fish_species }}
                        @if ($catch->length_cm)
                        <span class="ml-2">{{ $catch->length_cm }} cm</span>
                        @endif
                        @if ($catch->weight_g)
                        <span class="ml-2 text-sm font-normal">{{ $catch->weight_g }} g</span>
                        @endif
                    </p>
                    <p class="text-sand">{{ $catch->method }}{{ $catch->method_detail ? '・' . $catch->method_detail : '' }}</p>
                    @if ($isOwner && $catch->notes)
                    <p class="whitespace-pre-line">{{ $catch->notes }}</p>
                    @endif
                    @if ($catch->photoUrl())
                    <img src="{{ $catch->photoUrl() }}" alt="{{ $catch->fish_species }}の写真" loading="lazy"
                        class="mt-2 max-h-80 w-full rounded-md object-cover">
                    @endif
                </article>
                @empty
                <p class="bg-white rounded-md shadow-sm p-4 text-sm">坊主</p>
                @endforelse
            </section>

            @if ($isOwner)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('trips.index') }}" class="text-sm underline text-sand">釣行の記録に戻る</a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('trips.edit', $trip) }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-bold text-sm text-ink shadow-sm hover:bg-tide">
                        編集する
                    </a>
                    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-trip-deletion')">
                        削除する
                    </x-danger-button>
                </div>
            </div>

            {{-- 削除の確認（PG14） --}}
            <x-modal name="confirm-trip-deletion" focusable>
                <form method="POST" action="{{ route('trips.destroy', $trip) }}" class="p-6">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-bold">この釣行を削除しますか？</h2>
                    <p class="mt-2 text-sm text-sand">
                        {{ $trip->went_at->format('Y年n月j日') }}の釣行と、
                        {{ $trip->catches->isEmpty() ? '坊主の記録' : '釣果 ' . $trip->catches->count() . ' 匹' }}が削除されます。元には戻せません。
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">キャンセル</x-secondary-button>
                        <x-danger-button>削除する</x-danger-button>
                    </div>
                </form>
            </x-modal>
            @endif
        </div>
    </div>
</x-app-layout>