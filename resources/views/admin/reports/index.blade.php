<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">報告の管理</h2>
    </x-slot>

    @php
    $statusLabels = config('fishing.report_statuses');
    $filterLabels = $statusLabels + ['all' => 'すべて'];
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
            <p class="bg-sea-50 text-sea rounded-md p-3 text-sm">{{ session('status') }}</p>
            @endif

            {{-- 対応状況の切り替え。最初は「未対応」 --}}
            <nav class="flex flex-wrap gap-2 text-sm">
                @foreach ($filterLabels as $key => $label)
                @php
                $count = $key === 'all' ? $statusCounts->sum() : ($statusCounts[$key] ?? 0);
                @endphp
                <a href="{{ route('admin.reports.index', ['status' => $key]) }}"
                    class="font-hand px-3 py-1.5 rounded-md border {{ $filter === $key ? 'bg-sea text-white border-sea font-bold' : 'bg-white border-gray-300 text-ink hover:bg-tide' }}">
                    {{ $label }}（{{ $count }}）
                </a>
                @endforeach
            </nav>

            @forelse ($reports as $report)
            @php
            $isTrip = $report->trip_id !== null;
            $total = $isTrip ? $tripCounts[$report->trip_id] : $spotCounts[$report->spot_id];
            $alreadyHidden = $isTrip ? $report->trip->visibility === 'private' : $report->spot->visibility === 'private';
            @endphp
            <article class="crayon-card p-5 space-y-4">
                {{-- 報告の中身 --}}
                <div class="space-y-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-bold">
                            <span class="text-sand font-normal">#{{ $report->id }}</span>
                            {{ $isTrip ? '釣行' : '釣り場' }}への報告：{{ $report->reason }}
                        </p>
                        <span class="text-xs px-2 py-0.5 rounded {{ $report->status === 'open' ? 'bg-float text-white font-bold' : 'bg-tide text-ink' }}">
                            {{ $statusLabels[$report->status] }}
                        </span>
                    </div>
                    <p class="text-xs text-sand">
                        {{ $report->created_at->format('Y/m/d H:i') }}・報告した人：{{ $report->reporter->name }}
                        ・この投稿への報告は全部で {{ $total }}件
                    </p>
                    @if ($report->detail)
                    <p class="text-sm whitespace-pre-line">{{ $report->detail }}</p>
                    @endif
                </div>

                {{-- 報告された投稿（ほかの人に見えている項目だけ。本人にだけ見せるメモは出さない） --}}
                <div class="rounded-md border border-gray-200 p-4 text-sm">
                    @if ($isTrip)
                    @include('admin.reports.partials.trip', ['trip' => $report->trip])
                    @else
                    @include('admin.reports.partials.spot', ['spot' => $report->spot])
                    @endif
                </div>

                {{-- 操作 --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label for="status-{{ $report->id }}" class="sr-only">対応状況</label>
                        <x-option-select id="status-{{ $report->id }}" name="status" class="text-sm"
                            :options="array_keys($statusLabels)" :labels="$statusLabels" :selected="$report->status" />
                        <x-secondary-button type="submit">変更</x-secondary-button>
                    </form>

                    @if ($alreadyHidden)
                    <p class="text-sm text-sand">非公開になっています</p>
                    @else
                    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'hide-report-{{ $report->id }}')">
                        非表示にする
                    </x-danger-button>
                    @endif
                </div>

                @unless ($alreadyHidden)
                <x-modal name="hide-report-{{ $report->id }}" focusable>
                    <form method="POST" action="{{ route('admin.reports.hide', $report) }}" class="p-6">
                        @csrf
                        <h2 class="text-lg font-bold">この{{ $isTrip ? '釣行' : '釣り場' }}を非表示にしますか？</h2>
                        <p class="mt-2 text-sm text-sand">
                            公開範囲を「非公開」にします。ほかの人の画面（フィード・検索・カルテなど）から見えなくなります。
                            投稿した人は自分の画面で見られ、公開に戻すこともできます。
                            この投稿への報告は、まとめて「対応完了」になります。
                        </p>
                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')">キャンセル</x-secondary-button>
                            <x-danger-button>非表示にする</x-danger-button>
                        </div>
                    </form>
                </x-modal>
                @endunless
            </article>
            @empty
            <p class="crayon-card p-6 text-center text-sm">{{ $filterLabels[$filter] }}の報告はありません。</p>
            @endforelse

            {{ $reports->links() }}
        </div>
    </div>
</x-app-layout>
