# セキュリティポリシー（Wawa.hz テーマ）

Wawa.hz は、Material 3 Expressive デザインで構築した WordPress ブログ / 作品アーカイブ用の
オリジナルテーマです。本リポジトリには **テーマのみ** が含まれます
（WordPress 本体・DB・アップロード画像・`wp-config.php` は含みません）。

## サポート対象バージョン

サポート対象は **`main` ブランチの最新コミット** です。
過去のコミット・スナップショットは対象外です。

## 脆弱性の報告方法

脆弱性を発見した場合は、**公開のIssueには詳細を書かないでください。**

1. GitHubリポジトリの **Security タブ → Report a vulnerability**
   （プライベート脆弱性報告）から報告するのが推奨です。
2. 機能しない場合は、リポジトリオーナーへ GitHub 経由で直接ご連絡ください。

### 報告に含める情報

- 問題の概要と、再現手順（または概念実証コード）
- 影響を受けるファイル（`functions.php` / `inc/*.php` / `template-parts/*.php` / `view.php` など）と、
  再現に必要な画面・操作
- 想定される影響度

### 報告に含めない情報

- 本物の秘密情報（APIキー・DBのパスワード・認証用の鍵）は貼らないでください。
  該当する値は報告前に失効・再発行してください。

### 対応の方針

- 受領は **2営業日以内** を目安にご連絡します
- 修正は非公開の環境で準備し、**修正を `main` へ反映した後**に内容を開示します

## 対象範囲

- 対象: 本リポジトリのテーマコード（PHP テンプレート / CSS / JavaScript / 設定ファイル）
- 対象外: WordPress 本体・プラグイン・サーバーソフトウェア自体の問題
  （各プロバイダの報告窓口をご利用ください）
- 対象外: テーマを設置したサイト固有の問題
  （サーバー設定・`wp-config.php`・DB・アップロード画像。運用者側の管理責任です）

## テーマコードで実施している対策

- **CSRF**: 管理画面から保存する処理は `wp_verify_nonce()` で nonce を検証します
  （Now Playing の保存・ホームの Pickup / Gallery / Now Playing ピン留め）。
- **入力**: 受け取った値は `sanitize_text_field()` / `wp_unslash()` / `absint()` で正規化します。
  `$_GET` / `$_POST` をそのまま出力・保存しません。
- **出力**: 画面へ出す値は `esc_html()` / `esc_attr()` / `esc_url()` を通します。
- **画面ルーティング**: `view.php` の `?view=` は許可したキーの表（`category` / `post` / `gallery` /
  `game` / `nowplaying` / `profile` / `info`）だけを `template-parts/` の固定ファイル名へ対応させます。
  入力文字列をそのままパスに使わないため、任意ファイルの読み込み（LFI）は成立しません。
- **YouTube**: 動画ID・プレイリストIDは正規表現で形式を検証し、
  埋め込みは YouTube 公式プレイヤー（`iframe` / `youtube-nocookie`）のみを使用します。
- **直接アクセス**: `.htaccess`（Apache）と `web.config`（IIS）で、
  `.md` / `.json` / `.lock` / `.git*` への HTTP 直接アクセスを拒否します。
- **秘密情報を置かない**: テーマは APIキー・DBパスワードを一切持ちません
  （`wp-config.php` は WordPress 本体側で管理し、`.gitignore` で除外しています）。

## セルフホスト運用者へ

公開サーバーとして運用する場合、以下は運用者ご自身の責任で行ってください。

- HTTPSでの公開（リバースプロキシ・CDNの利用など）
- WordPress 本体・プラグイン・テーマの更新
- `wp-config.php` の保護（DBのパスワード・認証用ソルトはテーマへ書かない）
- 管理画面の保護（強固なパスワード、必要なら追加のアクセス制限）
- テーマの設置は `wp-content/themes/Wawa.hz` へ `git clone`（`WINDOWS_DEPLOY.md` 参照）
- PHP本体の更新（下記を参照）

## PHP本体のセキュリティ更新（2026-10-21）

2026-10-21 に PHP プロジェクトから、未認証のリモートコード実行（RCE）につながる
2件の脆弱性が公表されました。PHP-FPM / Apache mod_php / php-cgi / FrankenPHP /
LiteSpeed が対象です。本テーマは PHP 8 系で動作するため、影響を受けます。

| CVE | 内容 |
| --- | --- |
| CVE-2026-109618 (GHSA-w8hw-w9fx-j79w) | OOM 処理の途中で内部リクエストデータが未初期化のまま残り、後始末が不正なデータを有効として扱う |
| CVE-2026-109619 (GHSA-6wmf-4rv5-p3rp) | 解放済みメモリ上の古いグローバル状態を、同じワーカーの後続リクエストが使う |

修正版は **PHP 8.5.12 / 8.4.27 / 8.3.36 / 8.2.35** です。8.1 以前はサポートが
終了しており、PHP プロジェクトからの修正版はありません。

- 更新例: macOS(Homebrew) `brew update && brew upgrade php` /
  Windows `winget upgrade PHP.PHP` / Debian系 `sudo apt update && sudo apt upgrade php`
- 更新後は Web サーバー（Apache / IIS / PHP-FPM）を再起動してください
- `display_errors` は本番で `Off` にし、エラーの詳細（パス・設定）を画面へ出さないでください

参考: [PHP セキュリティアドバイザリ（2026-10-21）](https://www.php.net/archive/2026.php#2026-10-21)

## ライセンス

テーマ本体は GNU General Public License v2 or later（`style.css` のテーマヘッダー参照）です。
