{{-- 仮の本文。公開前に内容を見直す --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">利用規約</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-md shadow-sm p-6">
                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700">
                    <li>投稿した写真と釣り場の情報は、このサービスの中で表示されます。ログインしていない人にも見えます。</li>
                    <li>釣り禁止・立入禁止の場所や私有地は、投稿しないでください。</li>
                    <li>現地の情報は参考情報です。安全と法律を守る責任は、利用する人自身にあります。</li>
                    <li>釣り場の現地の情報は、ほかのユーザーも編集できます。</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>