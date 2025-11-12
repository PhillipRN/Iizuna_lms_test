# 書籍アップロード／LMSクイズ: 選択肢ID化計画

## 1. 背景と課題
- 現在の `json_quiz` データ構造は、選択肢を文字列 (`answer_text`) だけで保持している。
- 採点処理 (`JsonQuizController::IsCorrectAnswer`) は `strip_tags()` 後の文字列比較に依存しているため、傍線 `<u>` など装飾タグで差を表す問題は正誤判定とログ表示が破綻する。
- 結果画面でも `answers_json` に保存された回答が「最初に一致した選択肢の文字列」に置換されてしまい、ユーザーの実際の選択肢が表示されない。

## 2. ゴール
- すべての選択肢にユニークID（`answer_id`）を付与し、フロント／バックともにIDベースでやり取りする。
- 既存の `json_quiz` と `json_quiz_result`（`answers_json`）を後方互換を保ったまま ID 付きに移行する。
- 今後の CSV → JSON 変換でも自動的に `answer_id` を生成する。

## 3. 影響範囲
| 領域 | 影響内容 |
| --- | --- |
| データベース | `json_quiz.json` の `answers[]` へ `answer_id` を追加。`json_quiz_result.answers_json` の `answer` を ID へ置換。 |
| コンバータ (`setup_database/iizuna_lms/converter.php`) | CSV から JSON を生成する際に `answer_id` を付与。 |
| フロント（学生画面 `_quiz.html`） | ラジオ／テキスト回答で送信する値を `answer_id` に変更。 |
| バックエンド (`JsonQuizController`) | ID を使って weight 判定・結果保存。`answers_json` の `answer` も ID を保持し、表示時に逆引き。 |
| テストコード | 新旧混在がないことを確認するための追加テスト。 |

## 4. 実装ステップ
1. **データ移行スクリプトの作成**
   - `json_quiz.json` を走査し、各 `question` ごとに `answers[].answer_id` を生成（例：`"{question_id}-{index}"` または `CHOICESNUM` 由来のキー`）。
   - `json_quiz_result.answers_json` を読み込み、`question_id` + `answer_text` で逆引きして `answer_id` に置換。既に ID 付きの場合はそのまま。
   - スクリプトは再実行しても安全なように idempotent に設計する。

2. **CSV → JSON コンバータの改修**
   - `setup_database/iizuna_lms/converter.php`（および関連スクリプト）で、選択肢生成時に `answer_id` を付与して JSON を出力。
   - 生成ルールは移行スクリプトと一致させる（`question_id` + `CHOICESNUM` 等）。

3. **バックエンドロジックの変更**
   - `JsonQuizController::IsCorrectAnswer` / `IsCorrectOtherAnswer` を、文字列比較ではなく `answer_id` 比較に変更。
   - `answers_json` 保存時も `answer_id` を記録し、結果表示時は `question` の選択肢配列から `answer_id` → `answer_text` を逆引きする。

4. **フロントエンドの更新**
   - `public/student/_quiz.html` で `<input type="radio" value="...">` を `answer_id` に変更。
   - テキスト入力（短答）の場合も、ID を保持する必要があれば同様に対応（短答式はこれまで通り文字列比較でよい場合は除外）。
   - 履歴や結果ダイアログでも `answer_id` を基に文字列を復元する仕組みに揃える。

5. **テスト・検証**
   - 既存クイズと新規クイズで `quiz.php` → `quiz_result.php` の往復テストを実施。
   - 傍線タグを含む設問で、選択肢表示／回答／結果表示が期待通りか確認。
   - `json_quiz_result` の `answers_json` が ID を保持し、再表示時に元の文字列が再現できることを確認。

6. **ロールアウト**
   - 先に移行スクリプトを本番で実行し、データが整ったことを確認。
   - その後アプリケーションの改修をデプロイ（順序を逆にすると旧データで失敗するため注意）。

## 5. リスクと対策
| リスク | 対策 |
| --- | --- |
| 既存 `answers_json` の文字列が重複しており、逆引きできない | 逆引き失敗時はログ出力し、手動確認できるようにする。問題ごとの `CHOICESNUM` を利用すれば多くの場合一意にマッピング可能。 |
| コンバータと移行ツールの ID 生成ルールに差異が出る | 生成ロジックを共通関数化するか、同一ルールをドキュメント化して維持する。 |
| デプロイ順序のミス（アプリ先行リリース） | Runbook を整備し、移行 → 動作確認 → アプリ更新の順で進める。 |

## 6. 想定スケジュール（例）
1. Day1: データ移行スクリプト実装＋ステージングで試験。
2. Day2: コンバータ・API 改修／フロント更新。
3. Day3: 結合テスト（既存・新規クイズ）。
4. Day4: 本番データ移行 → 本番リリース。

状況に応じて前倒し／後ろ倒し可能。少なくともステージングで移行 → アプリ更新を一度通し、Runbook を確定させてから本番適用する。
