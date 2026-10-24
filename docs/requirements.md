# 要件ごとの対応表（要件トレーサビリティ表）

定義書（[`docs/spec.md`](spec.md)）の要件番号ごとに、**状態・受け入れの条件・確認方法・結果**をまとめた表です。
定義書と違うことをしたときの判断と理由は、[`docs/decisions.md`](decisions.md) に日付つきで記録しています（表の「備考」の欄は、その要約です）。

## 文書の情報

| 項目 | 内容 |
|---|---|
| 文書名 | FishingLog 要件ごとの対応表 |
| 版 | 1.7 |
| 作成日 | 2026-10-23 |
| 最終更新日 | 2026-10-23 |
| 作成者 | kazuyuki-a-dev |
| 対象 | 定義書 docs/spec.md の機能要件（FN）・非機能要件（NF）・画面（PG） |

## 改訂履歴

| 版 | 日付 | 変更内容 | Issue |
|---|---|---|---|
| 1.0 | 2026-10-23 | 初版（一覧の表と、要件ごとの詳しい内容に分けた形） | #198 |
| 1.1 | 2026-10-23 | FN-14 に現地の情報の変更の履歴（受け入れの条件 5）を足した。定義書にない画面に「変更の履歴」を足した | #200 |
| 1.2 | 2026-10-23 | FN-05 を完了にした（公開環境からパスワード再設定のメールを送れるようにし、送れないときの案内を足した） | #202 |
| 1.3 | 2026-10-23 | NF-03 を完了にした（公開環境の毎日の自動バックアップと、戻す試し） | #204 |
| 1.4 | 2026-10-23 | NF-04 の備考に運用の手順書を、FN-13・NF-07 の備考に本番リリース計画を書いた | #206 |
| 1.5 | 2026-10-24 | FN-16・PG22・NF-01 に、ゲストもプランナーを使えることを書いた | #209 |
| 1.6 | 2026-10-24 | FN-11・FN-16 に記録の内訳とダミーの回数を、FN-13・NF-07 にダミーの印を書いた | #211 |
| 1.7 | 2026-10-24 | FN-05 に会員登録のメールの確認を足した | #213 |

## 確認した環境

| 確認方法 | 環境 |
|---|---|
| 自動テスト | GitHub Actions（ubuntu-24.04、PHP 8.5.12、MySQL 8.4、Node 24）。push のたびに全部のテストを流す（`.github/workflows/tests.yml`） |
| 画面 | 開発環境（Laravel Sail、Chrome）。スマホ幅は Chrome の開発者ツールで 360px |

**結果の根拠**：この版の自動テストの結果は、GitHub Actions の実行の記録 [#40055523961](https://github.com/kazuyuki-a-dev/fishing-log/actions/runs/40055523961)（2026-10-24、394 件成功・失敗 0）。最新の結果は README のいちばん上のバッジと [Actions の画面](https://github.com/kazuyuki-a-dev/fishing-log/actions/workflows/tests.yml) で見られます（実行の画面の Summary に、成功・失敗の件数の表）。

## 見方

- **状態**
  - **完了**：受け入れの条件をすべて満たしている
  - **一部対応**：できていない部分がある（何が足りないかと、直す予定を書いている）
  - **未対応**：まだ作っていない
  - **お試し環境の制約**：機能はあるが、無料の公開環境（お試し環境）では使えない・止めているもの
- **確認方法**：「自動テスト」（テストのプログラムで確かめる）、「画面」（画面を開いて確かめる）、「自動テスト＋画面」、「手作業」（スクリプトなどを手で動かして確かめる）
- **テスト・手順**：`〇〇Test::test_〇〇` は自動テストの名前（`tests/Feature/` か `tests/Unit/` にある）。「画面」は確かめる手順
- **表の形**：区分ごとに、まず一覧の表（番号・要件・状態・確認方法・結果・確認日）があり、その下に要件ごとの詳しい内容（受け入れの条件・テスト・手順・結果・備考）があります。一覧の番号を押すと、詳しい内容に移ります
- **結果**：「成功（GitHub Actions）」「確認済み（どこで）」「一部成功：〇〇は未達」
- **確認日**：自動テストは、この版の GitHub Actions の実行日。画面は、確かめた日
- この文書に書いたテストの名前が本当にあるか、定義書の要件番号が全部そろっているか（一覧と詳しい内容の両方）、まとめの件数が合っているか、確認方法・結果・確認日が入っているかは、`RequirementsDocTest` で自動で確かめています
- 状態が変わったら、この表を直して版を上げ、改訂履歴に書きます

## まとめ

| 区分 | 完了 | 一部対応 | 未対応 | お試し環境の制約 |
|---|---|---|---|---|
| 機能要件（FN-01〜18） | 17 | 1 | 0 | 0 |
| 非機能要件（NF-01〜07） | 6 | 1 | 0 | 0 |
| 画面（PG01〜25） | 25 | 0 | 0 | 0 |

一部対応のもの：FN-13・NF-07（開発者自身の実際の釣行をまだ登録していない）。

## 機能要件（FN）

| 番号 | 要件 | 状態 | 確認方法 | 結果 | 確認日 |
|---|---|---|---|---|---|
| [FN-01](#fn-01) | 釣り場・釣行・釣果の登録・編集・削除 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-02](#fn-02) | シーズンヒートマップ | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-03](#fn-03) | 条件検索 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-04](#fn-04) | CSV 出力 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-05](#fn-05) | ユーザー認証とプロフィール | 完了 | 自動テスト＋画面 | 成功（GitHub Actions）。公開環境でも確認済み | 2026-10-24 |
| [FN-06](#fn-06) | サマリー指標ダッシュボード | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-07](#fn-07) | 釣果ハイライト | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-08](#fn-08) | 写真の Exif・天候・潮の自動補完 | 完了 | 自動テスト＋画面 | 成功（GitHub Actions） | 2026-10-22 |
| [FN-09](#fn-09) | 釣り場カルテ | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-10](#fn-10) | 継続支援表示 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-11](#fn-11) | 釣行判断ビュー | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-24 |
| [FN-12](#fn-12) | 公開範囲の3段階 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-13](#fn-13) | 公開釣果フィード | 一部対応 | 自動テスト | 一部成功 | 2026-10-24 |
| [FN-14](#fn-14) | 釣り場の現地情報 | 完了 | 自動テスト＋画面 | 成功（GitHub Actions） | 2026-10-23 |
| [FN-15](#fn-15) | 釣り方（エサ／ルアー）の記録 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-16](#fn-16) | 釣行プランナー（主役） | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-24 |
| [FN-17](#fn-17) | メインフィールドとエリア絞り込み | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [FN-18](#fn-18) | 県内新着のアプリ内お知らせ | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |

### FN-01

**釣り場・釣行・釣果の登録・編集・削除**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 釣り場・釣行・釣果を登録・編集・削除できる
2. ほかの人の公開の釣り場も選べ、非公開の釣り場は選べない
3. 魚種は20種＋その他から選ぶ
4. 釣果0件は坊主として記録できる
5. 編集・削除は本人だけ

テスト・手順

- `SpotStoreTest::test_user_can_register_a_spot`
- `TripStoreTest::test_user_can_record_a_trip_with_catches`
- `TripStoreTest::test_trip_without_catches_is_recorded_as_bozu`
- `TripStoreTest::test_others_private_spot_cannot_be_chosen`
- `BulkTripTest::test_others_public_spot_can_be_chosen`
- `TripUpdateTest::test_owner_can_update_trip_and_catches_are_replaced`
- `TripUpdateTest::test_owner_can_delete_trip_with_its_catches`
- `TripUpdateTest::test_others_cannot_open_edit_or_update`
- `TripUpdateTest::test_others_cannot_delete`

結果：成功（GitHub Actions）

備考：釣り場は県を選んでから選ぶ（全国138か所が並ぶため。#138・#142）

### FN-02

**シーズンヒートマップ**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 月×魚種の表で、多いマスほど濃い色
2. 自分／みんな（公開）を切り替えられる
3. みんなには非公開の釣行が入らない

テスト・手順

- `SeasonHeatmapTest::test_color_follows_fish_count_and_empty_months_are_white`
- `SeasonHeatmapTest::test_public_counts_public_and_spot_hidden_but_not_private`
- `SeasonHeatmapTest::test_mine_counts_own_private_trips_and_not_others`
- `SeasonHeatmapTest::test_page_can_switch_to_mine`

結果：成功（GitHub Actions）

備考：色は匹数、回数は小さく添える。1匹から色を付ける（#80）

### FN-03

**条件検索**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 釣り場・魚種・期間・潮・天候・時間帯・釣り方で絞り込め、組み合わせられる
2. 自分／みんなを切り替えられる
3. みんなでは「釣り場だけ隠す」の釣り場名を出さない
4. 時間帯は登録時に選んだ値を使う

テスト・手順

- `TripSearchTest::test_each_condition_narrows_the_list`
- `TripSearchTest::test_conditions_are_combined`
- `TripSearchTest::test_species_and_method_must_match_the_same_fish`
- `TripSearchTest::test_mine_lists_only_my_trips_including_private`
- `TripSearchTest::test_public_lists_public_and_spot_hidden_and_hides_spot_name`
- `TripSearchTest::test_public_with_spot_excludes_spot_hidden_trips`

結果：成功（GitHub Actions）

備考：条件に「県」を足した（#138）。魚種×釣り方は同じ1匹で判定（#86）

### FN-04

**CSV 出力**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 今の絞り込みのまま、自分のデータだけを出す（URL を書きかえても自分だけ）
2. ほかの人の記録を表示しているときはボタンを出さない
3. 位置と写真は入れない

テスト・手順

- `TripExportTest::test_only_my_trips_even_with_public_scope`
- `TripExportTest::test_filters_are_applied_and_only_matching_fish_are_rows`
- `TripExportTest::test_button_is_shown_only_for_mine_with_results`
- `TripExportTest::test_location_is_not_included`
- `TripExportTest::test_formula_like_text_is_made_safe_for_excel`
- `TripExportTest::test_guest_is_sent_to_login`

結果：成功（GitHub Actions）

備考：魚1匹＝1行、BOM 付き、式の対策（#88）

### FN-05

**ユーザー認証とプロフィール**

- 状態：完了　／　確認方法：自動テスト＋画面　／　確認日：2026-10-24

受け入れの条件

1. 会員登録・ログイン・ログアウト・パスワード再設定・プロフィール編集・退会ができる
2. 登録時にメインフィールドと規約への同意が必須
3. パスワード再設定のメールが届く（日本語の件名と本文、60分で切れる再設定のリンク）
4. メールを送れないときは、エラーの画面にせず、利用者に案内を出してログに残す
5. 会員登録とメールアドレスの変更のときに確認のメールを送り、確かめるまではログインが必要なページを使えない（プロフィールとゲストも見られるページは開ける。#213）

テスト・手順

- `RegistrationTest::test_new_users_can_register`
- `RegistrationTest::test_home_prefecture_is_required`
- `RegistrationTest::test_terms_must_be_accepted`
- `AuthenticationTest::test_users_can_authenticate_using_the_login_screen`
- `AuthenticationTest::test_users_can_logout`
- `PasswordResetTest::test_password_can_be_reset_with_valid_token`
- `ProfileTest::test_profile_information_can_be_updated`
- `ProfileTest::test_user_can_delete_their_account`
- `PasswordResetMailTest::test_reset_mail_is_in_japanese_with_the_reset_link`
- `PasswordResetMailTest::test_shows_a_message_instead_of_an_error_when_mail_cannot_be_sent`
- `PasswordResetMailTest::test_unknown_address_does_not_send_mail`
- `EmailVerificationRequiredTest::test_registration_sends_a_japanese_verification_mail`
- `EmailVerificationRequiredTest::test_unverified_member_cannot_use_member_pages`
- `EmailVerificationRequiredTest::test_unverified_member_can_still_see_public_pages_and_profile`
- `EmailVerificationRequiredTest::test_registration_still_succeeds_when_mail_cannot_be_sent`
- 画面（開発環境）：再設定を頼むと、Mailpit（http://localhost:8025）にメールが届く
- 画面（開発環境）：会員登録 → 確認の画面 → 記録の一覧を開くと確認の画面に戻る → Mailpit に「【FishingLog】メールアドレスの確認のお願い」が届く → ボタンを押すとダッシュボードが使える（2026-10-24）
- 画面（公開環境）：普段のメールアドレスで会員登録 → ログアウト → 「パスワードを忘れた方」から再設定を頼む → 受信箱に届く → メールのボタンから新しいパスワードを決める → 新しいパスワードでログインできる（確かめたあと、その会員は退会する）

結果：成功（GitHub Actions）。公開環境でも確認済み（2026-10-23、通常の受信箱に届き、再設定と新しいパスワードでのログインができた）

備考：公開環境は Brevo の SMTP（ポート 2525。Render の無料のプランは 587 番が止められているため）で送る。送り主はアプリ専用の Gmail で、自分のドメインではないので迷惑メールに入ることがある（本格的に使うときは自分のドメインに切りかえる）。開発環境は Mailpit で受け止める（#202）。会員登録のメールの確認は、打ちまちがいや他人のアドレスでの登録を防ぐために足した（定義書にない。#213）

### FN-06

**サマリー指標ダッシュボード**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 累計の釣果数・魚種の数・釣行した日数・最大サイズを出す
2. 記録が0件でも画面が空にならない

テスト・手順

- `DashboardTest::test_summary_counts_only_my_catches_species_and_days`
- `DashboardTest::test_new_user_sees_zeros_and_guidance`
- `PersonalBestsTest::test_dashboard_links_the_biggest_and_shows_top_three`

結果：成功（GitHub Actions）

備考：最大サイズから釣果へ移れるようにし、魚種ごとの自己ベストを足した（#170・#174）

### FN-07

**釣果ハイライト**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 登録した直後に「最大サイズを更新」「初めて釣った魚」「この釣り場で初めて」を判定して出す

テスト・手順

- `CatchHighlighterTest::test_first_catch_gets_all_three_highlights`
- `CatchHighlighterTest::test_only_my_records_are_compared`
- `CatchHighlighterTest::test_highlight_is_shown_right_after_recording`

結果：成功（GitHub Actions）

備考：坊主のときは、行った回数をそえて次につながる言葉を出す

### FN-08

**写真の Exif・天候・潮の自動補完**

- 状態：完了　／　確認方法：自動テスト＋画面　／　確認日：2026-10-22

受け入れの条件

1. 写真の撮影日時と位置を読み、近く（300m 以内）の公開・自分の釣り場を提案する
2. ほかの人の非公開の釣り場は提案しない
3. 保存時に Exif を消す
4. 潮は日時から計算、天候は外の API から（取れなければ手で選ぶ）
5. 写真なしでも登録でき、まとめて登録モードがある

テスト・手順

- `NearbySpotsTest::test_public_and_my_spots_nearby_are_suggested`
- `NearbySpotsTest::test_others_private_spot_is_not_suggested`
- `NearbySpotsTest::test_spots_farther_than_300m_are_not_suggested`
- `CatchPhotoTest::test_exif_is_removed_from_the_saved_photo`
- `TideCalculatorTest::test_tide_names_for_known_dates`
- `WeatherTest::test_empty_weather_is_filled_automatically`
- `WeatherTest::test_trip_is_saved_even_if_the_weather_service_fails`
- `BulkTripTest::test_rows_on_the_same_day_spot_and_time_become_one_trip`
- `PhotoHintsTest::test_trip_form_accepts_heic_and_reads_photo_hints`
- 画面：Exif（日時・位置）入りの写真を「釣行を記録」で選ぶと、日時が入り近くの釣り場が候補に出る（2026-10-22、秋田港の位置入りのテスト用写真で確認）

結果：成功（GitHub Actions）。画面も確認済み（開発環境、テスト用の写真）

備考：写真の読み取りはブラウザの中（ExifReader）で行う

### FN-09

**釣り場カルテ**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 釣行の履歴・釣れたときの条件と釣り方・最大サイズ・最後に行った日・現地の情報を1画面に出す
2. 「何回行って何回釣れた」を両方出す
3. 上部に判断ビューを置く

テスト・手順

- `SpotShowTest::test_my_record_shows_visits_and_catches_and_hides_emails`
- `SpotShowTest::test_only_others_public_trips_are_shown_on_a_public_spot`
- `SpotHistoryPageTest::test_history_is_shown_ten_trips_per_page`
- `SpotSeasonTableTest::test_table_uses_the_same_trips_as_the_history`

結果：成功（GitHub Actions）

備考：履歴は10件ずつ（記録が増えても重くならないように。#166）

### FN-10

**継続支援表示**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 今月の回数・連続月数・去年の同じ月との比較を出す
2. 釣行3件目から気づきカード（何件の記録からかも出す）
3. 表に「何件から作っているか」を出す

テスト・手順

- `KeepRecordingTest::test_counter_counts_this_month_streak_and_last_year`
- `KeepRecordingTest::test_streak_continues_until_this_month_ends`
- `KeepRecordingTest::test_insights_need_three_trips`
- `KeepRecordingTest::test_insight_with_enough_visits_is_stated_plainly`
- `SpotSeasonTableTest::test_shows_trip_count_and_message_when_empty`

結果：成功（GitHub Actions）

備考：「あと何件で表示」は「釣行 N 件から作っています」に変えた（1匹から色を付けるため。#80）。5回未満の気づきは「かもしれません」（#172）

### FN-11

**釣行判断ビュー**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-24

受け入れの条件

1. 選んだ日の潮・天気・時間帯を出す
2. その条件のときの過去の記録と一致を示す
3. 次の大潮を出す
4. 数えた記録の内訳（時間帯・潮ごとの行った回数・釣れた回数）と、そのうちダミーの回数を出す（#211）

テスト・手順

- `SpotJudgeTest::test_shows_tide_and_next_big_tide_for_the_chosen_date`
- `SpotJudgeTest::test_counts_only_trips_with_the_same_tide`
- `SpotJudgeTest::test_time_of_day_narrows_the_match`
- `SpotJudgeTest::test_relaxes_to_season_and_time_when_exact_has_no_record`
- `WeatherTest::test_karte_shows_forecast_and_caches_it`
- `DummyTripTest::test_karte_shows_dummy_count_and_breakdown`
- `DummyTripTest::test_breakdown_counts_only_visible_trips_in_order`

結果：成功（GitHub Actions）

備考：時期（前後1か月）をいつも条件に入れ、一致をハンコで見せる（#158）。天気予報は MET Norway（#146）。記録の内訳は表でいつも出し、時間帯で絞っていないときは記録がない時間帯も書く（#211）

### FN-12

**公開範囲の3段階**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 全体公開／釣り場だけ隠す／非公開（初期値は非公開）
2. 釣り場が非公開なら釣行は「釣り場だけ隠す」として扱う
3. 公開する位置は小数第2位で切り捨て、正確な位置は本人だけ
4. Exif を消す

テスト・手順

- `TripShowTest::test_private_trip_returns_404_to_others`
- `TripShowTest::test_spot_hidden_trip_does_not_contain_spot_name_or_link`
- `TripShowTest::test_public_trip_on_private_spot_is_treated_as_spot_hidden`
- `SpotLocationTest::test_others_see_only_the_rounded_location`
- `SpotLocationTest::test_location_is_cut_down_not_rounded_up`
- `SpotLocationTest::test_owner_sees_the_exact_location`
- `CatchPhotoTest::test_exif_is_removed_from_the_saved_photo`

結果：成功（GitHub Actions）

### FN-13

**公開釣果フィード**

- 状態：一部対応　／　確認方法：自動テスト　／　確認日：2026-10-24

受け入れの条件

1. 公開の釣行を新着順・魚種別に見られる（ゲストも）
2. 投稿者はニックネームだけ
3. 「釣り場だけ隠す」の釣り場名を出さない
4. 空のときは「最初の投稿者になる」導線
5. リリース時に開発者自身の過去の釣行を数件公開する

テスト・手順

- `FeedTest::test_only_public_and_spot_hidden_trips_are_listed`
- `FeedTest::test_spot_hidden_trip_does_not_contain_spot_name`
- `FeedTest::test_public_trip_on_private_spot_does_not_contain_spot_name`
- `FeedTest::test_member_sees_home_prefecture_by_default_and_no_emails`
- `FeedTest::test_guest_is_asked_to_choose_a_prefecture_first`
- `FeedTest::test_species_filter_shows_only_trips_with_that_fish`
- `FeedTest::test_empty_feed_shows_first_poster_button`
- `DummyTripTest::test_only_trips_of_dummy_people_are_marked_in_the_feed`

結果：一部成功（自動テストは成功。開発者自身の実際の釣行の登録は未達）

備考：**足りないこと**：⑤ の開発者自身の実際の釣行は、まだ登録していない（お試し環境はダミーの釣行840件で空にならないようにしている）。本番に移るときに登録する（[`docs/release-plan.md`](release-plan.md) の項目 10）。ダミーの釣行には、フィードなどで「ダミー」の印を付けた（#211）

### FN-14

**釣り場の現地情報**

- 状態：完了　／　確認方法：自動テスト＋画面　／　確認日：2026-10-23

受け入れの条件

1. 駐車場・トイレ・コンビニまでの距離・注意区分とメモを記録できる
2. 登録した直後に空欄を1〜2問だけ聞く
3. 全員が編集でき、最後に更新した人と日付を出す
4. 基本の情報（名前・位置）は登録した本人だけ
5. 現地の情報の変更の履歴（だれが・いつ・どの項目を・何から何に）を残し、ログインした会員が見られ、管理者が前の値に戻せる（定義書にない追加。#200）

テスト・手順

- `SpotUpdateTest::test_owner_can_update_basic_info_and_local_info`
- `SpotUpdateTest::test_others_can_update_only_local_info_even_if_they_send_basic_info`
- `LocalInfoQuestionsTest::test_first_two_empty_items_are_asked_right_after_recording`
- `LocalInfoQuestionsTest::test_answers_are_saved_without_erasing_other_items`
- `LocalInfoHistoryTest::test_only_changed_items_are_recorded_one_row_each`
- `LocalInfoHistoryTest::test_basic_info_and_private_memo_are_not_recorded`
- `LocalInfoHistoryTest::test_member_sees_the_history_on_the_karte_but_guest_does_not`
- `LocalInfoHistoryTest::test_history_of_others_private_spot_is_not_found`
- `LocalInfoHistoryTest::test_admin_can_revert_a_change_and_the_revert_is_recorded`
- `LocalInfoHistoryTest::test_only_admin_can_revert`
- 画面：会員で現地の情報を2回直し、カルテの「変更の履歴」と「すべての履歴を見る」を確かめる。ログアウトすると出ないこと。admin@example.com で「この変更の前に戻す」を押すと値が戻ること

結果：成功（GitHub Actions）。画面も確認済み（開発環境）

備考：変更の履歴は会員に見せ、元に戻すのは管理者だけ（#200）

### FN-15

**釣り方（エサ／ルアー）の記録**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 魚1匹ごとにエサかルアーかを必ず選び、細かい内容は備考に書ける

テスト・手順

- `TripStoreTest::test_method_is_required_for_each_catch`
- `BulkTripTest::test_method_is_required_when_species_is_chosen`

結果：成功（GitHub Actions）

### FN-16

**釣行プランナー（主役）**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-24

受け入れの条件

1. 日付を選ぶとその日の潮を出す
2. メインフィールドの県の釣り場を、同じ条件で釣れた記録が多い順に並べる（県は切り替えられる）
3. よく釣れた時間帯・釣り方・魚種・最大サイズと「何回行って何回釣れた」を出す
4. 注意区分の警告
5. 条件を少しずつゆるめ、どの一致かを示す
6. ゲストも使える。数えるのはみんなの全体公開の記録だけで、県は選んでもらう（#209。定義書と違う）
7. 数えた記録の内訳（時間帯・潮・年）と、そのうちダミーの回数を出す（内訳は押すと開く。#211）

テスト・手順

- `PlannerTest::test_shows_the_tide_without_the_lunar_day`
- `PlannerTest::test_spots_are_ordered_by_catches_including_others_public_trips`
- `PlannerTest::test_only_visible_spots_in_the_chosen_prefecture_are_listed`
- `PlannerTest::test_caution_and_link_to_the_karte_with_the_same_date`
- `PlannerTest::test_exact_match_comes_before_relaxed_matches`
- `ConditionMatcherTest::test_match_by_spot_chooses_the_level_for_each_spot`
- `PlannerTest::test_guest_is_asked_to_choose_a_prefecture_first`
- `PlannerTest::test_guest_uses_only_public_trips`
- `DummyTripTest::test_planner_shows_dummy_count_and_breakdown`

結果：成功（GitHub Actions）

備考：天気予報はプランナーに出さずカルテで見る。条件は時期を先に絞り、ハンコで見せる（#158）。定義書ではログインが必要だが、登録の前に主役の機能を体験できるよう、ゲストにも開いた（#209。decisions.md）

### FN-17

**メインフィールドとエリア絞り込み**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 登録時に必須、プロフィールで変えられる
2. プランナー・釣り場一覧・フィード・ヒートマップは最初メインフィールドの県
3. ほかの県・全国に切り替えられる
4. ゲストは見るたびに県を選ぶ

テスト・手順

- `RegistrationTest::test_home_prefecture_must_be_one_of_47_prefectures`
- `ProfileTest::test_home_prefecture_must_be_one_of_47_prefectures_on_update`
- `SpotIndexTest::test_spots_are_filtered_by_home_prefecture_by_default`
- `SeasonHeatmapTest::test_page_shows_public_of_home_prefecture_by_default`
- `SpotGuestTest::test_guest_is_asked_to_choose_a_prefecture_first`
- `SeasonHeatmapTest::test_empty_page_guides_to_record_and_all_prefectures`

結果：成功（GitHub Actions）

備考：釣行の記録の絞り込みも、最初はメインフィールドの県（#138）

### FN-18

**県内新着のアプリ内お知らせ**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 同じ県の公開の釣行・釣り場をお知らせする（自分・非公開は送らない）
2. 「釣り場だけ隠す」は釣り場名を入れない
3. ベルに未読件数、一覧で確認
4. プロフィールで ON／OFF

テスト・手順

- `NotificationSendTest::test_public_trip_is_sent_only_to_members_of_the_same_prefecture_with_notify_on`
- `NotificationSendTest::test_private_trip_is_not_sent`
- `NotificationListTest::test_spot_hidden_trip_does_not_show_the_spot_name`
- `NotificationListTest::test_bell_shows_unread_count_and_opening_the_list_marks_all_as_read`
- `NotificationListTest::test_notifications_can_be_turned_off_and_on_in_profile`

結果：成功（GitHub Actions）

備考：文章ではなく番号だけを保存し、表示のたびに公開範囲を確かめる（#76）。一覧を開いたら全部既読

## 非機能要件（NF）

| 番号 | 要件 | 状態 | 確認方法 | 結果 | 確認日 |
|---|---|---|---|---|---|
| [NF-01](#nf-01) | セキュリティ（本人だけ・ゲストの範囲・公開範囲） | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [NF-02](#nf-02) | 対応環境（PC とスマホ） | 完了 | 自動テスト＋画面 | 成功（GitHub Actions） | 2026-10-16 |
| [NF-03](#nf-03) | バックアップ | 完了 | 手作業 | 成功（公開環境は戻す試しまで） | 2026-10-23 |
| [NF-04](#nf-04) | 公開情報の運用 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [NF-05](#nf-05) | 規約とプライバシーポリシー | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [NF-06](#nf-06) | テスト方針（7つの自動チェック） | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [NF-07](#nf-07) | 初期データ（シーダー） | 一部対応 | 自動テスト | 一部成功 | 2026-10-24 |

### NF-01

**セキュリティ（本人だけ・ゲストの範囲・公開範囲）**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 登録・編集・削除は本人だけ
2. ゲストはフィード・カルテ・プランナー（公開の記録だけ。#209）まで（詳細・検索・CSV・投稿・編集は不可）
3. 「釣り場だけ隠す」はサーバーで取り除く
4. 画面以外の出口（お知らせ・提案・CSV）でも同じチェック
5. Exif を消す
6. 位置は小数第2位で切り捨て
7. パスワードはハッシュ化

テスト・手順

- `TripUpdateTest::test_others_cannot_open_edit_or_update`
- `TripShowTest::test_guest_is_sent_to_login`
- `TripExportTest::test_guest_is_sent_to_login`
- `SpotGuestTest::test_guest_cannot_open_the_spot_form`
- `TripShowTest::test_spot_hidden_trip_does_not_contain_spot_name_or_link`
- `NotificationListTest::test_spot_hidden_trip_does_not_show_the_spot_name`
- `NearbySpotsTest::test_others_private_spot_is_not_suggested`
- `TripExportTest::test_only_my_trips_even_with_public_scope`
- `CatchPhotoTest::test_exif_is_removed_from_the_saved_photo`
- `SpotLocationTest::test_location_is_cut_down_not_rounded_up`
- `PasswordUpdateTest::test_password_can_be_updated`

結果：成功（GitHub Actions）

### NF-02

**対応環境（PC とスマホ）**

- 状態：完了　／　確認方法：自動テスト＋画面　／　確認日：2026-10-16

受け入れの条件

1. PC（Chrome）でもスマホ（幅 360px）でも、レイアウトが崩れずに見られる

テスト・手順

- 画面：Chrome の開発者ツールで幅 360px と 1280px にして全画面を開く（Phase 1 で全画面、以後は画面を変えるたびに確認）。README の画面の画像は PC 1280×900 とスマホ 390×844 で撮影。`NavigationTest::test_admin_tab_is_short_on_narrow_screens`

結果：成功（GitHub Actions）。画面も確認済み（開発環境の Chrome。幅 360px は 2026-10-03、README の画像は 2026-10-16 に PC 1280×900・スマホ 390×844 で撮影）

備考：せまい画面ではナビの付箋のアイコンを消して名前を小さく（#124）

### NF-03

**バックアップ**

- 状態：完了　／　確認方法：手作業　／　確認日：2026-10-23

受け入れの条件

1. データベースと写真のバックアップを定期的に取る
2. 実際に戻せる

テスト・手順

- 公開環境：GitHub Actions の `.github/workflows/backup.yml` が毎日、Aiven のデータベースを書き出して R2 の非公開のバケット `fishinglog-backups` に置き（30日分）、写真を写す。毎回、空の MySQL に戻してテーブルと件数を確かめる
- 2026-10-23 に手で動かした結果：本番と戻したものの件数が一致（users 18・spots 138・trips 840・catches 1158）、ファイル 47KB、写真の写しの枚数も確認。わざとデータベースの住所を間違えると、実行が失敗になった
- 開発環境：`scripts/backup.sh` と `scripts/restore.sh`（手順は [`docs/backup.md`](backup.md)）。2026-10-03 に実際に戻せることを確認

結果：成功（公開環境は GitHub Actions のバックアップの実行で、戻す試しまで成功。開発環境も戻せることを確認）

備考：本番に戻す手順・設定・失敗したときの見方は docs/backup.md（版 2.0）。Aiven の自動バックアップとは別に、自前の写しを別の場所に持つ（#204）

### NF-04

**公開情報の運用**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 釣り禁止・立入禁止の場所を登録しないことを規約と登録フォームに書く
2. 注意区分の警告をカルテとプランナーに出す
3. 駐車場は公式かどうかを区別
4. 不適切な投稿の報告と、管理者が対応状況を変える画面
5. ほかの人に見えるのはニックネームだけ

テスト・手順

- `ReportTest::test_can_report_public_and_spot_hidden_trips`
- `ReportTest::test_cannot_report_what_i_cannot_see`
- `ReportTest::test_cannot_report_my_own_posts`
- `AdminReportTest::test_only_admin_can_use_admin_pages`
- `AdminReportTest::test_admin_can_change_status`
- `AdminReportTest::test_hide_trip_makes_it_private_and_closes_its_reports`
- `PlannerTest::test_caution_and_link_to_the_karte_with_the_same_date`
- `HomeTest::test_terms_and_privacy_have_the_required_items`
- `SeederTest::test_ports_warn_that_fishing_is_often_prohibited`

結果：成功（GitHub Actions）

備考：「非表示」は公開範囲を非公開にする（#94）。確認の担当・期限（3日以内）・基準・訂正の手順は運用の手順書 [`docs/operations.md`](operations.md) に書いた（#206）

### NF-05

**規約とプライバシーポリシー**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 利用規約とプライバシーポリシーのページがある
2. 登録時に同意が必須
3. 書くことが入っている

テスト・手順

- `HomeTest::test_terms_and_privacy_pages_have_the_menu`
- `HomeTest::test_terms_and_privacy_have_the_required_items`
- `RegistrationTest::test_terms_must_be_accepted`

結果：成功（GitHub Actions）

備考：退会すると写真も消す（#118）

### NF-06

**テスト方針（7つの自動チェック）**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件（次の7つを Feature テストで自動で確かめる）

1. 非公開の釣行は、ほかの人から見られない
2. 「釣り場だけ隠す」の釣行は、サーバーが返すデータに釣り場名と位置が入っていない
3. 全体公開の位置は、小数第2位に丸められている
4. ゲストは、釣行の詳細・条件検索・CSV 出力に入れない
5. お知らせの文章に、「釣り場だけ隠す」の釣り場名が入っていない
6. 非公開の釣り場にひもづく「全体公開」の釣行でも、フィードや釣行詳細に釣り場名と位置が出ない
7. 「この釣り場ですか？」の提案に、ほかの人の非公開の釣り場が出ない。CSV にほかの人のデータが入らない

テスト・手順

- ① `TripShowTest::test_private_trip_returns_404_to_others`
- ② `TripShowTest::test_spot_hidden_trip_does_not_contain_spot_name_or_link`、`FeedTest::test_spot_hidden_trip_does_not_contain_spot_name`
- ③ `SpotLocationTest::test_others_see_only_the_rounded_location`
- ④ `TripShowTest::test_guest_is_sent_to_login`、`TripExportTest::test_guest_is_sent_to_login`（条件検索は釣行一覧と同じログインが必要なグループ）
- ⑤ `NotificationListTest::test_spot_hidden_trip_does_not_show_the_spot_name`
- ⑥ `FeedTest::test_public_trip_on_private_spot_does_not_contain_spot_name`、`TripShowTest::test_public_trip_on_private_spot_is_treated_as_spot_hidden`
- ⑦ `NearbySpotsTest::test_others_private_spot_is_not_suggested`、`TripExportTest::test_only_my_trips_even_with_public_scope`
- GitHub Actions で push のたびに全部流している（#196）

結果：成功（GitHub Actions）

備考：テストは公開範囲とアクセス制御に絞らず、計算や入力チェックにも書いた（394件）

### NF-07

**初期データ（シーダー）**

- 状態：一部対応　／　確認方法：自動テスト　／　確認日：2026-10-24

受け入れの条件

1. ダミーのユーザー数名
2. 釣り場と現地の情報・注意区分
3. 釣行を50件ぐらい（条件がばらばら、坊主あり）
4. 公開範囲3通りを混ぜる
5. 本番公開のときに開発者自身の実際の釣行を数件登録する

テスト・手順

- `SeederTest::test_real_spots_are_in_every_prefecture_except_gifu`
- `SeederTest::test_dummy_trips_are_at_model_ports_and_akita_ports`
- `SeederTest::test_all_visibilities_are_mixed`
- `SeederTest::test_some_trips_are_bozu`
- `SeederTest::test_catches_follow_the_season`
- `SeederTest::test_tide_matches_the_date`
- `DeployTest::test_production_seeder_does_not_use_the_readme_password`

結果：一部成功（自動テストは成功。開発者自身の実際の釣行の登録は未達）

備考：釣り場は全国の実在の港131・湖7、釣行は840件で季節あり（#106・#160）。管理者以外の釣り人はダミーの人（`users.is_sample`）で、その釣行は画面で「ダミー」と出る（#211）。**足りないこと**：⑤ の開発者自身の実際の釣行はまだ（FN-13 と同じ。本番に移るときに登録する。[`docs/release-plan.md`](release-plan.md) の項目 10）

## 画面（PG）

| 番号 | 画面 | 状態 | 確認方法 | 結果 | 確認日 |
|---|---|---|---|---|---|
| [PG01](#pg01) | トップページ `/` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG02](#pg02) | ユーザー登録 `/register` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG03](#pg03) | ログイン `/login` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG04](#pg04) | ログアウト `/logout` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG05](#pg05) | ダッシュボード `/dashboard` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG06](#pg06) | 釣り場カルテ一覧 `/spots` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG07](#pg07) | 釣り場カルテ詳細（主役） `/spots/{spot}` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG08](#pg08) | 釣り場登録 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG09](#pg09) | 釣り場編集 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG10](#pg10) | 釣行一覧 `/trips` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG11](#pg11) | 釣行詳細 `/trips/{trip}` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG12](#pg12) | 釣行登録 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG13](#pg13) | 釣行編集 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG14](#pg14) | 釣行削除 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG15](#pg15) | 過去釣行のまとめて登録 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG16](#pg16) | シーズンヒートマップ `/analysis/heatmap` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG17](#pg17) | CSV 出力 `/trips/export` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG18](#pg18) | プロフィール編集 `/profile` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG19](#pg19) | アカウント削除 | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG20](#pg20) | 公開釣果フィード `/feed` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG21](#pg21) | 公開投稿の報告 `/reports` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG22](#pg22) | 釣行プランナー（主役） `/planner` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-24 |
| [PG23](#pg23) | 利用規約 `/terms` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG24](#pg24) | プライバシーポリシー `/privacy` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |
| [PG25](#pg25) | お知らせ一覧 `/notifications` | 完了 | 自動テスト | 成功（GitHub Actions） | 2026-10-23 |

### PG01

**トップページ `/`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. ゲストに入口と新着の公開釣果を出す。会員はダッシュボードへ

テスト・手順

- `HomeTest::test_guest_sees_entry_links_and_footer`
- `HomeTest::test_member_is_redirected_to_dashboard`
- `HomeTest::test_spot_hidden_trip_does_not_contain_spot_name`

結果：成功（GitHub Actions）

### PG02

**ユーザー登録 `/register`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 県と規約への同意が必須

テスト・手順

- `RegistrationTest::test_new_users_can_register`
- `RegistrationTest::test_terms_must_be_accepted`

結果：成功（GitHub Actions）

### PG03

**ログイン `/login`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 正しいパスワードでログインでき、まちがいは断る

テスト・手順

- `AuthenticationTest::test_users_can_authenticate_using_the_login_screen`
- `AuthenticationTest::test_users_can_not_authenticate_with_invalid_password`

結果：成功（GitHub Actions）

### PG04

**ログアウト `/logout`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. ログアウトできる

テスト・手順

- `AuthenticationTest::test_users_can_logout`

結果：成功（GitHub Actions）

### PG05

**ダッシュボード `/dashboard`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. サマリー指標・継続カウンタ・気づき・自己ベスト

テスト・手順

- `DashboardTest::test_summary_counts_only_my_catches_species_and_days`
- `KeepRecordingTest::test_dashboard_shows_counter_with_no_trips`

結果：成功（GitHub Actions）

### PG06

**釣り場カルテ一覧 `/spots`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 公開と自分の釣り場だけ。県で絞る。自分の釣果数と並べ替え

テスト・手順

- `SpotIndexTest::test_public_spots_and_own_spots_are_listed_but_others_private_spots_are_not`
- `SpotIndexTest::test_my_catch_count_is_shown_without_others_catches`
- `SpotIndexTest::test_spots_can_be_sorted_by_last_visit_and_by_catches`
- `SpotGuestTest::test_guest_sees_only_public_spots_in_the_chosen_prefecture`

結果：成功（GitHub Actions）

### PG07

**釣り場カルテ詳細（主役） `/spots/{spot}`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 判断ビュー・実績・表・現地の情報。ゲストは正確な位置と詳細へのリンクなし

テスト・手順

- `SpotShowTest::test_others_private_spot_returns_404_but_own_private_spot_can_be_opened`
- `SpotGuestTest::test_guest_sees_only_public_trips_on_the_karte`
- `SpotLocationTest::test_guests_see_only_the_rounded_location`
- `SpotJudgeTest::test_shows_tide_and_next_big_tide_for_the_chosen_date`

結果：成功（GitHub Actions）

### PG08

**釣り場登録**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 登録でき、登録者はフォームから変えられない

テスト・手順

- `SpotStoreTest::test_user_can_register_a_spot`
- `SpotStoreTest::test_created_by_cannot_be_set_from_the_form`

結果：成功（GitHub Actions）

### PG09

**釣り場編集**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 基本の情報は本人、現地の情報は全員

テスト・手順

- `SpotUpdateTest::test_owner_can_update_basic_info_and_local_info`
- `SpotUpdateTest::test_others_can_update_only_local_info_even_if_they_send_basic_info`

結果：成功（GitHub Actions）

### PG10

**釣行一覧 `/trips`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 自分の釣行と、条件検索・自分／みんな

テスト・手順

- `TripShowTest::test_index_lists_only_my_trips`
- `TripSearchTest::test_conditions_are_combined`

結果：成功（GitHub Actions）

### PG11

**釣行詳細 `/trips/{trip}`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 公開範囲どおりに見せる。ゲストは不可

テスト・手順

- `TripShowTest::test_owner_sees_everything_including_notes`
- `TripShowTest::test_others_can_see_public_trip_but_not_notes`
- `TripShowTest::test_guest_is_sent_to_login`

結果：成功（GitHub Actions）

### PG12

**釣行登録**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 釣行と釣果を1つのフォームで登録

テスト・手順

- `TripStoreTest::test_user_can_record_a_trip_with_catches`

結果：成功（GitHub Actions）

### PG13

**釣行編集**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 本人だけ編集できる

テスト・手順

- `TripUpdateTest::test_owner_can_update_trip_and_catches_are_replaced`
- `TripUpdateTest::test_others_cannot_open_edit_or_update`

結果：成功（GitHub Actions）

### PG14

**釣行削除**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 本人だけ削除でき、写真も消える

テスト・手順

- `TripUpdateTest::test_owner_can_delete_trip_with_its_catches`
- `TripUpdateTest::test_others_cannot_delete`
- `CatchPhotoTest::test_photo_files_are_deleted_with_the_trip`

結果：成功（GitHub Actions）

### PG15

**過去釣行のまとめて登録**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 写真をまとめて選び、同じ日・釣り場・時間帯を1つの釣行にまとめる

テスト・手順

- `BulkTripTest::test_bulk_mode_shows_the_bulk_form`
- `BulkTripTest::test_rows_on_the_same_day_spot_and_time_become_one_trip`

結果：成功（GitHub Actions）

備考：保存の入口は `POST /trips/bulk` に分けた

### PG16

**シーズンヒートマップ `/analysis/heatmap`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 月×魚種の表、自分／みんな、県

テスト・手順

- `SeasonHeatmapTest::test_page_shows_public_of_home_prefecture_by_default`
- `SeasonHeatmapTest::test_dashboard_has_a_link_and_guest_is_sent_to_login`

結果：成功（GitHub Actions）

### PG17

**CSV 出力 `/trips/export`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 自分のデータだけ

テスト・手順

- `TripExportTest::test_only_my_trips_even_with_public_scope`

結果：成功（GitHub Actions）

### PG18

**プロフィール編集 `/profile`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. ニックネーム・県・お知らせの ON／OFF を変えられる

テスト・手順

- `ProfileTest::test_profile_information_can_be_updated`
- `NotificationListTest::test_notifications_can_be_turned_off_and_on_in_profile`

結果：成功（GitHub Actions）

### PG19

**アカウント削除**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. パスワードを確かめて退会、写真も消える

テスト・手順

- `ProfileTest::test_user_can_delete_their_account`
- `ProfileTest::test_correct_password_must_be_provided_to_delete_account`
- `ProfileTest::test_photos_are_deleted_with_the_account`

結果：成功（GitHub Actions）

### PG20

**公開釣果フィード `/feed`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. ゲストも見られる、公開範囲どおり

テスト・手順

- `FeedTest::test_only_public_and_spot_hidden_trips_are_listed`
- `FeedTest::test_guest_is_asked_to_choose_a_prefecture_first`

結果：成功（GitHub Actions）

### PG21

**公開投稿の報告 `/reports`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 見えるほかの人の投稿だけを報告できる

テスト・手順

- `ReportTest::test_can_report_public_and_spot_hidden_trips`
- `ReportTest::test_guest_cannot_report`

結果：成功（GitHub Actions）

### PG22

**釣行プランナー（主役） `/planner`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-24

受け入れの条件

1. 日付・県から釣り場を提案
2. ゲストも開ける。「自分の記録だけ」は会員だけ（#209）

テスト・手順

- `PlannerTest::test_spots_are_ordered_by_catches_including_others_public_trips`
- `PlannerTest::test_mine_scope_uses_only_my_trips`
- `PlannerTest::test_guest_cannot_use_mine_scope`
- `HomeTest::test_guest_sees_what_can_be_tried_before_signing_up`

結果：成功（GitHub Actions）

備考：定義書ではログインが「必要」だが、ゲストにも開いた（#209。decisions.md）。トップに「登録しなくても試せること／登録すると使えること」を出す

### PG23

**利用規約 `/terms`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. だれでも見られる

テスト・手順

- `HomeTest::test_terms_and_privacy_pages_have_the_menu`

結果：成功（GitHub Actions）

### PG24

**プライバシーポリシー `/privacy`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. だれでも見られる

テスト・手順

- `HomeTest::test_terms_and_privacy_have_the_required_items`

結果：成功（GitHub Actions）

### PG25

**お知らせ一覧 `/notifications`**

- 状態：完了　／　確認方法：自動テスト　／　確認日：2026-10-23

受け入れの条件

1. 一覧で確認でき、開いたら既読

テスト・手順

- `NotificationListTest::test_bell_shows_unread_count_and_opening_the_list_marks_all_as_read`
- `NotificationListTest::test_guest_is_sent_to_login`

結果：成功（GitHub Actions）

備考：既読にする PATCH は作らず、一覧を開いたら全部既読（#76）

### 定義書にない画面（足したもの）

| 画面 | 内容 | 確認方法 | テスト・手順 | 結果 | 確認日 |
|---|---|---|---|---|---|
| 自己ベスト `/bests` | 魚種ごとの自己ベストを写真つきで（#170） | 自動テスト | `PersonalBestsTest::test_bests_page_lists_every_species_with_links`、`PersonalBestsTest::test_guest_cannot_see_bests_page` | 成功（GitHub Actions） | 2026-10-23 |
| 使い方 `/guide` | 使う流れの順に画面とボタンの名前で説明（#116） | 自動テスト | `GuideTest::test_guest_can_open_the_guide` | 成功（GitHub Actions） | 2026-10-23 |
| 単位変換 `/tools/converter` | 重さ・長さ・ラインの号と lb（#122） | 自動テスト | `UnitConverterTest::test_guest_can_open_the_converter`、`UnitConverterTest::test_nylon_table_goes_up_in_order` | 成功（GitHub Actions） | 2026-10-23 |
| 管理画面 `/admin/reports` | 報告の一覧・対応状況・非表示（#94。NF-04） | 自動テスト | `AdminReportTest::test_only_admin_can_use_admin_pages`、`AdminReportTest::test_list_shows_report_and_post_without_private_notes` | 成功（GitHub Actions） | 2026-10-23 |
| 変更の履歴 `/spots/{spot}/history` | 現地の情報の変更の履歴（会員だけ）。管理者は前の値に戻せる（`POST /admin/spot-changes/{change}/revert`。#200） | 自動テスト＋画面 | `LocalInfoHistoryTest::test_history_page_shows_all_changes_to_members_only`、`LocalInfoHistoryTest::test_admin_can_revert_a_change_and_the_revert_is_recorded` | 成功（GitHub Actions）。画面も確認済み（開発環境） | 2026-10-23 |
| エラーページ | 何が起きたかと次にすることをふつうの言葉で（#120） | 自動テスト | `ErrorPageTest::test_not_found_page_is_in_plain_japanese` | 成功（GitHub Actions） | 2026-10-23 |
