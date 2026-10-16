{{-- 一致のハンコの色の説明（#158）。プランナーの一覧の上とカルテの判断ビューで使う --}}
<p {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-1 text-xs text-sand']) }}>
    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full border-[3px] border-double border-float-dark"></span>行く日と同じ</span>
    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full border-[3px] border-double border-sea"></span>違う（釣れたときに多かった値）</span>
</p>
