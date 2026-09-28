{{-- 釣り場フォーム（登録 PG08・編集 PG09 で共用）
     $spot は編集のときだけ入る。$canEditBasic は釣り場名などを直してよいか --}}
@php
$spot = $spot ?? null;
@endphp

@if ($canEditBasic)
@unless ($spot)
<p class="text-sm bg-tide rounded-md p-3">
    釣り禁止・立入禁止の場所は、釣り場として登録できません。
</p>
@endunless

{{-- 基本の情報：登録した本人だけ（PG09） --}}
<div>
    <x-input-label for="name" value="釣り場名" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
        :value="old('name', $spot?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="prefecture" value="都道府県" />
    <x-prefecture-select id="prefecture" name="prefecture" class="mt-1 block w-full"
        :selected="old('prefecture', $spot?->prefecture ?? $defaultPrefecture ?? null)" required />
    <x-input-error :messages="$errors->get('prefecture')" class="mt-2" />
</div>

<div>
    <x-input-label for="visibility" value="公開設定" />
    <x-option-select id="visibility" name="visibility" class="mt-1 block w-full"
        :options="config('fishing.spot_visibility')" :labels="config('fishing.visibility_labels')"
        :selected="old('visibility', $spot?->visibility ?? 'private')" required />
    <p class="mt-1 text-sm text-sand">公開にすると、ほかのユーザーにも表示されます（位置はおおまかにしか出ません）。</p>
    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
</div>

<div>
    <x-input-label for="notes" value="メモ" />
    <textarea id="notes" name="notes" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">{{ old('notes', $spot?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
@else
{{-- 本人でなければ、基本の情報は見せるだけ（入力欄は出さない） --}}
<div class="rounded-md bg-tide p-4 text-sm space-y-1">
    <p class="font-bold">{{ $spot->name }}（{{ $spot->prefecture }}）</p>
    <p class="text-sand">釣り場名・都道府県・公開設定は、登録した人だけが変更できます。</p>
</div>
@endif

{{-- 現地の情報：見られる人みんなで更新できる（FN-14） --}}
<div class="{{ $canEditBasic ? 'border-t border-gray-100 pt-6' : '' }} space-y-6">
    <div>
        <h3 class="font-bold">現地の情報</h3>
        <p class="text-sm text-sand">
            @if ($spot)
            現地の情報は、この釣り場を見られる人みんなで更新できます。
            @else
            分かる範囲で入力してください。空欄でも登録できます。
            @endif
        </p>
    </div>

    <div>
        <x-input-label for="caution_type" value="注意区分" />
        <x-option-select id="caution_type" name="caution_type" class="mt-1 block w-full"
            :options="config('fishing.caution_types')" :selected="old('caution_type', $spot?->caution_type)" placeholder="選択しない" />
        <x-input-error :messages="$errors->get('caution_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="parking_type" value="駐車場" />
        <x-option-select id="parking_type" name="parking_type" class="mt-1 block w-full"
            :options="config('fishing.parking_types')" :selected="old('parking_type', $spot?->parking_type)" placeholder="選択しない" />
        <x-text-input name="parking_note" type="text" class="mt-2 block w-full"
            :value="old('parking_note', $spot?->parking_note)" placeholder="台数・料金・入口の場所など" />
        <x-input-error :messages="$errors->get('parking_type')" class="mt-2" />
        <x-input-error :messages="$errors->get('parking_note')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="toilet_available" value="トイレ" />
        <x-option-select id="toilet_available" name="toilet_available" class="mt-1 block w-full"
            :options="config('fishing.toilet_available')" :selected="old('toilet_available', $spot?->toilet_available)" placeholder="選択しない" />
        <x-text-input name="toilet_note" type="text" class="mt-2 block w-full"
            :value="old('toilet_note', $spot?->toilet_note)" placeholder="場所・使える時間など" />
        <x-input-error :messages="$errors->get('toilet_available')" class="mt-2" />
        <x-input-error :messages="$errors->get('toilet_note')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="convenience_distance_m" value="最寄りのコンビニまでの距離（メートル）" />
        <x-text-input id="convenience_distance_m" name="convenience_distance_m" type="number" min="0"
            class="mt-1 block w-full" :value="old('convenience_distance_m', $spot?->convenience_distance_m)" />
        <x-input-error :messages="$errors->get('convenience_distance_m')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="facility_note" value="現地情報のメモ" />
        <textarea id="facility_note" name="facility_note" rows="3"
            class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm"
            placeholder="足場・柵・夜の照明・電波・危ない場所など">{{ old('facility_note', $spot?->facility_note) }}</textarea>
        <x-input-error :messages="$errors->get('facility_note')" class="mt-2" />
    </div>
</div>