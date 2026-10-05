{{-- 仮の本文。公開前に内容を見直す --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">プライバシーポリシー</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="crayon-card p-6">
                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700">
                    <li>取得する情報：メールアドレス、ニックネーム、都道府県、釣行の位置情報</li>
                    <li>使いみち：ログイン、釣行の記録と集計、都道府県での絞り込みなど、このサービスを提供するため</li>
                    <li>公開する範囲：メールアドレスと住んでいる都道府県は公開しません。位置は小数第2位で丸めて公開します。</li>
                    <li>退会したとき：釣行と釣果は削除します。釣り場の情報は、登録した人の名前を外して残ります。</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>