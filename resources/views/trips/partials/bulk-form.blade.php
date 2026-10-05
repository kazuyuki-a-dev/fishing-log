{{-- 過去の釣行のまとめて登録（PG15）。1行＝釣った魚1匹。魚種が空の行は坊主 --}}
@php
$bulkRows = array_values(old('rows', []));
$spotIds = $spots->pluck('id')->all();
$nearbyUrl = route('spots.nearby');
$maxRows = \App\Http\Requests\BulkTripRequest::MAX_ROWS;
@endphp

<div class="crayon-note p-4 text-sm space-y-1">
    <p>釣った魚の写真をまとめて選ぶと、写真1枚につき1行ができます。撮影日時と近くの釣り場は、写真から読み取れたら最初から入れておきます。</p>
    <p>同じ日・同じ釣り場・同じ時間帯の行は、保存するときに1つの釣行にまとまります。魚種を「坊主」にした行は、釣れなかった釣行になります。</p>
    <p>潮と天候は自動で入ります。メモや天候の手直しは、あとで各釣行の編集からできます。</p>
</div>

<div>
    <x-input-label for="visibility" value="公開範囲（まとめて登録する釣行すべて）" />
    <x-option-select id="visibility" name="visibility" class="mt-1 block w-full"
        :options="config('fishing.trip_visibility')" :labels="config('fishing.visibility_labels')"
        :selected="old('visibility', 'private')" required />
    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
</div>

<div class="border-t border-gray-100 pt-6 space-y-4"
    x-data="bulkRows(@js($bulkRows), @js($nearbyUrl), @js($spotIds), @js($maxRows))">

    <label class="block rounded-md border-2 border-dashed border-sea-100 p-4 text-center cursor-pointer hover:bg-sea-50">
        <span class="font-bold text-sea">写真をまとめて選ぶ</span>
        <span class="block text-sm text-sand">{{ $maxRows }}枚まで。HEIC（iPhone の写真）も選べます</span>
        <input type="file" multiple class="hidden"
            accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif"
            @change="pickPhotos($event)">
    </label>

    <p x-show="converting" class="text-sm text-sea">写真を読み取っています…</p>
    <p x-show="message" x-text="message" class="text-sm text-sea"></p>

    @if ($errors->any())
    <p class="text-sm text-sand">入力エラーで戻ったときは、写真をもう一度選んでください（行の中身は残っています）。</p>
    @endif

    <x-input-error :messages="$errors->get('rows')" />
    @if ($errors->has('rows.*'))
    <ul class="text-sm text-red-600 space-y-1">
        @foreach ($errors->get('rows.*') as $messages)
        @foreach ($messages as $message)
        <li>{{ $message }}</li>
        @endforeach
        @endforeach
    </ul>
    @endif

    <template x-for="(row, i) in rows" :key="row.key">
        <div class="rounded-md border border-gray-200 p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <template x-if="row.preview">
                        <img :src="row.preview" alt="選んだ写真" class="h-16 w-16 rounded-md object-cover">
                    </template>
                    <p class="font-bold" x-text="`${i + 1}行目`"></p>
                </div>
                <button type="button" @click="remove(i)" class="text-sm underline text-sand">この行を消す</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="datetime-local" :name="`rows[${i}][went_at]`" x-model="row.went_at" required
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                <select :name="`rows[${i}][spot_id]`" x-model="row.spot_id" required
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                    <option value="">釣り場を選ぶ</option>
                    @foreach ($spots as $spot)
                    <option value="{{ $spot->id }}">{{ $spot->name }}（{{ $spot->prefecture }}）</option>
                    @endforeach
                </select>
                <select :name="`rows[${i}][time_of_day]`" x-model="row.time_of_day" required
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                    <option value="">時間帯を選ぶ</option>
                    @foreach (config('fishing.times_of_day') as $time)
                    <option value="{{ $time }}">{{ $time }}</option>
                    @endforeach
                </select>
            </div>
            <p x-show="row.spotNote" x-text="row.spotNote" class="text-sm text-sea"></p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <select :name="`rows[${i}][fish_species]`" x-model="row.fish_species"
                        class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                        <option value="">坊主（釣れなかった）</option>
                        @foreach (config('fishing.fish_species') as $fish)
                        <option value="{{ $fish }}">{{ $fish }}</option>
                        @endforeach
                        <option value="その他">その他</option>
                    </select>
                    <input x-show="row.fish_species === 'その他'" type="text"
                        :name="`rows[${i}][fish_species_other]`" x-model="row.fish_species_other"
                        placeholder="魚の名前"
                        class="mt-2 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
                </div>
                <div x-show="row.fish_species" class="flex items-center gap-6">
                    @foreach (config('fishing.methods') as $method)
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" :name="`rows[${i}][method]`" value="{{ $method }}"
                            x-model="row.method" :required="row.fish_species !== ''" class="text-sea-600 focus:ring-sea-500">
                        {{ $method }}
                    </label>
                    @endforeach
                </div>
                <input x-show="row.fish_species" type="number" step="0.1" min="0" :name="`rows[${i}][length_cm]`"
                    x-model="row.length_cm" placeholder="サイズ（cm）"
                    class="block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">
            </div>

            {{-- 選んだ写真は JavaScript でここに入れる（画面には出さない） --}}
            <input type="file" :name="`rows[${i}][photo]`" class="hidden" x-init="attachFile($el, row.key)">
        </div>
    </template>

    <p x-show="rows.length === 0" class="text-sm text-sand">
        まだ行がありません。写真を選ぶか、下のボタンで行を足してください。
    </p>

    <button type="button" @click="add()"
        class="w-full rounded-md border-2 border-dashed border-sea-100 py-3 text-sm font-bold text-sea hover:bg-sea-50">
        ＋ 写真なしで行を足す
    </button>
</div>