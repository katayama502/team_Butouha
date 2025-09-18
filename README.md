# お知らせ管理アプリケーション

重要・地域貢献・その他のカテゴリに分けて PDF と音声付きのお知らせを投稿し、一覧ページから確認できる PHP アプリケーションです。ユーザーはログインして役割に応じた機能を利用でき、会議室（小会議室・大会議室）の予約も行えます。投稿データや予約情報は MySQL データベースで管理され、phpMyAdmin からテーブルを確認・編集できます。

## 主な機能

- ログイン機能（管理者・一般ユーザーの 2 役割）
- 投稿フォームからタイトル・PDF・音声を登録（管理者のみ）
- `uploads/` ディレクトリにファイルを保存し、パスをデータベースへ登録
- 一覧ページ（`form.php` / `boranthia.php` / `sonota.php`）で最新投稿をカテゴリ別に取得して表示
- 一覧の音声アイコンからブラウザ内で音声を再生
- 会議室（小会議室・大会議室）の予約機能

## 必要要件

- PHP 8.1 以降
- MySQL 5.7 / 8 互換 DB（MariaDB 10.5+ を含む）
- phpMyAdmin（データベースのGUI管理ツールとして任意）
- Composer など追加の依存はありません

## セットアップ手順

1. **リポジトリを取得**
   ```bash
   git clone &lt;repo-url&gt;
   cd team_Butouha
   ```

2. **データベースを作成**（phpMyAdmin または MySQL クライアント）
   - phpMyAdmin でログインし「インポート」を選択、`database/schema.sql` を読み込む。
   - 直接 MySQL を使用する場合は以下を実行:
     ```sql
     SOURCE database/schema.sql;
     ```
  - これにより `butouha_app` データベースとカテゴリ別の投稿テーブル（`important_posts`、`contribution_posts`、`other_posts`）、ユーザー管理用の `app_users`、会議室予約の `reservations` テーブルが作成されます。
  - `app_users` テーブルにはサンプルアカウントが登録されます。

3. **接続情報を設定**
   - `config.php` は環境変数 `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` を参照します。
   - もしくは `config.php` 内のデフォルト値（`127.0.0.1`, `butouha_app`, `root`, 空パスワード）を環境に合わせて編集してください。

4. **ファイル保存ディレクトリを準備**
   - `uploads/` ディレクトリは投稿時に自動作成されます。Web サーバーから書き込みできるように権限を設定してください。

5. **ローカルサーバーを起動**（例）
   ```bash
   php -S 127.0.0.1:8000
   ```
   ブラウザで `http://127.0.0.1:8000/index.php` へアクセスします。

## アカウント情報

`database/schema.sql` の実行時に以下のサンプルアカウントが登録されます。必要に応じて `app_users` テーブルで変更してください。

| 役割 | ユーザー名 | パスワード | 利用可能な機能 |
| ---- | ---------- | ---------- | -------------- |
| 管理者 | `admin` | `adminpass` | 重要/地域貢献/その他の投稿、会議室予約 |
| 一般ユーザー | `user` | `userpass` | 重要・その他のお知らせ閲覧、会議室予約 |

## phpMyAdmin での管理

- `important_posts` / `contribution_posts` / `other_posts` の各テーブルには以下の列があります：
  | 列名 | 説明 |
  | ---- | ---- |
  | `id` | 自動採番の主キー |
  | `title` | 投稿タイトル |
  | `pdf_path` | PDF ファイルの相対パス（例：`uploads/document_20240101010101_xxxx.pdf`） |
  | `audio_path` | 音声ファイルの相対パス（例：`uploads/voice_20240101010101_xxxx.webm`） |
  | `created_at` | 投稿日時（自動設定） |
- phpMyAdmin 上で投稿を編集・削除すると、それぞれのカテゴリ一覧に即時反映されます。
- `app_users` テーブルにはログインに利用するユーザー（`username`、`password_hash`、`role`、`display_name`）が保存されます。
- `reservations` テーブルには会議室予約（`room`、`reserved_at`、`user_id`、`reserved_for`、`note`）が保存され、同じ日時・会議室の重複を防ぐユニーク制約があります。

## 動作確認

1. ログインページで上記のサンプルアカウントからいずれかを選んでログインします。
2. 管理者でログインした場合は `post_form.php?category=important` などからタイトルと PDF を入力し、音声を録音して投稿します。
3. `upload.php` の完了画面でアップロード結果とデータベース登録状態を確認します。
4. `form.php`、`boranthia.php`、`sonota.php` の各一覧で投稿が表示され、音声アイコンから再生できることを確認します（一般ユーザーは「重要」「その他」のみ閲覧可能です）。
5. 「会議室予約」ページで小会議室・大会議室の予約を登録し、重複が拒否されることと一覧へ反映されることを確認します。

## 補足

- データベースエラーが発生した場合は `upload.php` に警告が表示されます。Web サーバーのログを参照し、`config.php` の接続情報を確認してください。
- 投稿一覧は `posts.php?category=important` などカテゴリを指定して JSON 形式で取得します。外部システムからの連携にも再利用できます。
