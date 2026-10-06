{{-- 単位変換ツール（#122）。どれか1つの欄に入れると、ほかの欄が自動で出る。計算はブラウザの中だけで、何も保存しない --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">単位変換</h2>
    </x-slot>

    @php
    $lineTypes = $units['line']['types'];
    @endphp

    <div class="py-8">
        <div x-data="unitConverter(@js($units))" class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm">海外の道具（oz・lb・ft・inch）と日本の道具（号・g・尺・cm）の単位を変換します。どれか1つの欄に数字を入れると、ほかの欄に答えが出ます。</p>

            {{-- 重さ：いったん g にしてから、ほかの単位に直す --}}
            <section class="crayon-card p-5 space-y-4">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="scale" class="h-5 w-5" />重さ（ルアー・おもり）</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($units['weight'] as $key => $unit)
                    <div>
                        <x-input-label for="weight-{{ $key }}" :value="$unit['label']" />
                        <x-text-input id="weight-{{ $key }}" type="number" inputmode="decimal" min="0" step="any"
                            class="mt-1 block w-full" x-model="weight.{{ $key }}"
                            x-on:input="convert('weight', '{{ $key }}', $event.target.value)" />
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-600">1 oz ＝ 28.35 g、1 lb ＝ 453.6 g、おもりの1号 ＝ 3.75 g（1匁）</p>
            </section>

            {{-- 長さ：いったん cm にしてから、ほかの単位に直す --}}
            <section class="crayon-card p-5 space-y-4">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="fish" class="h-5 w-5" />長さ（魚・竿・ライン）</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($units['length'] as $key => $unit)
                    <div>
                        <x-input-label for="length-{{ $key }}" :value="$unit['label']" />
                        <x-text-input id="length-{{ $key }}" type="number" inputmode="decimal" min="0" step="any"
                            class="mt-1 block w-full" x-model="length.{{ $key }}"
                            x-on:input="convert('length', '{{ $key }}', $event.target.value)" />
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-600">1 inch ＝ 2.54 cm、1 ft ＝ 30.48 cm、1 尺 ＝ 30.30 cm</p>
            </section>

            {{-- ライン：いったん lb にしてから、ほかの単位に直す。号と lb は種類ごとの目安 --}}
            <section class="crayon-card p-5 space-y-4">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="hook" class="h-5 w-5" />ライン（糸の太さと強さ）</h3>

                {{-- 種類の切り替え。今選んでいるほうを濃くする --}}
                <div class="flex flex-wrap rounded-md shadow-sm w-fit" role="group" aria-label="ラインの種類">
                    @foreach ($lineTypes as $key => $type)
                    <button type="button" @click="setLineType('{{ $key }}')"
                        :aria-pressed="lineType === '{{ $key }}'"
                        :class="lineType === '{{ $key }}' ? 'bg-sea text-white' : 'bg-white text-sea hover:bg-tide'"
                        class="font-hand px-4 py-2 text-sm font-bold border border-sea {{ $loop->first ? 'rounded-l-md' : '' }} {{ $loop->last ? 'rounded-r-md' : '' }} {{ $loop->first ? '' : '-ml-px' }}">
                        {{ $type['label'] }}
                    </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="line-gou" value="号" />
                        <x-text-input id="line-gou" type="number" inputmode="decimal" min="0" step="any"
                            class="mt-1 block w-full" x-model="line.gou"
                            x-on:input="convertLine('gou', $event.target.value)" />
                    </div>
                    <div>
                        <x-input-label for="line-lb" value="lb（ポンド）" />
                        <x-text-input id="line-lb" type="number" inputmode="decimal" min="0" step="any"
                            class="mt-1 block w-full" x-model="line.lb"
                            x-on:input="convertLine('lb', $event.target.value)" />
                    </div>
                    <div>
                        <x-input-label for="line-kg" value="kg（キロ）" />
                        <x-text-input id="line-kg" type="number" inputmode="decimal" min="0" step="any"
                            class="mt-1 block w-full" x-model="line.kg"
                            x-on:input="convertLine('kg', $event.target.value)" />
                    </div>
                </div>

                {{-- ナイロン・フロロは表の範囲だけ。外れたら号を出さずに知らせる --}}
                <p x-show="outOfTable" style="display: none" class="text-sm">
                    ナイロン・フロロの号は、目安の表がある 0.6〜20号（2〜80 lb）の間だけ出します。
                </p>

                {{-- PE・エステルの係数。使っている糸のパッケージに合わせると、より正確になる --}}
                @foreach ($lineTypes as $key => $type)
                @if (isset($type['coef']))
                <div x-show="lineType === '{{ $key }}'" style="display: none" class="space-y-1">
                    <x-input-label for="coef-{{ $key }}" value="係数（{{ $type['label'] }} の 1号が何 lb か）" />
                    <div class="flex items-center gap-3">
                        <x-text-input id="coef-{{ $key }}" type="number" inputmode="decimal"
                            min="{{ $type['coef']['min'] }}" max="{{ $type['coef']['max'] }}" step="{{ $type['coef']['step'] }}"
                            class="block w-28" x-bind:value="coef.{{ $key }}"
                            x-on:input="setCoef('{{ $key }}', $event.target.value)" />
                        <button type="button" class="text-sm underline text-sea" @click="resetCoef('{{ $key }}')">
                            はじめの値（{{ $type['coef']['default'] }}）に戻す
                        </button>
                    </div>
                    <p class="text-xs text-gray-600">lb ＝ 号 × 係数。{{ $type['coef']['min'] }}〜{{ $type['coef']['max'] }} の間で変えられます。パッケージの「1号 ＝ ○lb」に合わせてください。このブラウザに覚えておきます。</p>
                </div>
                @endif
                @endforeach

                <div class="crayon-note p-4 text-sm">
                    号と強さ（lb・kg）の関係は、メーカーや製品によって違います。ここで出るのは<b>大体の値</b>です。くわしくは糸のパッケージを見てください。lb と kg の変換はぴったりの計算です。
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
