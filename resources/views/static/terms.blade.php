{{-- 利用規約（NF-05・PG23）。定義書の「書くこと」と、あとから増えた機能（公開範囲・報告）を書く（#118） --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">利用規約</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-sm">
            <p>FishingLog（このサービス）を使う前に読んでください。会員登録をすると、この規約に同意したことになります。</p>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">1. 投稿した内容の表示</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>公開した釣行の写真・魚種・サイズ・条件・釣り方と、公開した釣り場の情報は、このサービスの中で表示されます。ログインしていない人にも見えます。</li>
                    <li>釣行の公開範囲は、<b>全体公開</b>・<b>釣り場だけ隠す</b>（釣り場の名前と位置を隠す）・<b>非公開</b>（自分だけ）から選べます。最初は非公開です。</li>
                    <li>釣り場が非公開のときは、そこでの釣行を全体公開にしても「釣り場だけ隠す」として表示されます。</li>
                    <li>ほかの人に見えるのはニックネームだけです。ニックネームに本名を入れないでください。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">2. 投稿してはいけないもの</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>釣り禁止・立入禁止の場所や私有地を、釣り場として投稿しないでください。</li>
                    <li>ほかの人の権利（写真の著作権・肖像など）をおかすもの、人を傷つけるもの、うその情報を投稿しないでください。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">3. 現地の情報と安全</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>釣り場の現地の情報（注意区分・駐車場・トイレなど）は、利用する人たちが入れる参考情報です。ほかのユーザーも編集できます。</li>
                    <li>現地では、表示や決まりを守ってください。安全と法律を守る責任は、利用する人自身にあります。</li>
                    <li>港の中は、立入禁止・釣り禁止の場所が多いです。釣りができる場所は、現地の表示や港の管理者の案内で確かめてください。</li>
                    <li>潮・天気・釣果の傾向は、記録や外部のデータから計算した目安です。釣れることや安全を約束するものではありません。</li>
                </ul>
            </section>

            <section class="crayon-card p-5 space-y-2">
                <h3 class="crayon-subheading">4. 報告と管理</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>不適切な投稿や、間違った釣り場の情報を見つけたら、釣行詳細やカルテの「報告する」から知らせてください。</li>
                    <li>管理者は報告を確かめ、この規約に合わない投稿を非公開にすることがあります。</li>
                </ul>
            </section>

            <p class="text-xs text-sand text-right">2026年10月5日</p>
        </div>
    </div>
</x-app-layout>
