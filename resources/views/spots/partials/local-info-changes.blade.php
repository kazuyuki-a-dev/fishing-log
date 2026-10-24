{{-- 現地の情報の変更の履歴の一覧（#200）。カルテ（最新5件）と、履歴のページ（すべて）で使う --}}
{{-- $changes：SpotLocalInfoChange の一覧（user を読み込み済み） --}}
@php
$actionLabels = ['create' => '登録', 'edit' => '編集', 'revert' => '管理者が戻した'];
$canRevert = auth()->user()?->isAdmin() ?? false;
@endphp
<ol class="divide-y divide-dashed divide-gray-300 text-sm">
    @foreach ($changes as $change)
    <li class="py-2">
        <p class="text-xs text-sand">
            {{ $change->created_at->format('Y/m/d H:i') }}・{{ $change->user?->name ?? '退会したユーザー' }}・{{ $actionLabels[$change->action] ?? $change->action }}
        </p>
        @php
        $fieldLabel = \App\Services\LocalInfoHistory::FIELDS[$change->field] ?? $change->field;
        $oldText = \App\Services\LocalInfoHistory::display($change->field, $change->old_value);
        $newText = \App\Services\LocalInfoHistory::display($change->field, $change->new_value);
        $oldShort = \App\Services\LocalInfoHistory::short($change->field, $change->old_value);
        $newShort = \App\Services\LocalInfoHistory::short($change->field, $change->new_value);
        @endphp
        <p class="mt-0.5 break-words">
            <span class="font-bold">{{ $fieldLabel }}</span>：{{ $oldShort }} → {{ $newShort }}
        </p>
        {{-- 長いメモは一覧では短くし、押すと前と後の全文を見せる（#223） --}}
        @if ($oldShort !== $oldText || $newShort !== $newText)
        <details class="mt-1 text-xs">
            <summary class="w-fit cursor-pointer text-sea underline">全文を見る</summary>
            <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-2 gap-y-1">
                <dt class="text-sand">前</dt>
                <dd class="whitespace-pre-line break-words">{{ $oldText }}</dd>
                <dt class="text-sand">後</dt>
                <dd class="whitespace-pre-line break-words">{{ $newText }}</dd>
            </dl>
        </details>
        @endif
        @if ($canRevert)
        <form method="POST" action="{{ route('admin.spot-changes.revert', $change) }}" class="mt-1 text-right"
            x-data x-on:submit="if (! confirm('この変更の前の値に戻しますか？')) $event.preventDefault()">
            @csrf
            <button type="submit" class="text-xs underline text-sea">この変更の前に戻す</button>
        </form>
        @endif
    </li>
    @endforeach
</ol>
