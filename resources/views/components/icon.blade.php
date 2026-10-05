{{--
    釣りのアイコン（#112）。<x-icon name="fish" class="h-4 w-4" /> のように名前で選ぶ
    - Tabler Icons（MIT ライセンス、https://tabler.io/icons）：fish・hook・lighthouse・notebook・moon・flag・bucket
    - 自分で描いたもの：worm（エサ）・lure（ルアー）
    線の色は文字の色（currentColor）。飾りなので読み上げソフトには読ませない（aria-hidden）
    読みやすさのため、アイコンにはクレヨンのざらつきをかけない
--}}
@props(['name'])

@php
$paths = [
'fish' => ['M16.69 7.44a6.973 6.973 0 0 0 -1.69 4.56c0 1.747 .64 3.345 1.699 4.571', 'M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25 -7.761 -12.285 -6.142 -20 2.504', 'M18 11v.01', 'M11.5 10.5c-.667 1 -.667 2 0 3'],
'hook' => ['M16 9v6a5 5 0 0 1 -10 0v-4l3 3', 'M14 7a2 2 0 1 0 4 0a2 2 0 1 0 -4 0', 'M16 5v-2'],
'lighthouse' => ['M12 3l2 3l2 15h-8l2 -15l2 -3', 'M8 9l8 0', 'M3 11l2 -2l-2 -2', 'M21 11l-2 -2l2 -2'],
'notebook' => ['M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18', 'M13 8l2 0', 'M13 12l2 0'],
'moon' => ['M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454l0 .008'],
'flag' => ['M5 5a5 5 0 0 1 7 0a5 5 0 0 0 7 0v9a5 5 0 0 1 -7 0a5 5 0 0 0 -7 0v-9', 'M5 21v-7'],
'bucket' => ['M4 7a8 4 0 1 0 16 0a8 4 0 1 0 -16 0', 'M4 7c0 .664 .088 1.324 .263 1.965l2.737 10.035c.5 1.5 2.239 2 5 2s4.5 -.5 5 -2c.333 -1 1.246 -4.345 2.737 -10.035a7.45 7.45 0 0 0 .263 -1.965'],
'worm' => ['M3 15c2 -5 4 3 6 -1s4 3 6 -1s3 2 6 -2', 'M20.5 10.5v.01'],
'lure' => ['M3 12c3 -4 9 -4 13 0c-4 4 -10 4 -13 0z', 'M16 12l4 -3v6z', 'M7 11.5v.01', 'M9 15v4l2 -1'],
];
@endphp

<svg {{ $attributes->merge(['class' => 'inline-block h-4 w-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @foreach ($paths[$name] as $d)
    <path d="{{ $d }}" />
    @endforeach
</svg>
