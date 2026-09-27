<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">釣り場を登録</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('spots.store') }}" class="bg-white rounded-md shadow-sm p-6 space-y-6">
                @csrf

                <p class="text-sm bg-tide rounded-md p-3">
                    釣り禁止・立入禁止の場所は、釣り場として登録できません。
                </p>

                {{-- 基本の情報 --}}
                <div>
                    <x-input-label for="name" value="釣り場名" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="prefecture" value="都道府県" />
                    <x-prefecture-select id="prefecture" name="prefecture" class="mt-1 block w-full"
                        :selected="old('prefecture', $defaultPrefecture)" required />
                    <x-input-error :messages="$errors->get('prefecture')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="visibility" value="公開設定" />
                    <x-option-select id="visibility" name="visibility" class="mt-1 block w-full"
                        :options="config('fishing.spot_visibility')" :labels="config('fishing.visibility_labels')"
                        :selected="old('visibility', 'private')" required />
                    <p class="mt-1 text-sm text-sand">公開にすると、ほかのユーザーにも表示されます（位置はおおまかにしか出ません）。</p>
                    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                </div>

                {{-- 現地の情報（分かる範囲で。空欄でも登録できます） --}}
                <div class="border-t border-gray-100 pt-6 space-y-6">
                    <p class="text-sm text-sand">ここから下は、分かる範囲で入力してください。空欄でも登録できます。</p>

                    <div>
                        <x-input-label for="caution_type" value="注意区分" />
                        <x-option-select id="caution_type" name="caution_type" class="mt-1 block w-full"
                            :options="config('fishing.caution_types')" :selected="old('caution_type')" placeholder="選択しない" />
                        <x-input-error :messages="$errors->get('caution_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="parking_type" value="駐車場" />
                        <x-option-select id="parking_type" name="parking_type" class="mt-1 block w-full"
                            :options="config('fishing.parking_types')" :selected="old('parking_type')" placeholder="選択しない" />
                        <x-text-input name="parking_note" type="text" class="mt-2 block w-full"
                            :value="old('parking_note')" placeholder="台数・料金・入口の場所など" />
                        <x-input-error :messages="$errors->get('parking_type')" class="mt-2" />
                        <x-input-error :messages="$errors->get('parking_note')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="toilet_available" value="トイレ" />
                        <x-option-select id="toilet_available" name="toilet_available" class="mt-1 block w-full"
                            :options="config('fishing.toilet_available')" :selected="old('toilet_available')" placeholder="選択しない" />
                        <x-text-input name="toilet_note" type="text" class="mt-2 block w-full"
                            :value="old('toilet_note')" placeholder="場所・使える時間など" />
                        <x-input-error :messages="$errors->get('toilet_available')" class="mt-2" />
                        <x-input-error :messages="$errors->get('toilet_note')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="convenience_distance_m" value="最寄りのコンビニまでの距離（メートル）" />
                        <x-text-input id="convenience_distance_m" name="convenience_distance_m" type="number" min="0"
                            class="mt-1 block w-full" :value="old('convenience_distance_m')" />
                        <x-input-error :messages="$errors->get('convenience_distance_m')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="facility_note" value="現地情報のメモ" />
                        <textarea id="facility_note" name="facility_note" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm"
                            placeholder="足場・柵・夜の照明・電波・危ない場所など">{{ old('facility_note') }}</textarea>
                        <x-input-error :messages="$errors->get('facility_note')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="メモ" />
                        <textarea id="notes" name="notes" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('spots.index') }}" class="text-sm underline text-sand">キャンセル</a>
                    <x-primary-button>登録する</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>