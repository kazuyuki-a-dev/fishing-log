{{--
    不適切な投稿の報告（PG21・NF-04）
    使い方：@include('reports.partials.modal', ['field' => 'trip_id', 'targetId' => $trip->id, 'targetLabel' => 'この釣行'])
    field は trip_id か spot_id。報告してよいかは、呼ぶ側の @can とサーバー側の両方で確かめる
--}}
@php
// 入力ミスで戻ってきたときは、同じ対象のモーダルを開いたままにする
$reopen = $errors->report->isNotEmpty() && (string) old($field) === (string) $targetId;
@endphp
<div class="text-right">
    <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'report-post')"
        class="text-xs underline text-sand hover:text-ink">
        {{ $targetLabel }}を報告する
    </button>
</div>

<x-modal name="report-post" :show="$reopen" focusable>
    <form method="POST" action="{{ route('reports.store') }}" class="p-6 space-y-4">
        @csrf
        <input type="hidden" name="{{ $field }}" value="{{ $targetId }}">

        <div>
            <h2 class="text-lg font-bold">{{ $targetLabel }}を報告する</h2>
            <p class="mt-1 text-sm text-sand">不適切な内容や、間違った情報を見つけたら教えてください。報告した人の名前は、投稿した人には伝わりません。</p>
        </div>

        <fieldset>
            <legend class="text-sm font-bold">報告の理由</legend>
            <div class="mt-2 space-y-2">
                @foreach (config('fishing.report_reasons') as $reason)
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="reason" value="{{ $reason }}" @checked(old('reason')===$reason)
                        class="text-sea-500 focus:ring-sea-500">
                    {{ $reason }}
                </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->report->get('reason')" class="mt-2" />
        </fieldset>

        <div>
            <label for="report-detail" class="text-sm font-bold">詳しい内容（任意）</label>
            <textarea id="report-detail" name="detail" rows="3" maxlength="1000"
                class="mt-1 block w-full border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm text-sm">{{ old('detail') }}</textarea>
            <x-input-error :messages="$errors->report->get('detail')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">キャンセル</x-secondary-button>
            <x-danger-button>報告する</x-danger-button>
        </div>
    </form>
</x-modal>
