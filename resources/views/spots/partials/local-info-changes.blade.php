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
        <p class="mt-0.5 break-words">
            <span class="font-bold">{{ \App\Services\LocalInfoHistory::FIELDS[$change->field] ?? $change->field }}</span>：{{ \App\Services\LocalInfoHistory::display($change->field, $change->old_value) }} → {{ \App\Services\LocalInfoHistory::display($change->field, $change->new_value) }}
        </p>
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
