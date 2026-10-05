{{--
    クレヨンの線と塗りの SVG フィルター（見た目の土台）
    細かいノイズ（feTurbulence）を作り、それで線をゆらす（feDisplacementMap）ことで、クレヨンで描いたように見せる
    画面には何も出さない。CSS の filter: url(#crayon-line) / url(#crayon-fill) から使う
    レイアウトの <body> のすぐ下で1回だけ読み込む
--}}
<svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
    <filter id="crayon-line">
        <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="3" result="noise" />
        <feDisplacementMap in="SourceGraphic" in2="noise" scale="3.5" />
    </filter>
    <filter id="crayon-fill">
        <feTurbulence type="fractalNoise" baseFrequency="0.7" numOctaves="3" seed="8" result="noise" />
        <feDisplacementMap in="SourceGraphic" in2="noise" scale="5" result="moved" />
        <feComposite in="moved" in2="noise" operator="in" />
    </filter>
</svg>
