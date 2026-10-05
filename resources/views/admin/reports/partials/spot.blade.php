{{-- 報告された釣り場（管理画面）。カルテでほかの人に見えている項目だけ。登録者のメモは出さない --}}
<dl class="grid grid-cols-[6rem_1fr] gap-y-1">
    <dt class="text-sand">釣り場</dt>
    <dd>
        @if ($spot->visibility === 'public')
        <a href="{{ route('spots.show', $spot) }}" class="underline text-sea">{{ $spot->name }}</a>
        @else
        {{ $spot->name }}
        @endif
        （{{ $spot->prefecture }}）
    </dd>

    <dt class="text-sand">登録した人</dt>
    <dd>{{ $spot->creator?->name ?? '退会したユーザー' }}</dd>

    <dt class="text-sand">注意区分</dt>
    <dd>{{ $spot->caution_type ?? '未入力' }}</dd>

    <dt class="text-sand">駐車場</dt>
    <dd>{{ $spot->parking_type ?? '未入力' }}{{ $spot->parking_note ? '（' . $spot->parking_note . '）' : '' }}</dd>

    <dt class="text-sand">トイレ</dt>
    <dd>{{ $spot->toilet_available ?? '未入力' }}{{ $spot->toilet_note ? '（' . $spot->toilet_note . '）' : '' }}</dd>

    <dt class="text-sand">コンビニ</dt>
    <dd>{{ $spot->convenience_distance_m !== null ? $spot->convenience_distance_m . ' m' : '未入力' }}</dd>

    <dt class="text-sand">設備のメモ</dt>
    <dd class="whitespace-pre-line">{{ $spot->facility_note ?? '未入力' }}</dd>
</dl>
