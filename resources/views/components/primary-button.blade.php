{{-- フォームの決定ボタン。ぬり絵風（crayon-button、#100） --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'crayon-button disabled:opacity-50']) }}>
    {{ $slot }}
</button>