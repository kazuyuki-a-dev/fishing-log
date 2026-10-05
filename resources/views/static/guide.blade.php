{{-- アプリの使い方（#116）。定義書の「使う流れ」の順に、画面とボタンの名前をそのまま使って書く --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">使い方</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="crayon-note p-4 text-sm space-y-2">
                <p>FishingLog は、釣行を記録していくと「どの潮・時間帯・釣り方なら、何回行って何回釣れたか」が貯まり、次にどこへ行くかを決める材料になるアプリです。</p>
                @guest
                <p>釣果フィードと釣り場カルテは、ログインしなくても見られます。記録やプランナーを使うには、<a href="{{ route('register') }}" class="underline text-sea">会員登録</a>してください。</p>
                @endguest
            </div>

            {{-- もくじ --}}
            <nav class="crayon-card p-5" aria-label="使い方のもくじ">
                <ol class="grid gap-1 text-sm sm:grid-cols-2 list-decimal pl-5">
                    <li><a href="#start" class="underline text-sea">はじめに</a></li>
                    <li><a href="#plan" class="underline text-sea">行く前に（プランナーとカルテ）</a></li>
                    <li><a href="#record" class="underline text-sea">記録する</a></li>
                    <li><a href="#review" class="underline text-sea">ふり返る</a></li>
                    <li><a href="#share" class="underline text-sea">みんなと使う</a></li>
                    <li><a href="#safety" class="underline text-sea">安全とマナー</a></li>
                </ol>
            </nav>

            <section id="start" class="crayon-card p-5 space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="notebook" class="h-5 w-5" />1. はじめに</h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li>「会員登録」で、ニックネーム・<b>メインフィールド</b>（よく行く都道府県）・メールアドレス・パスワードを入れ、利用規約に同意します。ほかの人に見えるのはニックネームだけです。</li>
                    <li>メインフィールドの県が、プランナーや釣果フィードの最初の県になります。あとから「プロフィール」で変えられます。</li>
                    <li>「プロフィール」で、同じ県の新しい釣果や釣り場のお知らせを受け取るかどうかを選べます。</li>
                </ol>
            </section>

            <section id="plan" class="crayon-card p-5 space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="moon" class="h-5 w-5" />2. 行く前に（プランナーとカルテ）</h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li>ダッシュボードの「次の釣行をプランする」か、付箋の「釣行プランナー」を開きます。</li>
                    <li><b>行く日</b>と<b>県</b>を選ぶと、その日の潮（旧暦から計算）と、過去の記録から釣り場が順に並びます。時間帯と「使う記録」（みんなの公開記録も使う／自分の記録だけ）も選べます。</li>
                    <li>釣り場ごとに、記録の近さが出ます。<b>ぴったり一致</b>（同じ潮と時間帯）→ <b>潮だけ一致</b> → <b>月だけ一致</b> の順に、近い記録ほど当てになります。</li>
                    <li>気になる釣り場を押すと<b>釣り場カルテ</b>が開きます。いちばん上の黄色の付箋（釣行判断ビュー）で、その条件のときに「何回行って何回釣れたか」と、釣れたときの釣り方・魚が分かります。</li>
                    <li>カルテには、天気予報・自分とほかの人の実績・月×魚種の表・釣行の履歴・現地の情報（駐車場・トイレなど）もあります。</li>
                </ol>
            </section>

            <section id="record" class="crayon-card p-5 space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="hook" class="h-5 w-5" />3. 記録する</h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li>「釣行を記録」（釣行の一覧やカルテの「この釣り場で釣行を記録」）を押します。釣り場が一覧になければ「釣り場を登録」から登録します。</li>
                    <li>釣った魚の写真を選ぶと、撮った日時と近くの釣り場を候補に出します。潮と天気は、日時と場所から自動で入ります。</li>
                    <li>魚ごとに、魚種・サイズ・釣り方（エサ／ルアー）を入れます。釣れなかった日（坊主）も、そのまま記録してください。「何回行って」の数に入ります。</li>
                    <li><b>公開範囲</b>を選びます。<b>全体公開</b>／<b>釣り場だけ隠す</b>（釣果は見せて、釣り場の名前と位置は隠す）／<b>非公開</b>（自分だけ）。最初は非公開です。</li>
                    <li>記録した直後に、その釣り場の現地の情報（駐車場・トイレなど）で空いている項目を1〜2問聞きます。分かるものだけ答えてください。</li>
                    <li>前に行った釣行は「昔の釣行をまとめて登録」で、まとめて入れられます。</li>
                </ol>
            </section>

            <section id="review" class="crayon-card p-5 space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="fish" class="h-5 w-5" />4. ふり返る</h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li><b>ダッシュボード</b>：累計の釣果・魚種・釣行した日・最大サイズ、今月の釣行と去年の同じ月、連続で記録した月数のスタンプが出ます。釣行が3件以上になると、「大潮のときに釣れているようです」などの<b>気づき</b>が出ます。</li>
                    <li><b>釣行の一覧</b>：釣り場・魚種・釣り方・潮・天候・時間帯・期間で「絞り込む」と、その条件で何回行って何回釣れたかが分かります。「CSV で保存」で、自分の記録を Excel で開けるファイルにできます。</li>
                    <li><b>シーズンヒートマップ</b>：ダッシュボードの「月ごとに釣れる魚を見る（シーズンヒートマップ）」から。県ごとに、月×魚種の釣れた数が色の濃さで分かります。</li>
                </ol>
            </section>

            <section id="share" class="crayon-card p-5 space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="lighthouse" class="h-5 w-5" />5. みんなと使う</h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li><b>釣果フィード</b>：みんなの公開釣果が新しい順に並びます。県や魚種で絞れます。</li>
                    <li><b>お知らせ</b>：メインフィールドの県で釣果や釣り場が公開されると、ベルに件数が出ます。</li>
                    <li><b>報告</b>：不適切な投稿や、間違った釣り場の情報（位置のまちがいなど）を見つけたら、釣行詳細やカルテのいちばん下の「この釣行を報告する」「この釣り場を報告する」から知らせてください。管理者が確かめます。</li>
                </ol>
            </section>

            <section id="safety" class="space-y-3 scroll-mt-32">
                <h3 class="crayon-subheading flex items-center gap-2"><x-icon name="flag" class="h-5 w-5" />6. 安全とマナー</h3>
                <div class="crayon-caution">
                    <span class="crayon-caution-icon" aria-hidden="true">!</span>
                    <ul class="list-disc pl-5 space-y-2 text-sm">
                        <li>釣り禁止・立入禁止の場所や私有地は、釣り場として登録しないでください。</li>
                        <li>カルテやプランナーの<b>注意区分</b>（注意あり・立入注意・私有地隣接）を確かめてください。港の中は立入禁止・釣り禁止の場所が多いので、釣りができる場所は現地の表示や港の管理者の案内で確かめてください。</li>
                        <li>現地の情報は、みんなで入れる参考情報です。安全と法律を守る責任は、利用する人自身にあります。</li>
                        <li>ほかの人には、釣り場の位置を約1km四方にぼかして見せています。正確な位置は、登録した本人にだけ見えます。写真の撮影場所の情報（Exif）は、保存するときに消しています。</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
