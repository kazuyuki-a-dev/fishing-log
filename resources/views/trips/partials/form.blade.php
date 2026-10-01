{{-- 釣行フォーム（登録 PG12・編集 PG13 で共用）。$trip は編集のときだけ入る --}}
@php
$trip = $trip ?? null;

// 釣果の最初の行：入力エラーで戻ったときは入力した値、編集のときは今の釣果
$initialCatches = old('catches', $trip?->catches->map(function ($catch) {
$isListed = in_array($catch->fish_species, config('fishing.fish_species'), true);

return [
'fish_species' => $isListed ? $catch->fish_species : 'その他',
'fish_species_other' => $isListed ? '' : $catch->fish_species,
'method' => $catch->method,
'method_detail' => $catch->method_detail,
'length_cm' => $catch->length_cm,
'weight_g' => $catch->weight_g,
'keep_photo' => $catch->image_path,
];
})->all() ?? []);
$catchRows = array_values($initialCatches);
$nearbyUrl = route('spots.nearby');
@endphp

<div>
    <x-input-label for="spot_id" value="釣り場" />
    <select id="spot_id" name="spot_id" required
        class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
        <option value="">選択してください</option>
        @foreach ($spots as $spot)
        <option value="{{ $spot->id }}" @selected((string) old('spot_id', $trip?->spot_id ?? $selectedSpotId ?? '') === (string) $spot->id)>
            {{ $spot->name }}（{{ $spot->prefecture }}）
        </option>
        @endforeach
    </select>
    <p class="mt-1 text-sm text-sand">
        一覧にない場合は <a href="{{ route('spots.create') }}" class="underline">釣り場を登録</a> してください。
    </p>
    <x-input-error :messages="$errors->get('spot_id')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="went_at" value="釣行日時" />
        <x-text-input id="went_at" name="went_at" type="datetime-local" class="mt-1 block w-full"
            :value="old('went_at', $trip?->went_at->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'))" required />
        <x-input-error :messages="$errors->get('went_at')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="time_of_day" value="時間帯" />
        <x-option-select id="time_of_day" name="time_of_day" class="mt-1 block w-full"
            :options="config('fishing.times_of_day')" :selected="old('time_of_day', $trip?->time_of_day)"
            placeholder="選択してください" required />
        <x-input-error :messages="$errors->get('time_of_day')" class="mt-2" />
    </div>
    <div>
        <x-input-label value="潮" />
        <p class="mt-2 text-sm text-sand">釣行日時から自動で決まります。</p>
    </div>
    <div>
        <x-input-label for="weather" value="天候" />
        <x-option-select id="weather" name="weather" class="mt-1 block w-full"
            :options="config('fishing.weathers')" :selected="old('weather', $trip?->weather)" placeholder="分からない" />
        <x-input-error :messages="$errors->get('weather')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="visibility" value="公開範囲" />
    <x-option-select id="visibility" name="visibility" class="mt-1 block w-full"
        :options="config('fishing.trip_visibility')" :labels="config('fishing.visibility_labels')"
        :selected="old('visibility', $trip?->visibility ?? 'private')" required />
    <p class="mt-1 text-sm text-sand">「釣り場だけ隠す」にすると、場所を伏せたまま、条件・釣り方・魚種・サイズだけを公開できます。</p>
    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
</div>

<div>
    <x-input-label for="notes" value="メモ（自分にだけ表示）" />
    <textarea id="notes" name="notes" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm"
        placeholder="坊主のときに試した釣り方など">{{ old('notes', $trip?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>

{{-- 釣果（Alpine.js で行を増やしたり減らしたりする） --}}
<div class="border-t border-gray-100 pt-6 space-y-4"
    x-data="catchRows(@js($catchRows), @js($nearbyUrl))">
    <h3 class="font-bold">釣果</h3>

    <p x-show="converting" class="text-sm text-sea">写真を変換しています…</p>
    <p x-show="photoMessage" x-text="photoMessage" class="text-sm text-sea"></p>
    <template x-if="hint">
        <div class="rounded-md border-l-4 border-float bg-white p-3 text-sm shadow-sm space-y-2">
            <p class="font-bold">写真から読み取った候補</p>
            <template x-if="hint.takenAt">
                <div class="flex flex-wrap items-center gap-3">
                    <span x-text="`撮影日時：${hint.takenAt.replace('T', ' ').replaceAll('-', '/')}`"></span>
                    <button type="button" @click="useTakenAt()" class="underline text-sea">釣行日時に入れる</button>
                </div>
            </template>
            <template x-for="spot in hint.spots" :key="spot.id">
                <div class="flex flex-wrap items-center gap-3">
                    <span x-text="`${spot.name}（約 ${spot.distance}m）で釣りましたか？`"></span>
                    <button type="button" @click="useSpot(spot.id)" class="underline text-sea">この釣り場を選ぶ</button>
                </div>
            </template>
        </div>
    </template>

    @if ($errors->any())
    <p class="text-sm text-sand">入力エラーで戻ったときは、写真をもう一度選んでください。</p>
    @endif

    @if ($errors->has('catches.*'))
    <ul class="text-sm text-red-600 space-y-1">
        @foreach ($errors->get('catches.*') as $messages)
        @foreach ($messages as $message)
        <li>{{ $message }}</li>
        @endforeach
        @endforeach
    </ul>
    @endif

    <template x-for="(row, i) in rows" :key="row.key">
        <div class="rounded-md border border-gray-200 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <p class="font-bold" x-text="`${i + 1}匹目`"></p>
                <button type="button" @click="remove(i)" class="text-sm underline text-sand">この魚を消す</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <select :name="`catches[${i}][fish_species]`" x-model="row.fish_species" required
                        class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                        <option value="">魚種を選ぶ</option>
                        @foreach (config('fishing.fish_species') as $fish)
                        <option value="{{ $fish }}">{{ $fish }}</option>
                        @endforeach
                        <option value="その他">その他</option>
                    </select>
                    <input x-show="row.fish_species === 'その他'" type="text"
                        :name="`catches[${i}][fish_species_other]`" x-model="row.fish_species_other"
                        placeholder="魚の名前"
                        class="mt-2 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                </div>

                <div class="flex items-center gap-6">
                    @foreach (config('fishing.methods') as $method)
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" :name="`catches[${i}][method]`" value="{{ $method }}"
                            x-model="row.method" required class="text-sea-600 focus:ring-sea-500">
                        {{ $method }}
                    </label>
                    @endforeach
                </div>

                <input type="number" step="0.1" min="0" :name="`catches[${i}][length_cm]`"
                    x-model="row.length_cm" placeholder="サイズ（cm）"
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                <input type="number" min="0" :name="`catches[${i}][weight_g]`"
                    x-model="row.weight_g" placeholder="重さ（g）"
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
            </div>

            <input type="text" :name="`catches[${i}][method_detail]`" x-model="row.method_detail"
                placeholder="仕掛け・ヒットしたルアー・エサの種類など"
                class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
            {{-- 写真（任意）。編集のときは今の写真を引き継げる --}}
            <div class="space-y-2">
                <template x-if="row.keep_photo">
                    <div class="flex items-center gap-3">
                        <img :src="`{{ asset('storage') }}/${row.keep_photo}`" alt="今の写真"
                            class="h-20 w-20 rounded-md object-cover">
                        <button type="button" @click="row.keep_photo = ''" class="text-sm underline text-sand">この写真を外す</button>
                    </div>
                </template>
                <input type="hidden" :name="`catches[${i}][keep_photo]`" :value="row.keep_photo ?? ''">
                <label class="block text-sm text-sand">
                    <span x-text="row.keep_photo ? '写真を入れ替える（任意）' : '写真（任意）'"></span>
                    <input type="file" :name="`catches[${i}][photo]`"
                        accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif"
                        @change="pickPhoto($event)"
                        class="mt-1 block w-full text-sm text-ink">
                </label>
            </div>
        </div>
    </template>

    <p x-show="rows.length === 0" class="text-sm text-sand">
        釣果がなければ、このまま保存すると「坊主」として記録されます。
    </p>

    <button type="button" @click="add()"
        class="w-full rounded-md border-2 border-dashed border-sea-100 py-3 text-sm font-bold text-sea hover:bg-sea-50">
        ＋ 釣れた魚を追加
    </button>
</div>