# お知らせ管理アプリケーション

重要・地域貢献・その他のカテゴリに分けて PDF と音声付きのお知らせを投稿し、一覧ページから確認できる PHP アプリケーションです。投稿データは MySQL データベースで管理され、音声ファイルのパスも保存されます。phpMyAdmin からテーブルを確認・編集できます。

## 主な機能

- 投稿フォームからタイトル・PDF・音声を登録
- `uploads/` ディレクトリにファイルを保存し、パスをデータベースへ登録
- 一覧ページ（`form.php` / `boranthia.php` / `sonota.php`）で最新投稿をカテゴリ別に取得して表示
- 一覧の音声アイコンからブラウザ内で音声を再生

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
   - これにより `butouha_app` データベースとカテゴリ別の投稿テーブル（`important_posts`、`contribution_posts`、`other_posts`）が作成されます。

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

## 動作確認

1. `post_form.php?category=important` など各カテゴリの投稿フォームからタイトルと PDF を入力し、音声を録音して投稿します。
2. `upload.php` の完了画面でアップロード結果とデータベース登録状態を確認します。
3. `form.php`、`boranthia.php`、`sonota.php` の各一覧で投稿が表示され、音声アイコンから再生できることを確認します。

## 補足

- データベースエラーが発生した場合は `upload.php` に警告が表示されます。Web サーバーのログを参照し、`config.php` の接続情報を確認してください。
- 投稿一覧は `posts.php?category=important` などカテゴリを指定して JSON 形式で取得します。外部システムからの連携にも再利用できます。




# 会議室予約システム

このリポジトリは、高橋建設の会議室予約および予定確認のための簡易Webアプリケーションです。トップページ（`me.html`）から予約メニューや年間予定（`yotei.php`）にアクセスできます。

## 主な画面
- **`me.html`**: トップページ。予約メニューと年間予定への導線があります。
- **`reserve.php`**: 会議室の種類（大会議室 / 小会議室）を選択する画面。
- **`calender.php` / `smallcalender.php`**: それぞれ大会議室・小会議室の予約カレンダー。週単位で空き状況を確認し予約・取消ができます。
- **`yotei.php`**: 年間カレンダー。祝日や社内行事に加えて、登録済みの会議室予約を日付ごとに確認できます。

## データベース設定（phpMyAdmin / MySQL）
1. phpMyAdminなどからMySQLにログインし、利用したいデータベースを作成します（例: `meeting_app`）。
2. `config.php` のホスト名・データベース名・ユーザー名・パスワードを環境に合わせて編集します。
3. アプリケーションを初めて開くと、自動的に `reservations` テーブルが作成されます。手動で作成する場合は下記SQLを実行してください。

```sql
CREATE TABLE IF NOT EXISTS reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room ENUM('large','small') NOT NULL,
  datetime DATETIME NOT NULL,
  name VARCHAR(100) NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY room_datetime_unique (room, datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 補足
- 予約カレンダーで「〇」は予約可能、「×」は予約済みを表します。予約済みスロットをクリックすると内容を確認したり取消できます。
- `yotei.php` ではクリックした日付の会議室予約一覧が表示され、年間の予定とあわせて確認できます。
